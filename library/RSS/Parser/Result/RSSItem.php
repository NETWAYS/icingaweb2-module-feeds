<?php

namespace Icinga\Module\RSS\Parser\Result;

use \DateTime;

class RSSItem
{
    public ?RSSChannel $channel = null;
    public ?string $title = null;
    public ?string $link = null;
    public ?string $description = null;
    public array $categories = [];
    public ?string $creator = null;
    public ?string $image = null;
    public ?DateTime $date = null;
}
