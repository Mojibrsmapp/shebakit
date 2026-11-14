<?php
/** Role and read-only moderator permission helpers. */
if (!function_exists('sheba_permission_keys')) {
    function sheba_permission_keys(): array
    {
        return ['orders', 'history', 'recharge', 'services', 'users'];
    }
}

if (!function_exists('sheba_default_permissions')) {
    function sheba_default_permissions(): array
    {
        return array_fill_keys(sheba_permission_keys(), false);
    }
}

if (!function_exists('sheba_ensure_admin_role_column')) {
    function sheba_ensure_admin_role_column($conn): bool
    {
        static $ready = null;
        if ($ready !== null) { return $ready; }
        try {
            $columns = $conn->query("SHOW COLUMNS FROM admin_user LIKE 'role'");
            if (!$columns || $columns->num_rows === 0) {
                $conn->query("ALTER TABLE admin_user ADD COLUMN `role` VARCHAR(20) NOT NULL DEFAULT 'admin'");
                $columns = $conn->query("SHOW COLUMNS FROM admin_user LIKE 'role'");
            }
            $hasRole = (bool)($columns && $columns->num_rows > 0);
            $permissionColumn = $conn->query("SHOW COLUMNS FROM admin_user LIKE 'moderator_permissions'");
            if (!$permissionColumn || $permissionColumn->num_rows === 0) {
                $conn->query("ALTER TABLE admin_user ADD COLUMN moderator_permissions TEXT NULL DEFAULT NULL");
                $permissionColumn = $conn->query("SHOW COLUMNS FROM admin_user LIKE 'moderator_permissions'");
            }
            $ready = $hasRole && (bool)($permissionColumn && $permissionColumn->num_rows > 0);
        } catch (Throwable $e) {
            $ready = false;
        }
        return $ready;
    }
}

if (!function_exists('sheba_admin_role_record')) {
    function sheba_admin_role_record($conn, string $token): ?array
    {
        if ($token === '') { return null; }
        sheba_ensure_admin_role_column($conn);
        try {
            $roleColumn = $conn->query("SHOW COLUMNS FROM admin_user LIKE 'role'");
            $hasRoleColumn = (bool)($roleColumn && $roleColumn->num_rows > 0);
            $permissionColumn = $hasRoleColumn ? $conn->query("SHOW COLUMNS FROM admin_user LIKE 'moderator_permissions'") : false;
            $hasPermissionColumn = (bool)($permissionColumn && $permissionColumn->num_rows > 0);
        } catch (Throwable $e) { return null; }
        if ($hasRoleColumn) {
            $sql = $hasPermissionColumn
                ? 'SELECT `role`, status, moderator_permissions FROM admin_user WHERE token = ? LIMIT 1'
                : 'SELECT `role`, status FROM admin_user WHERE token = ? LIMIT 1';
        } else {
            $sql = 'SELECT status FROM admin_user WHERE token = ? LIMIT 1';
        }
        $stmt = $conn->prepare($sql);
        if (!$stmt) { return null; }
        $stmt->bind_param('s', $token);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        if (!$row || (int)($row['status'] ?? 0) !== 1) { return null; }
        $role = strtolower(trim((string)($row['role'] ?? 'admin')));
        if (!in_array($role, ['admin', 'moderator'], true)) { return null; }
        $permissions = sheba_default_permissions();
        if ($role === 'moderator' && $hasPermissionColumn && !empty($row['moderator_permissions'])) {
            $decoded = json_decode((string)$row['moderator_permissions'], true);
            if (is_array($decoded)) {
                foreach (sheba_permission_keys() as $key) { $permissions[$key] = !empty($decoded[$key]); }
            }
        }
        return ['role' => $role, 'permissions' => $permissions];
    }
}

if (!function_exists('sheba_admin_role_for_token')) {
    function sheba_admin_role_for_token($conn, string $token): ?string
    {
        $record = sheba_admin_role_record($conn, $token);
        return $record['role'] ?? null;
    }
}

if (!function_exists('sheba_moderator_has_permission')) {
    function sheba_moderator_has_permission($conn, string $permission): bool
    {
        if (!in_array($permission, sheba_permission_keys(), true)) { return false; }
        $record = sheba_admin_role_record($conn, (string)($_SESSION['admin_token'] ?? ''));
        return ($record['role'] ?? '') === 'moderator' && !empty($record['permissions'][$permission]);
    }
}

if (!function_exists('sheba_moderator_route_permission')) {
    function sheba_moderator_route_permission(string $scriptName): ?string
    {
        $path = '/' . ltrim(str_replace('\\', '/', $scriptName), '/');
        if ($path === '/admin/moderator_panel.php' || $path === '/admin/profile.php' || $path === '/admin/logout.php') { return ''; }
        if ($path === '/admin/moderator_view.php') {
            $section = (string)($_GET['section'] ?? '');
            return in_array($section, sheba_permission_keys(), true) ? $section : null;
        }
        return null;
    }
}

if (!function_exists('sheba_enforce_moderator_scope')) {
    function sheba_enforce_moderator_scope($conn): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) { session_start(); }
        $token = (string)($_SESSION['admin_token'] ?? '');
        if ($token === '') { return; }
        $record = sheba_admin_role_record($conn, $token);
        if (!$record) {
            unset($_SESSION['admin_token'], $_SESSION['admin_role'], $_SESSION['admin_id']);
            http_response_code(401);
            if (!empty($_SERVER['HTTP_X_REQUESTED_WITH'])) {
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode(['success' => false, 'message' => 'লগইন সেশন আর সক্রিয় নেই। আবার লগইন করুন।']);
            } else { header('Location: /admin/index.php'); }
            exit;
        }
        $_SESSION['admin_role'] = $record['role'];
        if ($record['role'] === 'moderator') {
            $required = sheba_moderator_route_permission((string)($_SERVER['SCRIPT_NAME'] ?? ''));
            $allowed = $required === '' || ($required !== null && !empty($record['permissions'][$required]));
            if (!$allowed) {
                http_response_code(403);
                if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) || strpos((string)($_SERVER['HTTP_ACCEPT'] ?? ''), 'application/json') !== false) {
                    header('Content-Type: application/json; charset=utf-8');
                    echo json_encode(['success' => false, 'message' => 'এই পেজ দেখার অনুমতি আপনার নেই।']);
                } else {
                    $_SESSION['admin_access_notice'] = 'অ্যাডমিন আপনার জন্য এই অংশের অনুমতি চালু করেননি।';
                    header('Location: /admin/moderator_panel.php');
                }
                exit;
            }
            // View-only areas must never accept state-changing requests.
            if ($required !== '' && $_SERVER['REQUEST_METHOD'] !== 'GET' && $_SERVER['REQUEST_METHOD'] !== 'HEAD') {
                http_response_code(403);
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode(['success' => false, 'message' => 'মডারেটর অ্যাক্সেস শুধু দেখার জন্য।']);
                exit;
            }
        }
    }
}

if (!function_exists('sheba_require_admin_role')) {
    function sheba_require_admin_role($conn, array $allowedRoles = ['admin']): string
    {
        if (session_status() !== PHP_SESSION_ACTIVE) { session_start(); }
        $role = sheba_admin_role_for_token($conn, (string)($_SESSION['admin_token'] ?? ''));
        if ($role === null || !in_array($role, $allowedRoles, true)) {
            http_response_code(403);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['success' => false, 'message' => 'এই কাজের অনুমতি আপনার নেই।']);
            exit;
        }
        $_SESSION['admin_role'] = $role;
        return $role;
    }
}
