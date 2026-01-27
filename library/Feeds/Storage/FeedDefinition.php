<?php

namespace Icinga\Module\Feeds\Storage;

use Icinga\Module\Feeds\Parser\FeedType;

/**
 * FeedDefinition represents a feed in the configuration
 */
class FeedDefinition
{
    public function __construct(
        public string   $name,
        public string   $url,
        public string   $description = '',
        public bool     $isVisible = true,
        public FeedType $type = FeedType::Auto,
        public ?int     $pollingInterval = null,
    ) {
    }

    /**
     * toArray returns an array representation of the object
     */
    public function toArray(): array
    {
        return [
            'name' => trim($this->name),
            'url' => trim($this->url),
            'description' => trim($this->description),
            'is_visible' => $this->isVisible,
            'type' => $this->type->display(),
            'polling_interval' => $this->pollingInterval,
        ];
    }

    /**
     * fromArray returns object representation of a feed array
     */
    public static function fromArray(array $data): FeedDefinition
    {
        return new self(
            trim($data['name'] ?? ''),
            trim($data['url'] ?? ''),
            trim($data['description'] ?? ''),
            $data['is_visible'] ?? true,
            FeedType::fromDisplay(trim($data['type'] ?? 'auto')),
            $data['polling_interval'] ?? null,
        );
    }
}
