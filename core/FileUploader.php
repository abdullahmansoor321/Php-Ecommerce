<?php

class FileUploader
{
    /** Maximum number of images allowed per product. */
    public const MAX_IMAGES = 5;

    /** Per-file size ceiling in bytes. */
    public const MAX_BYTES = 2 * 1024 * 1024;

    /** Extension keyed by detected MIME, so the stored extension always
     *  matches the real content. The browser-supplied name is not trusted. */
    private const EXTENSIONS = [
        'image/jpeg' => 'jpg',
        'image/png'  => 'png',
        'image/webp' => 'webp',
        'image/gif'  => 'gif',
    ];

    /**
     * Upload a single image.
     *
     * @param array $file $_FILES['input_name']
     * @param string $destinationFolder Target directory (absolute path)
     * @return string|null The generated filename, or null on failure.
     */
    public static function uploadImage(array $file, string $destinationFolder): ?string
    {
        if (!isset($file['tmp_name']) || ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            return null;
        }

        if ($file['size'] > self::MAX_BYTES) {
            return null;
        }

        $mime = mime_content_type($file['tmp_name']);
        if (!isset(self::EXTENSIONS[$mime])) {
            return null;
        }

        if (!is_dir($destinationFolder)) {
            mkdir($destinationFolder, 0755, true);
        }

        $uniqueName = bin2hex(random_bytes(10)) . '.' . self::EXTENSIONS[$mime];
        $targetPath = rtrim($destinationFolder, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $uniqueName;

        if (move_uploaded_file($file['tmp_name'], $targetPath)) {
            return $uniqueName;
        }

        return null;
    }

    /**
     * Upload several images from one <input type="file" multiple>.
     *
     * Rejected files are skipped, not fatal, and the caller is told how many
     * were dropped so nothing fails silently.
     *
     * @param array $files The $_FILES['images'] array
     * @param string $destinationFolder Target directory (absolute path)
     * @return array{0: string[], 1: int} Stored filenames and rejected count
     */
    public static function uploadImages(array $files, string $destinationFolder): array
    {
        [$stored, $rejected] = self::uploadImagesIndexed($files, $destinationFolder);

        return [array_values($stored), $rejected];
    }

    /**
     * Upload several images while retaining each file's original input index.
     *
     * @param array $files The $_FILES['images'] array
     * @param string $destinationFolder Target directory (absolute path)
     * @return array{0: array<int, string>, 1: int} Stored filenames keyed by input index and rejected count
     */
    public static function uploadImagesIndexed(array $files, string $destinationFolder): array
    {
        if (!isset($files['name']) || !is_array($files['name'])) {
            $one = self::uploadImage($files, $destinationFolder);
            return [$one !== null ? [0 => $one] : [], $one !== null ? 0 : 1];
        }

        $stored = [];
        $rejected = 0;
        $total = count($files['name']);

        for ($i = 0; $i < $total; $i++) {
            // Skip empty slots the browser leaves when the user deselects
            if (($files['error'][$i] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
                continue;
            }

            $name = self::uploadImage([
                'tmp_name' => $files['tmp_name'][$i],
                'error'    => $files['error'][$i],
                'size'     => $files['size'][$i],
            ], $destinationFolder);

            if ($name !== null) {
                $stored[$i] = $name;
            } else {
                $rejected++;
            }
        }

        return [$stored, $rejected];
    }
}

