<?php

namespace Icinga\Module\RSS\Controllers;

use Icinga\Module\RSS\FeedReader;
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
    protected function addTabs(
        string $active,
        bool $disableExtensions = true,
    ): void {
        $this->getTabs()
            ->add('view', [
                'label'     => $this->translate('View'),
                'url'       => 'RSS/feeds'
            ])
            ->add('list', [
                'label'     => $this->translate('List'),
                'url'       => 'RSS/feeds/list'
            ])
            ->activate($active);

        if ($disableExtensions) {
            $this->getTabs()->disableLegacyExtensions();
        }
    }

    public function indexAction(): void
    {
        $this->addTabs('view', false);
        $this->addTitle($this->translate('Feeds'));
        $feeds = $this->params->shift('feeds');
        if ($feeds !== null) {
            $feeds = explode(',', $feeds);
        }
        $limit = $this->getLimitParam();
        $compact = $this->getViewParam() === 'minimal';
        $date = $this->getDateParam();
        if ($date !== null) {
            try {
                $date = new DateTime($date);
            } catch (Exception $ex) {
                $this->displayError('Invalid date');
                return;
            }
        }

        $storage = new Filesystem();
        $items = [];
        $feedsCounter = 0;
        foreach ($storage->getFeeds() as $feed) {
            if ($feeds !== null && !in_array($feed->name, $feeds)) {
                continue;
            }

            $feedsCounter++;
            try {
                $reader = new FeedReader($feed->url, $feed->feedtype);
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
        $this->addTabs('list');
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
                'Type' => $this->translate($feed->feedtype->display()),
                '_link' => "RSS/feed/edit?feed={$feed->name}",
                '_title' => $feed->description,
            ];
        }

        $table = new Table($data);
        $this->addContent($table);
    }
}
