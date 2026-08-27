<?php

declare(strict_types=1);

namespace App\Core;

final class PageContent
{
    public function __construct(
        public readonly string $title,
        public readonly string $description,
        public readonly string $html,
    ) {
    }

    /**
     * @return array{title: string, description: string, html: string}
     */
    public function toArray(): array
    {
        return [
            'title' => $this->title,
            'description' => $this->description,
            'html' => $this->html,
        ];
    }

    /**
     * @param array{title: string, description: string, html: string} $data
     */
    public static function fromArray(array $data): self
    {
        return new self($data['title'], $data['description'], $data['html']);
    }
}
