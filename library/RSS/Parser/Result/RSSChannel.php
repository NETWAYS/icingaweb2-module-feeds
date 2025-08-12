<?php

namespace Icinga\Module\RSS\Parser\Result;

class RSSChannel
{
    public ?string $title = null;
    public ?string $link = null;
    public ?string $image = null;
    public ?string $description = null;
    protected array $items = [];

    public function addItem(RSSitem $item): void
    {
        $this->items[] = $item;
    }

    public function getItems(): array
    {
        return $this->items;
    }
}
