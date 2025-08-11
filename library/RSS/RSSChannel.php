<?php

namespace Icinga\Module\RSS;

class RSSChannel
{
    public ?string $title;
    public ?string $link;
    public ?string $image = null;
    public ?string $description;
    public array $items = [];
}
