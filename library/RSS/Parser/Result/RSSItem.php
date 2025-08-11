<?php

namespace Icinga\Module\RSS\Parser\Result;

class RSSItem
{
    public ?string $title;
    public ?string $link;
    public ?string $description;
    public array $categories = [];
    public ?string $creator;
    public ?string $image;
}
