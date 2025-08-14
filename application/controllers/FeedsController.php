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
        if ($this->hasPermission('RSS/list')) {
            $this->getTabs()->add('list', [
                'label' => $this->translate('List'),
                'url' => 'RSS/feeds/list'
            ]);
        }

        if ($this->hasPermission('RSS/view')) {
            $this->getTabs()->add('view', [
                'label' => $this->translate('View'),
                'url' => 'RSS/feeds'
            ]);
        }

        $this->getTabs()->activate($active);

        if ($disableExtensions) {
            $this->getTabs()->disableLegacyExtensions();
        }
    }

    public function indexAction(): void
    {
        $this->assertPermission('RSS/view');

        $this->addTabs('view', false);

        $controlWrapper = HtmlElement::create('div',
            Attributes::create([
                'class' => 'control-wrapper',
            ]),
            []
        );
        $this->addControl($controlWrapper);

        $this->addTitle($this->translate('Feeds'), $controlWrapper);
        $feeds = $this->params->shift('feeds');
        if ($feeds !== null) {
            $feeds = explode(',', $feeds);
        }
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

        $controlWrapper->add($limitControl);
        $controlWrapper->add($viewModeSwitcher);

        $size = $viewModeSwitcher->getViewMode();
        $this->renderItems(
            $items,
            $limitControl->getLimit(),
            $date,
            $size === 'minimal',
        );

        $this->setAutorefreshInterval(300);
    }

    public function listAction(): void
    {
        $this->assertPermission('RSS/list');

        $this->addTabs('list');
        $this->addTitle($this->translate('List Feeds'));

        if ($this->hasPermission('RSS/modify')) {
            $this->addControl(
                new Link('Add', 'RSS/feed/create', Attributes::create([
                    'title' => $this->translate('Create a new Feed'),
                    'class' => 'icon-plus',
                    'data-base-target' => '_next',
                ]))
            );
        }

        $storage = StorageFactory::getStorage();
        $feeds = $storage->getFeeds();

        $data = [];
        foreach ($feeds as $feed) {
            $feedData = [
                $this->translate('Name') => $feed->name,
                $this->translate('Type') => $this->translate($feed->type->display()),
                '_title' => $feed->description,
            ];

            if ($this->hasPermission('RSS/view')) {
                $feedData['_link'] = "RSS/feed?feed={$feed->name}";
            }

            if ($this->hasPermission('RSS/modify')) {
                $feedData['_actions'] = [
                    $this->translate('Edit') => "RSS/feed/edit?feed={$feed->name}",
                ];
            }

            $data[] = $feedData;
        }

        $table = new Table($data);
        $this->addContent($table);
    }
}
