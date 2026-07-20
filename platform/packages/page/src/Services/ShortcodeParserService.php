<?php

namespace Botble\Page\Services;

class ShortcodeParserService
{
    protected array $shortcodes = [];

    protected int $idCounter = 0;

    public function parse(string $content): array
    {
        $this->shortcodes = [];
        $this->idCounter = 0;

        $pattern = $this->getShortcodeRegex();

        if (empty($content)) {
            return [];
        }

        preg_match_all($pattern, $content, $matches, PREG_SET_ORDER | PREG_OFFSET_CAPTURE);

        foreach ($matches as $match) {
            $shortcode = $this->parseShortcode($match);
            if ($shortcode) {
                $this->shortcodes[] = $shortcode;
            }
        }

        return $this->shortcodes;
    }

    protected function parseShortcode(array $match): ?array
    {
        $fullMatch = $match[0][0] ?? '';
        $position = $match[0][1] ?? 0;
        $name = $match[2][0] ?? '';
        $attributesString = $match[3][0] ?? '';
        $content = $match[5][0] ?? '';

        if (empty($name)) {
            return null;
        }

        $attributes = $this->parseAttributes($attributesString);

        $id = $attributes['data-vb-id'] ?? null;
        if (empty($id)) {
            $id = 'shortcode_' . time() . '_' . $this->idCounter++;
        }

        unset($attributes['data-vb-id']);

        return [
            'id' => $id,
            'name' => $name,
            'attributes' => $attributes,
            'content' => $content,
            'position' => $position,
            'raw' => $fullMatch,
            'isSelfClosing' => empty($match[5][0] ?? ''),
        ];
    }

    protected function parseAttributes(string $attributesString): array
    {
        $attributes = [];

        if (empty($attributesString)) {
            return $attributes;
        }

        $pattern = '/(\w+)\s*=\s*(?:"([^"]*)"|\'([^\']*)\'|([^\s]+))/';

        preg_match_all($pattern, $attributesString, $matches, PREG_SET_ORDER);

        foreach ($matches as $match) {
            $key = $match[1];
            $value = $match[2] ?? $match[3] ?? $match[4] ?? '';
            $attributes[$key] = $value;
        }

        return $attributes;
    }

    protected function getShortcodeRegex(): string
    {
        return '/\[(\[?)([\w-]+)(?![\w-])([^\]\/]*(?:\/(?!\])[^\]\/]*)*?)(?:(\/)\]|\](?:([^\[]*(?:\[(?!\/\2\])[^\[]*)*)(\[\/\2\]))?)(\]?)/';
    }

    public function serialize(array $shortcodes): string
    {
        $content = '';

        foreach ($shortcodes as $shortcode) {
            $name = $shortcode['name'] ?? '';
            $attributes = $shortcode['attributes'] ?? [];
            $innerContent = $shortcode['content'] ?? '';
            $id = $shortcode['id'] ?? '';

            if (empty($name)) {
                continue;
            }

            $shortcodeString = '[' . $name;

            if (! empty($id)) {
                $shortcodeString .= ' data-vb-id="' . $id . '"';
            }

            foreach ($attributes as $key => $value) {
                if (is_array($value)) {
                    $value = json_encode($value);
                }
                $escapedValue = $this->escapeAttribute($value);
                $shortcodeString .= ' ' . $key . '="' . $escapedValue . '"';
            }

            $shortcodeString .= ']';
            if (! empty($innerContent)) {
                $shortcodeString .= $innerContent;
            }
            $shortcodeString .= '[/' . $name . ']';

            $content .= $shortcodeString . "\n\n";
        }

        return trim($content);
    }

    protected function escapeAttribute(mixed $value): string
    {
        if (! is_string($value)) {
            $value = (string) $value;
        }

        return str_replace(['"', '\\'], ['\"', '\\\\'], $value);
    }

    public function getShortcodes(): array
    {
        return $this->shortcodes;
    }
}
