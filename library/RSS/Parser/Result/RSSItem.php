<?php

namespace Icinga\Module\RSS\Parser\Result;

use \DateTime;

class RSSItem
{
    public ?Feed $feed = null;
    public ?string $title = null;
    public ?string $link = null;
    public ?string $description = null;
    public array $categories = [];
    public ?string $creator = null;
    public ?string $image = null;
    public ?DateTime $date = null;

    public function compareDate(RSSItem $other): int {
        $ad = $this->date ?? new DateTime('NOW');
        $bd = $other->date ?? new DateTime('NOW');

        if ($ad == $bd) {
            return 0;
        }

        return $ad < $bd ? -1 : 1;
    }
}
