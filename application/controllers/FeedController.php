<?php

namespace Icinga\Module\RSS\Controllers;

use Icinga\Module\RSS\RSSReader;
use Icinga\Module\RSS\Web\Item;
use Icinga\Module\RSS\Forms\CreateFeedForm;
use Icinga\Module\RSS\Forms\EditFeedForm;
use Icinga\Module\RSS\Storage\Filesystem;
use Icinga\Module\RSS\Controller\RSSController;

use Icinga\Web\Notification;
use ipl\Html\Attributes;
use ipl\Html\HtmlElement;

use \Exception;
use \DateTime;

class FeedController extends RSSController
{
    public function indexAction(): void
    {
        $this->addTitle($this->translate("Feed"));
        $url = $this->getFeedUrl();
        if ($url === null) {
            return;
        }

        try {
            $reader = new RSSReader($url);
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

    protected function getFeedUrl(): ?string
    {
        $url = null;
        $name = $this->params->shift('feed');
        if ($name !== null && $name !== '') {
            $storage = new Filesystem();
            $feed = $storage->getFeedByName($name);
            if ($feed === null) {
                $this->displayError('Feed not found');
                return null;
            }
            $url = $feed->url;
        }

        if ($url === null) {
            $url = $this->params->shift('url');
        }

        if ($url === null or $url === '') {
            $this->displayError('No feed configured');
            return null;
        }

        return $url;
    }

    public function createAction(): void
    {
        /* $this->assertPermission('rss/create'); */

        $this->addTitle($this->translate('Create a new Feed'));

        $storage = new Filesystem();
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

        $this->addTitle($this->translate('Edit Feed'));

        $name = $this->params->shiftRequired('feed');
        $storage = new Filesystem();

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
        ]);

        $form->on(EditFeedForm::ON_SUCCESS, function () {
            $this->redirectNow('__CLOSE__');
        });

        $form->handleRequest($this->getServerRequest());

        $this->addContent($form);
    }
}
