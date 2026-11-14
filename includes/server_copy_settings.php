<?php
if (!function_exists('sheba_server_copy_settings')) {
    function sheba_server_copy_settings($conn): array
    {
        $settings = [
            'server_copy_enabled' => '1',
            'server_copy_api1_enabled' => '1',
            'server_copy_api2_enabled' => '1',
            'server_copy_api1_url' => '',
            'server_copy_api1_key' => '',
            'server_copy_api2_url' => '',
            'server_copy_api2_key' => '',
            'server_copy_smart_paste_enabled' => '1',
            'server_copy_download_list_enabled' => '1',
            'server_copy_download_v1_enabled' => '1',
            'server_copy_download_v2_enabled' => '1',
            'server_copy_download_nid_enabled' => '1',
            'server_copy_group_enabled' => '1',
            'server_copy_group_url' => 'https://chat.whatsapp.com/IKEmCVrXgwp5a87Lng1AGX',
            'server_copy_title' => 'ShebaKit সার্ভিস ড্যাশবোর্ড',
        ];
        try {
            $result = $conn->query("SELECT setting_key, setting_value FROM site_settings WHERE setting_key LIKE 'server_copy_%'");
            if ($result) {
                while ($row = $result->fetch_assoc()) {
                    $key = (string)($row['setting_key'] ?? '');
                    if (array_key_exists($key, $settings)) { $settings[$key] = (string)($row['setting_value'] ?? ''); }
                }
                $result->free();
            }
            if (stripos($settings['server_copy_title'], 'ICONIC') !== false) {
                $settings['server_copy_title'] = 'ShebaKit সার্ভিস ড্যাশবোর্ড';
                $stmt = $conn->prepare("UPDATE site_settings SET setting_value = ? WHERE setting_key = 'server_copy_title' AND setting_value LIKE '%ICONIC%'");
                if ($stmt) { $stmt->bind_param('s', $settings['server_copy_title']); $stmt->execute(); $stmt->close(); }
            }
        } catch (Throwable $e) {
            // Keep the legacy behavior and built-in defaults if settings storage is temporarily unavailable.
        }
        return $settings;
    }
}

if (!function_exists('sheba_server_copy_url')) {
    function sheba_server_copy_url(string $template, string $key, string $nid, string $dob): string
    {
        return strtr($template, [
            '{key}' => rawurlencode($key),
            '{nid}' => rawurlencode($nid),
            '{dob}' => rawurlencode($dob),
        ]);
    }
}
