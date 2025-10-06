<?php

namespace Icinga\Module\Feeds\Controllers;

use Icinga\Module\Feeds\Controller\BaseController;
use Icinga\Module\Feeds\FeedReader;
use Icinga\Module\Feeds\Forms\FeedForm;
use Icinga\Module\Feeds\Parser\FeedType;
use Icinga\Module\Feeds\Storage\StorageFactory;

use Icinga\Application\Benchmark;
use Icinga\Web\Notification;

use ipl\Html\Attributes;
use ipl\Html\Form;
use ipl\Html\HtmlElement;

use Exception;

class FeedController extends BaseController
{
    public function indexAction(): void
    {
        $this->assertPermission('feeds/view');

        $this->getTabs()
            ->add('view', [
                'label' => $this->translate('View'),
                'url' => $this->getRequest()->getUrl()
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

        $this->addTitle($this->translate('Feed'), $controlWrapper);

        [$url, $type, $name, $isActive] = $this->getFeedInfo();

        if ($url === null) {
            $this->displayError($this->translate('No such feed configured'));
            return;
        }

        $date = $this->getDateParam();

        if ($date === false) {
            $this->displayError($this->translate('Invalid date'));
            return;
        }

        Benchmark::measure('Started fetching feed');

        try {
            $reader = new FeedReader($url, $this->Config(), $type);
            $data = $reader->fetch($name);
        } catch (Exception $ex) {
            $this->displayError($ex->getMessage());
            return;
        }

        Benchmark::measure('Started rendering feed');

        $limitControl = $this->createLimitControl();
        $viewModeSwitcher = $this->createViewModeSwitcher($limitControl);

        $controlWrapper->add($limitControl);
        $controlWrapper->add($viewModeSwitcher);

        $size = $viewModeSwitcher->getViewMode();
        $this->renderItems(
            $data->getItems(),
            $limitControl->getLimit(),
            $date,
            $size === 'minimal',
        );

        $this->setAutorefreshInterval(300);
    }

    /**
     * getFeedInfo returns information feed from the storage
     */
    protected function getFeedInfo(): array
    {
        $name = $this->params->shift('feed');

        if ($name !== null && $name !== '') {
            $storage = StorageFactory::getStorage();
            $feed = $storage->getFeedByName($name);

            if ($feed === null) {
                return [null, null, null, null];
            }

            return [$feed->url, $feed->type, 'feed-' . $feed->name, $feed->isActive];
        }

        $url = $this->params->shift('url');

        if ($url === null or $url === '') {
            return [null, null, null, null];
        }

        $this->assertPermission('feeds/view/arbitrary');

        $type = $this->params->shift('type') ?? 'auto';
        $name = 'url-' . sha1($url . ':' . $type);

        return [$url, FeedType::fromDisplay($type), $name, true];
    }

    public function createAction(): void
    {
        $this->assertPermission('feeds/modify');

        $this->getTabs()
            ->add('create', [
                'label' => $this->translate('Create'),
                'url' => $this->getRequest()->getUrl()
            ])
            ->activate('create');

        $this->addTitle($this->translate('Create a new feed'));

        $storage = StorageFactory::getStorage();
        $form = new FeedForm($storage, null);

        $form->on(Form::ON_SUCCESS, function () {
            Notification::success($this->translate('Created new feed'));
            $this->redirectNow('__CLOSE__');
        });

        $form->handleRequest($this->getServerRequest());

        $this->addContent($form);
    }

    public function editAction(): void
    {
        $this->assertPermission('feeds/modify');

        $this->getTabs()
            ->add('edit', [
                'label' => $this->translate('Edit'),
                'url' => $this->getRequest()->getUrl()
            ])
            ->activate('edit');

        $this->addTitle($this->translate('Edit feed'));

        $name = $this->params->shiftRequired('feed');
        $storage = StorageFactory::getStorage();

        $feed = $storage->getFeedByName($name);

        if ($feed === null) {
            $this->displayError($this->translate('Feed not found'));
            return;
        }

        $title = $this->translate('Edit feed');
        $this->setTitle($title);

        $form = new FeedForm($storage, $feed);

        $form->populate([
            'name' => $feed->name,
            'url' => $feed->url,
            'description' => $feed->description,
            'is_active' => $feed->isActive,
            'type' => $feed->type->display(),
        ]);

        $form->on(Form::ON_SUCCESS, function () {
            // TODO: Should have a different message for deletion
            Notification::success($this->translate('Updated feeds'));
            $this->redirectNow('__CLOSE__');
        });

        $form->handleRequest($this->getServerRequest());

        $this->addContent($form);
    }
}
