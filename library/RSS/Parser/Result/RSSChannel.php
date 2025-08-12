<?php

namespace Icinga\Module\RSS\Parser\Result;

class RSSChannel
{
    public ?string $title = null;
    public ?string $link = null;
    public ?string $image = null;
    public ?string $description = null;
    protected array $items = [];

    protected bool $sorted = true;

    public function addItem(RSSitem $item): void
    {
        $this->items[] = $item;
        $this->sorted = false;
    }

    public function getItems(): array
    {
        if (!$this->sorted) {
            usort($this->items, function($a, $b) {
                $ad = $a->date;
                $bd = $b->date;

                if ($ad == $bd) {
                    return 0;
                }

                return $ad < $bd ? 1 : -1;
            });
            $this->sorted = true;
        }
        return $this->items;
    }
}
