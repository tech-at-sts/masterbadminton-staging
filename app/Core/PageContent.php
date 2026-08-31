<?php

declare(strict_types=1);

namespace App\Core;

final class PageContent
{
    /**
     * @param string $layout shape the extractor recognised the page as -
     *                       '', 'post' or 'archive'. The shared header uses
     *                       it to pick the body class and stylesheet.
     * @param array<string, string>|null $hero copy for the header's hero
     *                       band, when the page has one. Keys: eyebrow,
     *                       plus either title or lead+key, and optionally
     *                       blurb, meta and variant.
     */
    public function __construct(
        public readonly string $title,
        public readonly string $description,
        public readonly string $html,
        public readonly string $layout = '',
        public readonly ?array $hero = null,
    ) {
    }

    /**
     * @return array{title: string, description: string, html: string, layout: string, hero: array<string, string>|null}
     */
    public function toArray(): array
    {
        return [
            'title' => $this->title,
            'description' => $this->description,
            'html' => $this->html,
            'layout' => $this->layout,
            'hero' => $this->hero,
        ];
    }

    /**
     * @param array{title: string, description: string, html: string, layout?: string, hero?: array<string, string>|null} $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            $data['title'],
            $data['description'],
            $data['html'],
            $data['layout'] ?? '',
            $data['hero'] ?? null,
        );
    }
}
