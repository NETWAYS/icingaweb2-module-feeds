<?php

namespace Icinga\Module\Feeds\Storage;

use Icinga\Module\Feeds\Parser\FeedType;

class FeedDefinition
{
    public function __construct(
        public string   $name,
        public string   $url,
        public ?string  $description = null,
        public FeedType $type = FeedType::Auto,
    ) {
    }

    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'url' => $this->url,
            'description' => $this->description,
            'type' => $this->type->display(),
        ];
    }

    public static function fromArray(array $data): FeedDefinition
    {
        return new self(
            $data['name'],
            $data['url'],
            $data['description'] ?? null,
            FeedType::fromDisplay($data['type'] ?? 'auto'),
        );
    }
}
