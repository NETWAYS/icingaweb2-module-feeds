<?php

namespace Icinga\Module\RSS\Controllers;

use Icinga\Module\RSS\Storage\Filesystem;

use Icinga\Module\RSS\Web\Table;

use ipl\Html\Attributes;
use ipl\Html\HtmlElement;
use ipl\Web\Compat\CompatController;
use ipl\Web\Widget\Link;

class FeedsController extends CompatController
{
    public function indexAction(): void
    {
        $title = $this->translate('Feeds');
        $this->addControl(
            HtmlElement::create('h1', null, $title)
        );
        $this->setTitle($title);

        $storage = new Filesystem();
        $feeds = $storage->getFeeds();

        $data = [];
        foreach ($feeds as $feed) {
            $data[] = [
                'Name' => $feed->name,
                'Link' => $feed->url,
                '_link' => "RSS/feed/edit?feed={$feed->name}",
            ];
        }

        $table = new Table($data);
        $this->addContent($table);
    }
}
