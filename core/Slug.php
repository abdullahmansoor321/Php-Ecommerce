<?php

class Slug
{
    /**
     * Builds a URL-safe slug from a name.
     *
     * @param string $name
     * @return string
     */
    public static function generate(string $name): string
    {
        $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $name), '-'));

        // Collapse repeated hyphens produced by punctuation runs
        return trim(preg_replace('/-+/', '-', $slug), '-');
    }

    /**
     * Generates a slug that does not collide with an existing row.
     * Appends -2, -3, ... until the slug is free.
     *
     * @param string $name
     * @param callable $existsFn Receives the candidate slug, returns bool
     * @return string
     */
    public static function unique(string $name, callable $existsFn): string
    {
        $base = self::generate($name);

        if ($base === '') {
            $base = 'item';
        }

        $slug = $base;
        $suffix = 2;

        while ($existsFn($slug)) {
            $slug = $base . '-' . $suffix;
            $suffix++;
        }

        return $slug;
    }
}
