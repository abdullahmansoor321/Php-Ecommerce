<?php

class FileUploader
{
    /**
     * Upload an image file securely with MIME validation and size constraint.
     * 
     * @param array $file $_FILES['input_name']
     * @param string $destinationFolder Target directory (absolute path)
     * @return string|null Returns the unique filename on success, or null on failure.
     */
    public static function uploadImage(array $file, string $destinationFolder): ?string
    {
        if (!isset($file['tmp_name']) || $file['error'] !== UPLOAD_ERR_OK) {
            return null;
        }

        // Enforce 2MB max file size
        $maxSize = 2 * 1024 * 1024;
        if ($file['size'] > $maxSize) {
            return null;
        }

        // Validate MIME type securely
        $allowedMimes = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
        $mime = mime_content_type($file['tmp_name']);

        if (!in_array($mime, $allowedMimes)) {
            return null;
        }

        // Generate sanitized unique filename
        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $uniqueName = bin2hex(random_bytes(10)) . '.' . strtolower($extension);

        // Ensure destination directory exists
        if (!is_dir($destinationFolder)) {
            mkdir($destinationFolder, 0755, true);
        }

        $targetPath = rtrim($destinationFolder, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $uniqueName;

        if (move_uploaded_file($file['tmp_name'], $targetPath)) {
            return $uniqueName;
        }

        return null;
    }
}
