<?php

namespace Icinga\Module\RSS\Controllers;

use Icinga\Module\RSS\RSSReader;
use Icinga\Module\RSS\Storage\Filesystem;
use Icinga\Module\RSS\Web\Table;
use Icinga\Module\RSS\Web\Item;
use Icinga\Module\RSS\Controller\RSSController;

use ipl\Html\Attributes;
use ipl\Html\HtmlElement;
use ipl\Web\Widget\Link;

use \Exception;
use \DateTime;

class FeedsController extends RSSController
{
    public function indexAction(): void
    {
        $this->addTitle($this->translate('Feeds'));

        $storage = new Filesystem();

        $feeds = $this->params->shift('feeds');
        if ($feeds !== null) {
            $feeds = explode(',', $feeds);
        }
        $limit = $this->params->shift('limit') ?? 50;
        $compact = ($this->params->shift('view') ?? 'minimal') === 'minimal';
        $date = $this->params->shift('date');
        if ($date !== null) {
            try {
                $date = new DateTime($date);
            } catch (Exception $ex) {
                $this->displayError('Invalid date');
                return;
            }
        }

        $items = [];
        $feedsCounter = 0;
        foreach ($storage->getFeeds() as $feed) {
            if ($feeds !== null && !in_array($feed->name, $feeds)) {
                continue;
            }

            $feedsCounter++;
            try {
                $reader = new RSSReader($feed->url);
                $data = $reader->fetch();
            } catch (Exception $ex) {
                // TODO: Figure out a way to display the error
                continue;
            }

            $items = array_merge($items, $data->getItems());
        }

        if ($feedsCounter == 0) {
            $this->displayError('No feeds to display');
            return;
        }

        // Sort items
        usort($items, function($a, $b) {
            return -($a->compareDate($b));
        });

        $this->renderItems(
            $items,
            $limit,
            $date,
            $compact,
        );

        $this->setAutorefreshInterval(300);
    }

    public function listAction(): void
    {
        $this->addTitle($this->translate('List Feeds'));

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
