<?php
/**
 * Collect service records added through the admin service and pricing screens.
 * Missing optional tables are ignored so older installations keep rendering.
 */
if (!function_exists('shebakit_service_catalog')) {
    function shebakit_service_catalog($conn): array
    {
        $catalog = [];
        $queries = [
            ['SELECT id, service_name AS title, category, description, price, service_image AS image, is_pinned, status FROM custom_services WHERE status = \'active\'', 'custom'],
            ['SELECT id, service_title AS title, \'টেক্সট অর্ডার\' AS category, placeholder AS description, price, NULL AS image, 0 AS is_pinned, status FROM order_text_services WHERE status = \'active\'', 'text'],
            ['SELECT id, service_title AS title, \'ফাইল অর্ডার\' AS category, placeholder AS description, price, NULL AS image, 0 AS is_pinned, status FROM order_file_services WHERE status = \'active\'', 'file'],
            ['SELECT id, COALESCE(NULLIF(name, \'\'), orderName) AS title, \'রেগুলার সার্ভিস\' AS category, typeOforder AS description, price, NULL AS image, 0 AS is_pinned, work_status AS status, orderName, typeOforder FROM prices WHERE work_status = 1', 'price'],
        ];

        foreach ($queries as [$sql, $kind]) {
            try {
                $result = $conn->query($sql);
            } catch (Throwable $e) {
                $result = false;
            }
            if (!$result) {
                continue;
            }
            while ($row = $result->fetch_assoc()) {
                $row['kind'] = $kind;
                $row['title'] = trim((string)($row['title'] ?? ''));
                if ($kind === 'custom') {
                    $row['url'] = 'order_service.php?id=' . (int)$row['id'];
                    $image = trim((string)($row['image'] ?? ''));
                    $row['image_url'] = ($image !== '' && strpos($image, 'uploads/') === 0) ? 'admin/' . $image : $image;
                } elseif ($kind === 'text') {
                    $row['url'] = 'order_text.php?service_id=' . (int)$row['id'];
                    $row['image_url'] = '';
                } elseif ($kind === 'file') {
                    $row['url'] = 'order_file.php?service_id=' . (int)$row['id'];
                    $row['image_url'] = '';
                } else {
                    $orderName = strtolower(trim((string)($row['orderName'] ?? '')));
                    $type = strtolower(trim((string)($row['typeOforder'] ?? '')));
                    if ($orderName === 'sign_copy') {
                        $row['url'] = 'sign_order.php';
                    } elseif ($orderName === 'nid pdf') {
                        $row['url'] = 'nid_order.php';
                    } elseif ($type === 'text') {
                        $row['url'] = 'price_text_order.php?id=' . (int)$row['id'];
                    } else {
                        $row['url'] = 'file_work.php';
                    }
                    $row['image_url'] = '';
                }
                if ($row['title'] !== '') {
                    $catalog[] = $row;
                }
            }
            $result->free();
        }
        return $catalog;
    }
}

if (!function_exists('shebakit_service_sort')) {
    function shebakit_service_sort(array &$services): void
    {
        if (class_exists('Collator')) {
            $collator = new Collator('bn_BD');
            usort($services, static function ($a, $b) use ($collator) {
                $cmp = $collator->compare((string)$a['title'], (string)$b['title']);
                return $cmp === false ? strcasecmp((string)$a['title'], (string)$b['title']) : $cmp;
            });
            return;
        }
        usort($services, static function ($a, $b) {
            return strcasecmp((string)$a['title'], (string)$b['title']);
        });
    }
}
