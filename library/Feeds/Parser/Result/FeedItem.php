<?php

namespace Icinga\Module\Feeds\Parser\Result;

use \DateTime;

class FeedItem
{
    public ?Feed $feed = null;
    public ?string $title = null;
    public ?string $link = null;
    public ?string $description = null;
    public array $categories = [];
    public ?string $creator = null;
    public ?string $image = null;
    public ?DateTime $date = null;

    public function compareDate(FeedItem $other): int
    {
        $ad = $this->date ?? new DateTime('NOW');
        $bd = $other->date ?? new DateTime('NOW');

        if ($ad == $bd) {
            return 0;
        }

        return $ad < $bd ? -1 : 1;
    }
}
