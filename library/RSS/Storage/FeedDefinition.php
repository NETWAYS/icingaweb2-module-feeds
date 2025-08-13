<?php

namespace Icinga\Module\RSS\Storage;

use Icinga\Module\RSS\Parser\FeedType;

class FeedDefinition
{
    public function __construct(
        public string $name,
        public string $url,
        public ?string $description = null,
        public FeedType $feedtype = FeedType::Auto,
    ) {
    }

    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'url' => $this->url,
            'description' => $this->description,
            'feedtype' => $this->feedtype->display(),
        ];
    }

    public static function fromArray(array $data): FeedDefinition
    {
        return new self(
            $data['name'],
            $data['url'],
            $data['description'] ?? null,
            FeedType::fromDisplay($data['feedtype'] ?? 'auto'),
        );
    }
}
