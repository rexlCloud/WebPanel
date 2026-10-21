<?php

namespace App\Support;

/**
 * Renders a Minecraft JSON chat component (as the cloud returns it) to HTML for previews. All text is escaped.
 */
class ChatComponent
{
    private const COLORS = [
        'black' => '#000000', 'dark_blue' => '#0000aa', 'dark_green' => '#00aa00', 'dark_aqua' => '#00aaaa',
        'dark_red' => '#aa0000', 'dark_purple' => '#aa00aa', 'gold' => '#ffaa00', 'gray' => '#aaaaaa',
        'dark_gray' => '#555555', 'blue' => '#5555ff', 'green' => '#55ff55', 'aqua' => '#55ffff',
        'red' => '#ff5555', 'light_purple' => '#ff55ff', 'yellow' => '#ffff55', 'white' => '#ffffff',
    ];

    public static function toHtml(mixed $component, array $inherited = []): string
    {
        if (is_string($component)) {
            return self::span($component, $inherited);
        }
        if (array_is_list($component ?? [])) {
            return implode('', array_map(fn ($part) => self::toHtml($part, $inherited), $component ?? []));
        }

        $style = $inherited;
        foreach (['bold', 'italic', 'underlined', 'strikethrough', 'obfuscated'] as $decoration) {
            if (array_key_exists($decoration, $component)) {
                $style[$decoration] = (bool) $component[$decoration];
            }
        }
        if (isset($component['color'])) {
            $style['color'] = self::COLORS[$component['color']] ?? (preg_match('/^#[0-9a-fA-F]{6}$/', $component['color']) ? $component['color'] : null);
        }

        $html = self::span((string) ($component['text'] ?? ''), $style);
        foreach ($component['extra'] ?? [] as $child) {
            $html .= self::toHtml($child, $style);
        }

        return $html;
    }

    private static function span(string $text, array $style): string
    {
        if ($text === '') {
            return '';
        }
        $css = [];
        if (! empty($style['color'])) {
            $css[] = 'color:'.$style['color'];
        }
        if (! empty($style['bold'])) {
            $css[] = 'font-weight:700';
        }
        if (! empty($style['italic'])) {
            $css[] = 'font-style:italic';
        }
        $lines = [];
        if (! empty($style['underlined'])) {
            $lines[] = 'underline';
        }
        if (! empty($style['strikethrough'])) {
            $lines[] = 'line-through';
        }
        if ($lines) {
            $css[] = 'text-decoration:'.implode(' ', $lines);
        }
        $escaped = nl2br(e($text), false);

        return $css ? '<span style="'.implode(';', $css).'">'.$escaped.'</span>' : $escaped;
    }
}
