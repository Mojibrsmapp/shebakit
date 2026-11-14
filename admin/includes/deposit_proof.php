<?php
if (!function_exists('sheba_deposit_proof_url')) {
    function sheba_deposit_proof_url($storedPath): string
    {
        $path = trim((string)$storedPath);
        if ($path === '') { return ''; }
        if (preg_match('~^(https?:)?//~i', $path) || strpos($path, '/') === 0) { return $path; }
        return '../' . ltrim($path, './');
    }
}
