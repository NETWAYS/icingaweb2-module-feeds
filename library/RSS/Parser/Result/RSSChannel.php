<?php

namespace Icinga\Module\RSS\Parser\Result;

class RSSChannel
{
    public ?string $title = null;
    public ?string $link = null;
    public ?string $image = null;
    public ?string $description = null;
    public array $items = [];
}
