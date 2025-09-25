<?php

namespace Icinga\Module\Feeds\Forms;

use Icinga\Web\Notification;
use ipl\Validator\StringLengthValidator;
use ipl\Validator\CallbackValidator;
use ipl\Web\Compat\CompatForm;
use Icinga\Module\Feeds\Storage\StorageInterface;
use Icinga\Module\Feeds\Storage\FeedDefinition;
use Icinga\Module\Feeds\Parser\FeedType;

class CreateFeedForm extends CompatForm
{
    public function __construct(
        protected StorageInterface $storage,
    ) {
    }

    protected function assemble(): void
    {
        $this->addElement('text', 'name', [
            'label'      => $this->translate('Name'),
            'required'   => true,
            'description' => $this->translate(
                'This is the unique identifier of this feed'
            ),
            'validators' => [
                new StringLengthValidator(['max' => 255]),
                new CallbackValidator(function (string $value, CallbackValidator $validator) {
                    if (!preg_match('/^[a-zA-Z0-9\-_ ]+$/', $value)) {
                        $validator->addMessage($this->translate('The name must only contain alphanumeric characters'));
                        return false;
                    }

                    return true;
                })
            ],
        ]);

        $this->addElement('text', 'url', [
            'label'       => $this->translate('URL'),
            'required'    => true,
            'description' => $this->translate('The URL to the feed'),
        ]);

        $this->addElement('select', 'type', [
            'label'       => $this->translate('Feed type'),
            'required'    => true,
            'description' => $this->translate(
                'The type of feed that can be found at the defined URL'
            ),
            'multiOptions' => [
                'auto' => $this->translate('Determine automatically'),
                'rss' => $this->translate('RSS'),
                'atom' => $this->translate('Atom'),
                'jsonfeed' => $this->translate('JSONfeed'),
            ],
        ]);

        $this->addElement('textarea', 'description', [
            'label'       => $this->translate('Description'),
            'description' => $this->translate(
                'A slightly more detailed description for this feed, '
                . 'about 100-150 characters long'
            ),
            'rows' => 4,
            'validators' => [
                new StringLengthValidator(['max' => 255]),
            ],
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
        $description = $this->getValue('description');

        $feed = new FeedDefinition(
            $name,
            $url,
            $description,
            $type,
        );

        if ($this->storage->getFeedByName($name) !== null) {
            Notification::error("A feed with the name {$name} already exsits");
            return;
        }

        $this->storage->addFeed($feed);
    }
}
