<?php

namespace Icinga\Module\Feeds\Controllers;

use Icinga\Module\Feeds\FeedReader;
use Icinga\Module\Feeds\Forms\CreateFeedForm;
use Icinga\Module\Feeds\Forms\EditFeedForm;
use Icinga\Module\Feeds\Storage\StorageFactory;
use Icinga\Module\Feeds\Controller\BaseController;
use Icinga\Module\Feeds\Parser\FeedType;

use ipl\Html\Attributes;
use ipl\Html\Form;
use ipl\Html\HtmlElement;
use Icinga\Application\Benchmark;

use Exception;

class FeedController extends BaseController
{
    public function indexAction(): void
    {
        $this->assertPermission('feeds/view');

        $this->getTabs()
            ->add('view', [
                'label'     => $this->translate('View'),
                'url'       => $this->getRequest()->getUrl()
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
        [$url, $type] = $this->getFeedInfo();
        if ($url === null) {
            return;
        }
        $date = $this->getDateParam();
        if ($date === false) {
            return;
        }

        Benchmark::measure('Started fetching feed');

        try {
            $reader = new FeedReader($url, $type);
            $data = $reader->fetch();
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

    protected function getFeedInfo(): array
    {
        $name = $this->params->shift('feed');
        if ($name !== null && $name !== '') {
            $storage = StorageFactory::getStorage();
            $feed = $storage->getFeedByName($name);
            if ($feed === null) {
                $this->displayError('Feed not found');
                return [null, null];
            }
            return [$feed->url, $feed->type];
        }

        $url = $this->params->shift('url');
        if ($url === null or $url === '') {
            $this->displayError($this->translate('No feed configured'));
            return [null, null];
        }

        $this->assertPermission('feeds/view/arbitrary');

        $type = $this->params->shift('type') ?? 'auto';

        return [$url, FeedType::fromDisplay($type)];
    }

    public function createAction(): void
    {
        $this->assertPermission('feeds/modify');

        $this->getTabs()->disableLegacyExtensions();

        $this->addTitle($this->translate('Create a new feed'));

        $storage = StorageFactory::getStorage();
        $form = new CreateFeedForm($storage);

        $form->on(Form::ON_SUCCESS, function () {
            $this->redirectNow('__CLOSE__');
        });

        $form->handleRequest($this->getServerRequest());

        $this->addContent($form);
    }

    public function editAction(): void
    {
        $this->assertPermission('feeds/modify');

        $this->getTabs()->disableLegacyExtensions();

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

        $form = new EditFeedForm($storage, $feed);

        $form->populate([
            'name' => $feed->name,
            'url' => $feed->url,
            'description' => $feed->description,
            'type' => $feed->type->display(),
        ]);

        $form->on(Form::ON_SUCCESS, function () {
            $this->redirectNow('__CLOSE__');
        });

        $form->handleRequest($this->getServerRequest());

        $this->addContent($form);
    }
}
