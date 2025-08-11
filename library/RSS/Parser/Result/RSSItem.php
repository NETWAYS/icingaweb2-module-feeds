<?php

namespace Icinga\Module\RSS\Parser\Result;

class RSSItem
{
    public ?string $title = null;
    public ?string $link = null;
    public ?string $description = null;
    public array $categories = [];
    public ?string $creator = null;
    public ?string $image = null;
}
