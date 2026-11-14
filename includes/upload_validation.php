<?php
/** Validate an uploaded file by extension and server-detected MIME type. */
if (!function_exists('shebakit_valid_upload')) {
    function shebakit_valid_upload(array $file, int $maxBytes = 20971520): bool
    {
        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK
            || empty($file['tmp_name'])
            || !is_uploaded_file($file['tmp_name'])
            || (int)($file['size'] ?? 0) < 1
            || (int)$file['size'] > $maxBytes) {
            return false;
        }
        $allowed = [
            'pdf' => ['application/pdf'],
            'jpg' => ['image/jpeg'],
            'jpeg' => ['image/jpeg'],
            'png' => ['image/png'],
            'webp' => ['image/webp'],
            'txt' => ['text/plain'],
        ];
        $extension = strtolower(pathinfo((string)($file['name'] ?? ''), PATHINFO_EXTENSION));
        if (!isset($allowed[$extension]) || !class_exists('finfo')) {
            return false;
        }
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->file($file['tmp_name']);
        return in_array($mime, $allowed[$extension], true);
    }
}
