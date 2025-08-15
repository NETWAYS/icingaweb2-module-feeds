<?php

namespace Icinga\Module\RSS\Forms;

use Icinga\Web\Notification;
use ipl\Web\Compat\CompatForm;
use Icinga\Module\RSS\Storage\StorageInterface;
use Icinga\Module\RSS\Storage\FeedDefinition;
use Icinga\Module\RSS\Parser\FeedType;

class CreateFeedForm extends CompatForm
{
    public function __construct(
        protected StorageInterface $storage,
    ) {}

    protected function assemble(): void
    {
        // TODO: Add validation
        $this->addElement('text', 'name', [
            'label'      => $this->translate('Name'),
            'required'   => true,
            'description' => $this->translate(
                'This is the unique identifier of this feed'
            ),
        ]);

        $this->addElement('text', 'url', [
            'label'       => $this->translate('URL'),
            'required'    => true,
            'description' => $this->translate('The URL to the feed'),
        ]);

        $this->addElement('select', 'type', [
            'label'       => $this->translate('Feed Type'),
            'required'    => true,
            'description' => $this->translate(
                'The type of feed that can be found at the defined URL'
            ),
            'multiOptions' => [
                'auto' => $this->translate('Determine Automatically'),
                'rss' => $this->translate('RSS'),
                'atom' => $this->translate('Atom'),
                'jsonfeed' => $this->translate('Jsonfeed'),
            ],
        ]);

        $this->addElement('select', 'trusted', [
            'label'       => $this->translate('Trusted'),
            'required'    => true,
            'description' => $this->translate(
                'Should the content of the feed be treated as comming from a trusted source.'
                . 'This will improve rendering but at the cost of opening up the posibility of XSS atacks'
            ),
            'multiOptions' => [
                'true' => $this->translate('Yes'),
                'false' => $this->translate('No'),
            ],
        ]);

        $this->addElement('textarea', 'description', [
            'label'       => $this->translate('Description'),
            'description' => $this->translate(
                'A slightly more detailed description for this feed, '
                . 'about 100-150 characters long'
            ),
            'rows' => 4,
        ]);

        $this->addElement('submit', 'submit', [
            'label' => $this->translate('Submit')
        ]);
    }

    protected function onSuccess()
    {
        $name = $this->getValue('name');
        $url = $this->getValue('url');
        $type = FeedType::fromDisplay($this->getValue('type') ?? 'auto');
        $trusted = ($this->getValue('trusted') ?? 'false') === 'true';
        $description = $this->getValue('description');

        $feed = new FeedDefinition(
            $name,
            $url,
            $description,
            $type,
            $trusted,
        );

        if ($this->storage->getFeedByName($name) !== null) {
            Notification::error("A feed with the name {$name} already exsits");
            return;
        }

        $this->storage->addFeed($feed);
    }
}
