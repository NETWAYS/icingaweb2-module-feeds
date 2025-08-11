<?php

namespace Icinga\Module\RSS\Controllers;

use Icinga\Module\RSS\RSSReader;
use Icinga\Module\RSS\Web\Item;
use Icinga\Module\RSS\Forms\CreateFeedForm;
use Icinga\Module\RSS\Storage\Filesystem;

use Icinga\Web\Notification;
use ipl\Html\Attributes;
use ipl\Html\HtmlElement;
use ipl\Web\Compat\CompatController;

use \Exception;

class FeedController extends CompatController
{
    protected function displayError(string $msg): void
    {
        $this->addContent(HtmlElement::create(
            'p',
            Attributes::create([
                'tabindex' => -1,
                'class' => 'autofocus error-message',
            ]),
            $msg
        ));
    }

    public function singleAction(): void
    {
        $url = $this->params->shift('url');

        if ($url === null or $url === '') {
            $this->displayError('No feed configured');
            return;
        }

        try {
            $reader = new RSSReader($url);
            $data = $reader->fetch();
        } catch (Exception $ex) {
            $this->displayError($ex->getMessage());
            return;
        }

        $limit = $this->params->shift('limit') ?? 50;
        $compact = ($this->params->shift('view') ?? 'minimal') === 'minimal';

        $index = 0;
        $items = [];
        foreach ($data->items as $item) {
            $items[] = new Item($data, $item, $compact);
            $index++;
            if ($index > $limit) {
                break;
            }
        }

        $list = HtmlElement::create(
            'ul',
            Attributes::create(['class' => 'feed-items']),
            $items
        );

        $this->addContent($list);

        $this->setAutorefreshInterval(5);
    }

    public function createAction(): void
    {
        /* $this->assertPermission('rss/create'); */

        $title = $this->translate('Create a new Feed');
        $this->setTitle($title);

        $storage = new Filesystem();
        $form = new CreateFeedForm($storage);

        /* $formData = [ */
        /*     'name' => $this->params->get('name'), */
        /*     'url' => $this->params->get('url'), */
        /* ]; */
        /* $form->populate($formData); */

        $form->on(CreateFeedForm::ON_SUCCESS, function () {
            $this->redirectNow('__CLOSE__');
        });

        $form->handleRequest($this->getServerRequest());

        $this->addContent($form);

        /* $this->displayError('TODO: Implement form'); */
        /* return; */
    }
}
