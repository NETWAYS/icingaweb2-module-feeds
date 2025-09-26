<?php

namespace Icinga\Module\Feeds\Controllers;

use Icinga\Module\Feeds\FeedReader;
use Icinga\Module\Feeds\Storage\StorageFactory;
use Icinga\Module\Feeds\Web\FeedsTable;
use Icinga\Module\Feeds\Controller\BaseController;

use Icinga\Application\Benchmark;
use ipl\Html\Attributes;
use ipl\Html\HtmlElement;
use ipl\Web\Widget\Link;

use Exception;

class FeedsController extends BaseController
{
    protected function addTabs(
        string $active,
        bool $disableExtensions = true,
    ): void {
        if ($this->hasPermission('feeds/list')) {
            $this->getTabs()->add('list', [
                'label' => $this->translate('List'),
                'url' => 'feeds/feeds/list'
            ]);
        }

        if ($this->hasPermission('feeds/view')) {
            $this->getTabs()->add('view', [
                'label' => $this->translate('View'),
                'url' => 'feeds/feeds'
            ]);
        }

        $this->getTabs()->activate($active);

        if ($disableExtensions) {
            $this->getTabs()->disableLegacyExtensions();
        }
    }

    public function indexAction(): void
    {
        $this->assertPermission('feeds/view');

        $this->getTabs()
            ->add('view', [
                'label' => $this->translate('View'),
                'url' => 'feeds/feeds'
            ])
            ->activate('view');

        $controlWrapper = HtmlElement::create(
            'div',
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

        $failed = [];

        foreach ($storage->getFeeds() as $feed) {
            if ($feeds !== null && !in_array($feed->name, $feeds)) {
                continue;
            }

            try {
                $reader = new FeedReader($feed->url, $this->Config(), $feed->type);
                $data = $reader->fetch('feed-' . $feed->name);
                $feedsCounter++;
            } catch (Exception) {
                $failed[] = $feed->name;
                continue;
            }

            $items = array_merge($items, $data->getItems());
        }

        $this->renderFailedFeedNotification($failed);

        if ($feedsCounter == 0) {
            $this->displayError($this->translate('No feeds to display'));
            return;
        }

        Benchmark::measure('Started merging feeds');

        // Sort items
        usort($items, function ($a, $b) {
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

    protected function renderFailedFeedNotification(array $failed): void
    {
        if (count($failed) < 1) {
            return;
        }

        if (count($failed) === 1) {
            $text = sprintf($this->translate('Failed to load feed: %s'), $failed[0]);
        } elseif (count($failed) <= 3) {
            $failedNames = join(', ', $failed);
            $text = sprintf($this->translate('Failed to load %d feeds: %s'), count($failed), $failedNames);
        } else {
            // We cannot show an endless message, thus only three error
            $failedNames = join(', ', array_merge(array_slice($failed, 0, 3), ['...']));
            $text = sprintf($this->translate('Failed to load %d feeds: %s'), count($failed), $failedNames);
        }

        $this->addContent(HtmlElement::create(
            'span',
            Attributes::create(['class' => 'feed-list-error']),
            $text
        ));
    }

    public function listAction(): void
    {
        $this->assertPermission('feeds/list');

        $this->getTabs()
            ->add('list', [
                'label' => $this->translate('List'),
                'url' => 'feeds/list'
            ])
            ->activate('list');

        $this->addTitle($this->translate('Feeds'));

        if ($this->hasPermission('feeds/modify')) {
            $this->addControl(
                new Link($this->translate('Add'), 'feeds/feed/create', Attributes::create([
                    'title' => $this->translate('Create a new feed'),
                    'class' => 'icon-plus action-link',
                    'data-base-target' => '_next',
                ]))
            );
        }

        $storage = StorageFactory::getStorage();
        $feeds = $storage->getFeeds();

        $table = new FeedsTable($feeds);
        $this->addContent($table);
    }
}
