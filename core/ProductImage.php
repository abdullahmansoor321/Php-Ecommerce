<?php

/**
 * Helpers for the products.image JSON column.
 *
 * products.image holds a JSON array of filenames, e.g. ["product-1.jpg"].
 * The FIRST entry is the thumbnail. Files live in a per-product folder named
 * after the immutable product id: public/uploads/products/{id}/{filename}.
 */
class ProductImage
{
    /**
     * Decode the column into a list of filenames.
     * Tolerates NULL, malformed JSON, and legacy bare-string values.
     *
     * @return string[]
     */
    public static function all($json): array
    {
        if (is_array($json)) {
            $decoded = $json;
        } else {
            $decoded = json_decode((string)$json, true);
        }

        if (!is_array($decoded)) {
            // Legacy row still holding a bare filename
            $legacy = trim((string)$json);
            return $legacy === '' ? [] : [basename($legacy)];
        }

        $names = [];
        foreach ($decoded as $entry) {
            if (is_string($entry) && trim($entry) !== '') {
                $names[] = basename(trim($entry));
            }
        }

        return $names;
    }

    /**
     * The thumbnail filename (first entry), or '' when there is none.
     */
    public static function first($json): string
    {
        return self::all($json)[0] ?? '';
    }

    /**
     * Absolute path of a filename inside a product's folder.
     */
    public static function path(int $productId, string $filename): string
    {
        if ($productId <= 0 || $filename === '') {
            return '';
        }

        return UPLOADS_PATH . '/products/' . $productId . '/' . $filename;
    }

    /**
     * Public URL of a filename inside a product's folder.
     */
    public static function url(int $productId, string $filename): string
    {
        if ($productId <= 0 || $filename === '') {
            return '';
        }

        return UPLOADS_URL . '/products/' . $productId . '/' . rawurlencode($filename);
    }

    /**
     * Whether the thumbnail file exists on disk.
     */
    public static function exists(int $productId, $json): bool
    {
        $name = self::first($json);
        $path = self::path($productId, $name);

        return $path !== '' && is_file($path);
    }

    /**
     * Return only gallery filenames that are present in the product folder.
     *
     * @return string[]
     */
    public static function available(int $productId, $json): array
    {
        return array_values(array_filter(self::all($json), function ($filename) use ($productId) {
            return is_file(self::path($productId, $filename));
        }));
    }

    /**
     * Encode a list of filenames for storage.
     *
     * @param string[] $names
     */
    public static function encode(array $names): string
    {
        return (string)json_encode(array_values($names), JSON_UNESCAPED_SLASHES);
    }
}
