<?php

namespace Icinga\Module\Feeds\Parser\Result;

use \DateTime;

/**
 * FeedItem represents a single item in a Feed
 */
class FeedItem
{
    public ?Feed $feed = null;
    public ?string $title = null;
    public ?string $link = null;
    public string $description = '';
    public array $categories = [];
    public ?string $creator = null;
    public ?string $image = null;
    public ?DateTime $date = null;

    public function compareDate(FeedItem $other): int
    {
        $ad = $this->date ?? new DateTime('@0');
        $bd = $other->date ?? new DateTime('@0');

        if ($ad == $bd) {
            return 0;
        }

        return $ad < $bd ? -1 : 1;
    }
}
