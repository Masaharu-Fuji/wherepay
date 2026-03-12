<?php

if (! function_exists('cn')) {
    /**
     * Tailwind などのユーティリティクラスを結合するヘルパー.
     *
     * @param  mixed  ...$args
     */
    function cn(...$args): string
    {
        $parts = [];

        foreach ($args as $value) {
            if (! $value) {
                continue;
            }

            if (is_string($value)) {
                $parts = array_merge($parts, preg_split('/\s+/', $value, -1, PREG_SPLIT_NO_EMPTY) ?: []);

                continue;
            }

            if (is_array($value)) {
                $parts = array_merge($parts, array_values($value));

                continue;
            }

            if (is_object($value)) {
                foreach (get_object_vars($value) as $class => $enabled) {
                    if ($enabled) {
                        $parts[] = $class;
                    }
                }
            }
        }

        $parts = array_filter($parts, static fn ($class) => is_string($class) && $class !== '');

        return implode(' ', $parts);
    }
}
