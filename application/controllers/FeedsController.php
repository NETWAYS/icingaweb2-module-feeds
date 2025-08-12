<?php

namespace Icinga\Module\RSS\Controllers;

use Icinga\Module\RSS\RSSReader;
use Icinga\Module\RSS\Storage\Filesystem;
use Icinga\Module\RSS\Web\Table;
use Icinga\Module\RSS\Web\Item;

use ipl\Html\Attributes;
use ipl\Html\HtmlElement;
use ipl\Web\Compat\CompatController;
use ipl\Web\Widget\Link;

use \Exception;

class FeedsController extends CompatController
{
    public function indexAction(): void
    {
        $title = $this->translate('Feeds');
        $this->addControl(
            HtmlElement::create('h1', null, $title)
        );
        $this->setTitle($title);

        $storage = new Filesystem();

        $limit = $this->params->shift('limit') ?? 50;
        $compact = ($this->params->shift('view') ?? 'minimal') === 'minimal';

        $items = [];
        foreach ($storage->getFeeds() as $feed) {
            try {
                $reader = new RSSReader($feed->url);
                $data = $reader->fetch();
            } catch (Exception $ex) {
                // TODO: Figure out a way to display the error
                continue;
            }

            $items = array_merge($items, $data->getItems());
        }

        // Sort items
        usort($items, function($a, $b) {
            return -($a->compareDate($b));
        });

        $index = 0;
        $elements = [];
        foreach ($items as $item) {
            $elements[] = new Item($item, $compact);
            $index++;
            if ($index > $limit) {
                break;
            }
        }

        $list = HtmlElement::create(
            'ul',
            Attributes::create(['class' => 'feed-items']),
            $elements
        );

        $this->addContent($list);

        $this->setAutorefreshInterval(300);
    }

    public function listAction(): void
    {
        $title = $this->translate('List Feeds');
        $this->addControl(
            HtmlElement::create('h1', null, $title)
        );
        $this->setTitle($title);

        $this->addControl(
            new Link('Add', 'RSS/feed/create', Attributes::create([
                'title' => $this->translate('Create a new Feed'),
                'class' => 'icon-plus',
                'data-base-target' => '_next',
            ]))
        );

        $storage = new Filesystem();
        $feeds = $storage->getFeeds();

        $data = [];
        foreach ($feeds as $feed) {
            $data[] = [
                'Name' => $feed->name,
                'Link' => $feed->url,
                '_link' => "RSS/feed/edit?feed={$feed->name}",
            ];
        }

        $table = new Table($data);
        $this->addContent($table);
    }
}
