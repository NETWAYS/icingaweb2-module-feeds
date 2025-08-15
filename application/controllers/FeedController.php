<?php

namespace Icinga\Module\RSS\Controllers;

use Icinga\Module\RSS\FeedReader;
use Icinga\Module\RSS\Web\Item;
use Icinga\Module\RSS\Forms\CreateFeedForm;
use Icinga\Module\RSS\Forms\EditFeedForm;
use Icinga\Module\RSS\Storage\StorageFactory;
use Icinga\Module\RSS\Controller\BaseController;
use Icinga\Module\RSS\Parser\FeedType;

use ipl\Html\Attributes;
use ipl\Html\HtmlElement;
use Icinga\Application\Benchmark;

use \Exception;

class FeedController extends BaseController
{
    public function indexAction(): void
    {
        $this->assertPermission('RSS/view');

        $this->getTabs()
            ->add('view', [
                'label'     => $this->translate('View'),
                'url'       => $this->getRequest()->getUrl()
            ])
            ->activate('view');

        $controlWrapper = HtmlElement::create('div',
            Attributes::create([
                'class' => 'control-wrapper',
            ]),
            []
        );
        $this->addControl($controlWrapper);

        $this->addTitle($this->translate('Feed'), $controlWrapper);
        [$url, $type, $trusted] = $this->getFeedInfo();
        if ($url === null) {
            return;
        }
        $date = $this->getDateParam();
        if ($date === false) {
            return;
        }

        Benchmark::measure('Started fetching feed');

        try {
            $reader = new FeedReader($url, $type, $trusted);
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
                return [null, null, null];
            }
            return [$feed->url, $feed->type, $feed->trusted];
        }

        $url = $this->params->shift('url');
        if ($url === null or $url === '') {
            $this->displayError('No feed configured');
            return [null, null, null];
        }

        $this->assertPermission('RSS/view/arbitrary');

        $type = $this->params->shift('type') ?? 'auto';

        return [$url, FeedType::fromDisplay($type), false];
    }

    public function createAction(): void
    {
        $this->assertPermission('RSS/modify');

        $this->getTabs()->disableLegacyExtensions();

        $this->addTitle($this->translate('Create a new Feed'));

        $storage = StorageFactory::getStorage();
        $form = new CreateFeedForm($storage);

        $form->on(CreateFeedForm::ON_SUCCESS, function () {
            $this->redirectNow('__CLOSE__');
        });

        $form->handleRequest($this->getServerRequest());

        $this->addContent($form);
    }

    public function editAction(): void
    {
        $this->assertPermission('RSS/modify');

        $this->getTabs()->disableLegacyExtensions();

        $this->addTitle($this->translate('Edit Feed'));

        $name = $this->params->shiftRequired('feed');
        $storage = StorageFactory::getStorage();

        $feed = $storage->getFeedByName($name);
        if ($feed === null) {
            $this->displayError('Feed Not Found');
            return;
        }

        $title = $this->translate('Edit Feed');
        $this->setTitle($title);

        $form = new EditFeedForm($storage, $feed);

        $form->populate([
            'name' => $feed->name,
            'url' => $feed->url,
            'description' => $feed->description,
            'type' => $feed->type->display(),
            'trusted' => $feed->trusted ? 'true' : 'false',
        ]);

        $form->on(CreateFeedForm::ON_SUCCESS, function () {
            $this->redirectNow('__CLOSE__');
        });

        $form->handleRequest($this->getServerRequest());

        $this->addContent($form);
    }
}
