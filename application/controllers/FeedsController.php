<?php

namespace Icinga\Module\RSS\Controllers;

use Icinga\Module\RSS\FeedReader;
use Icinga\Module\RSS\Storage\StorageFactory;
use Icinga\Module\RSS\Web\Table;
use Icinga\Module\RSS\Web\Item;
use Icinga\Module\RSS\Controller\RSSController;

use Icinga\Application\Benchmark;
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
        $date = $this->getDateParam();
        if ($date !== null) {
            return;
        }

        $storage = StorageFactory::getStorage();
        $items = [];
        $feedsCounter = 0;

        Benchmark::measure('Started fetching feeds');

        foreach ($storage->getFeeds() as $feed) {
            if ($feeds !== null && !in_array($feed->name, $feeds)) {
                continue;
            }

            $feedsCounter++;
            try {
                $reader = new FeedReader($feed->url, $feed->type, $feed->trusted);
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

        Benchmark::measure('Started merging feeds');

        // Sort items
        usort($items, function($a, $b) {
            return -($a->compareDate($b));
        });

        Benchmark::measure('Started rendering feed');

        $limitControl = $this->createLimitControl();
        $viewModeSwitcher = $this->createViewModeSwitcher($limitControl);

        $this->addControl($limitControl);
        $this->addControl($viewModeSwitcher);

        $size = $viewModeSwitcher->getViewMode();
        $this->renderItems(
            $items,
            $limit,
            $date,
            $size === 'minimal',
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

        $storage = StorageFactory::getStorage();
        $feeds = $storage->getFeeds();

        $data = [];
        foreach ($feeds as $feed) {
            $data[] = [
                'Name' => $feed->name,
                'Link' => $feed->url,
                'Type' => $this->translate($feed->type->display()),
                '_link' => "RSS/feed/edit?feed={$feed->name}",
                '_title' => $feed->description,
            ];
        }

        $table = new Table($data);
        $this->addContent($table);
    }
}
