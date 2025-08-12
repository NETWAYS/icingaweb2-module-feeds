<?php

namespace Icinga\Module\RSS\Controller;

use Icinga\Module\RSS\RSSReader;
use Icinga\Module\RSS\Storage\Filesystem;
use Icinga\Module\RSS\Web\Table;
use Icinga\Module\RSS\Web\Item;

use ipl\Html\Attributes;
use ipl\Html\HtmlElement;
use ipl\Web\Compat\CompatController;
use ipl\Web\Widget\Link;

use \Exception;
use \DateTime;

class RSSController extends CompatController
{
    protected function displayError(string $msg): void
    {
        $this->addContent(HtmlElement::create(
            'p',
            Attributes::create([
                'tabindex' => -1,
                'class' => 'autofocus error-message',
            ]),
            $msg
        ));
    }

    protected function addTitle(string $title) {
        $this->addControl(
            HtmlElement::create('h1', null, $title)
        );
        $this->setTitle($title);
    }

    protected function renderItems(
        array $items,
        ?int $limit,
        ?DateTime $date,
        bool $compact,
    ): void {
        $index = 1;
        $elements = [];
        foreach ($items as $item) {
            if ($date !== null && $item->date < $date) {
                continue;
            }
            $elements[] = new Item($item, $compact);
            $index++;
            if ($index > $limit) {
                break;
            }
        }

        if (count($elements) == 0) {
            $this->displayError('No news to display');
            return;
        }

        $list = HtmlElement::create(
            'ul',
            Attributes::create(['class' => 'feed-items']),
            $elements
        );

        $this->addContent($list);
    }
}
