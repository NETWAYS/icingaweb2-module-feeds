<?php

namespace Icinga\Module\RSS\Storage;

class FeedDefinition
{
    public function __construct(
        public string $name,
        public string $url,
        public ?string $description = null,
    ) {
    }

    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'url' => $this->url,
            'description' => $this->description,
        ];
    }

    public static function fromArray(array $data): FeedDefinition
    {
        return new self(
            $data['name'],
            $data['url'],
            $data['description'] ?? null,
        );
    }
}
