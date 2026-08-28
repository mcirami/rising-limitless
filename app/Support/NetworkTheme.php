<?php
namespace App\Support;

class NetworkTheme
{
    // Preserve the eleven-slot legacy format; no database migration is needed.
    public const DEFAULTS = ['FFFFFF','FFFFFF','171B27','E8533D','FF634B','FF634B','EEF0F7','FFFFFF','181D29','777F8E','FF634B'];
    public const LABELS = ['Legacy header background','Legacy button / menu text','Sidebar background','Button hover','Links and focus accent','Navigation accent','Light mode page background','Light mode card background','Light mode text','Light mode secondary text','Primary button background'];

    public static function colors($value): array
    {
        $values = is_array($value) ? $value : explode(';', (string) $value);
        return array_map(function ($fallback, $i) use ($values) {
            $color = strtoupper(ltrim(trim((string) ($values[$i] ?? '')), '#'));
            return preg_match('/^[A-F0-9]{6}$/D', $color) ? $color : $fallback;
        }, self::DEFAULTS, array_keys(self::DEFAULTS));
    }

    public static function css($value): string
    {
        $c = self::colors($value);
        $ink = self::ink($c[10]);
        $hoverInk = self::ink($c[3]);
        return ':root:root{--rl-bg:#'.$c[6].';--rl-surface:#'.$c[7].';--rl-text:#'.$c[8].';--rl-muted:#'.$c[9].';--rl-sidebar:#'.$c[2].';--rl-accent:#'.$c[4].';--rl-brand-accent:#'.$c[5].';--rl-button:#'.$c[10].';--rl-button-hover:#'.$c[3].';--rl-button-ink:'.$ink.';--rl-button-hover-ink:'.$hoverInk.';--rl-accent-soft:color-mix(in srgb,var(--rl-accent) 10%,var(--rl-surface));}'
            . ':root:root[data-theme=dark]{--rl-bg:#10141d;--rl-surface:#1b202d;--rl-text:#eef1f7;--rl-muted:#a0a9bb;--rl-sidebar:color-mix(in srgb,#'.$c[2].' 25%,#10141d);--rl-accent:color-mix(in srgb,#'.$c[4].' 45%,white);--rl-brand-accent:color-mix(in srgb,#'.$c[5].' 45%,white);}';
    }
    private static function ink(string $color): string
    {
        $rgb = array_map(function ($hex) {
            $v = hexdec($hex) / 255;
            return $v <= .04045 ? $v / 12.92 : pow(($v + .055) / 1.055, 2.4);
        }, str_split($color, 2));
        $luminance = .2126 * $rgb[0] + .7152 * $rgb[1] + .0722 * $rgb[2];
        return ($luminance + .05) / .05 > 1.05 / ($luminance + .05) ? '#000000' : '#ffffff';
    }

}
