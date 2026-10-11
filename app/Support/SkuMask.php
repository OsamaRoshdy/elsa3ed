<?php

namespace App\Support;

use App\Models\User;

/**
 * Part numbers (SKUs) are shown masked to everyone except admins: "58101-2HA10" → "58••••••A10".
 * Search still matches the full SKU; only the display is masked.
 */
class SkuMask
{
    private const BULLET = '•';

    /**
     * The SKU as the viewer may see it (defaults to the logged-in user).
     */
    public static function display(?string $sku, ?User $viewer = null): ?string
    {
        if (blank($sku)) {
            return null;
        }

        $viewer ??= auth()->user();

        return $viewer?->isAdmin() ? $sku : self::mask($sku);
    }

    /**
     * Masks the SKU wherever it appears in a text (e.g. a Daftra description that repeats the part number).
     */
    public static function inText(?string $text, ?string $sku, ?User $viewer = null): ?string
    {
        if (blank($text) || blank($sku)) {
            return $text;
        }

        return str_ireplace($sku, (string) self::display($sku, $viewer), $text);
    }

    /**
     * Keeps the first 2 and last 3 characters (fewer for short SKUs) and hides the middle.
     */
    public static function mask(string $sku): string
    {
        $length = mb_strlen($sku);

        [$head, $tail] = match (true) {
            $length >= 8 => [2, 3],
            $length >= 5 => [1, 1],
            default => [min(1, $length - 1), 0],
        };

        return mb_substr($sku, 0, $head).str_repeat(self::BULLET, $length - $head - $tail).($tail ? mb_substr($sku, -$tail) : '');
    }
}
