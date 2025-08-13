<?php

namespace Icinga\Module\RSS\Controllers;

use Icinga\Module\RSS\FeedReader;
use Icinga\Module\RSS\Web\Item;
use Icinga\Module\RSS\Forms\CreateFeedForm;
use Icinga\Module\RSS\Forms\EditFeedForm;
use Icinga\Module\RSS\Storage\StorageFactory;
use Icinga\Module\RSS\Controller\RSSController;
use Icinga\Module\RSS\Parser\FeedType;

use Icinga\Web\Notification;
use ipl\Html\Attributes;
use ipl\Html\HtmlElement;

use \Exception;
use \DateTime;

class FeedController extends RSSController
{
    public function indexAction(): void
    {
        $this->getTabs()
            ->add('view', [
                'label'     => $this->translate('View'),
                'url'       => $this->getRequest()->getUrl()
            ])
            ->activate('view');

        $this->addTitle($this->translate("Feed"));
        [$url, $type] = $this->getFeedInfo();
        if ($url === null) {
            return;
        }

        try {
            $reader = new FeedReader($url, $type);
            $data = $reader->fetch();
        } catch (Exception $ex) {
            $this->displayError($ex->getMessage());
            return;
        }

        $limit = $this->getLimitParam();
        $compact = $this->getViewParam() === 'minimal';
        $date = $this->getDateParam();
        if ($date === false) {
            return;
        }

        $this->renderItems(
            $data->getItems(),
            $limit,
            $date,
            $compact,
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
            return [$feed->url, $feed->feedtype];
        }

        $url = $this->params->shift('url');

        if ($url === null or $url === '') {
            $this->displayError('No feed configured');
            return [null, null];
        }

        $type = $this->params->shift('type') ?? 'auto';

        return [$url, FeedType::fromDisplay($type)];
    }

    public function createAction(): void
    {
        /* $this->assertPermission('rss/create'); */

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
        /* $this->assertPermission('rss/edit'); */

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
            'feedtype' => $feed->feedtype->display(),
        ]);

        $form->on(EditFeedForm::ON_SUCCESS, function () {
            $this->redirectNow('__CLOSE__');
        });

        $form->handleRequest($this->getServerRequest());

        $this->addContent($form);
    }
}
