<?php

namespace Icinga\Module\RSS\Controller;

use Icinga\Module\RSS\Web\Item;

use ipl\Html\Attributes;
use ipl\Html\HtmlElement;
use ipl\Web\Compat\CompatController;

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

    protected function getLimitParam(): int
    {
        $limit = $this->params->shift('limit') ?? 200;

        if ($limit <= 0) {
            $limit = 1;
        }

        if ($limit > 5000) {
            $limit = 5000;
        }

        return $limit;
    }

    protected function getViewParam(): string
    {
        return $this->params->shift('view') ?? 'minimal';
    }

    protected function getDateParam(): DateTime|bool|null
    {
        $date = $this->params->shift('date');
        if ($date !== null) {
            try {
                $date = new DateTime($date);
                return $date;
            } catch (Exception $ex) {
                $this->displayError('Invalid date');
                return false;
            }
        }
        return null;
    }
}
