<?php

namespace Icinga\Module\RSS;

class RSSItem
{
    public ?string $title;
    public ?string $link;
    public ?string $description;
    public array $categories = [];
    public ?string $creator;
    public ?string $image;
}
