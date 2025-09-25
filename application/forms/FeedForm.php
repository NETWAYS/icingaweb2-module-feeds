<?php

namespace Icinga\Module\Feeds\Forms;

use ipl\Web\Compat\CompatForm;
use ipl\Validator\StringLengthValidator;
use ipl\Validator\CallbackValidator;
use ipl\Web\Common\CsrfCounterMeasure;
use Icinga\Web\Session;
use Icinga\Web\Notification;
use Icinga\Module\Feeds\Storage\StorageInterface;
use Icinga\Module\Feeds\Storage\FeedDefinition;
use Icinga\Module\Feeds\Parser\FeedType;

class FeedForm extends CompatForm
{
    use CsrfCounterMeasure;

    protected ?string $deleteButtonName = null;

    public function __construct(
        protected StorageInterface $storage,
        protected ?FeedDefinition $feed,
    ) {
    }

    protected function assemble(): void
    {
        $this->addElement($this->createCsrfCounterMeasure(Session::getSession()->getId()));

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
            'validators' => [
                new CallbackValidator(function (string $value, CallbackValidator $validator) {
                    if (! filter_var($value, FILTER_VALIDATE_URL)) {
                        $validator->addMessage($this->translate('Invalid URL'));
                        return false;
                    }

                    return true;
                })
            ],
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
            'validators' => [new StringLengthValidator(['max' => 255])],
        ]);

        $this->addElement('submit', 'submit', [
            'label' => $this->isCreateForm() ? $this->translate('Create') : $this->translate('Store')
        ]);

        if (!$this->isCreateForm()) {
            $label = $this->translate('Delete');
            $this->deleteButtonName = $label;
            $deleteButton = $this->createElement('submit', 'delete', [
                'label' => $label,
            ]);
            $this->registerElement($deleteButton);
            $this->getElement('submit')
                ->getWrapper()
                ->prepend($deleteButton);
        }
    }

    public function isValid()
    {
        if ($this->getPressedSubmitElement()->getName() === 'delete') {
            $csrfElement = $this->getElement('CSRFToken');

            if (! $csrfElement->isValid()) {
                return false;
            }

            return true;
        }

        return parent::isValid();
    }

    public function hasBeenSubmitted(): bool
    {
        if (! $this->hasBeenSent()) {
            return false;
        }

        return true;
    }

    protected function isCreateForm(): bool
    {
        return $this->feed === null;
    }

    public function hasDeleteButton(): bool
    {
        return $this->deleteButtonName !== null;
    }

    public function shouldBeDeleted()
    {
        if (!$this->hasDeleteButton()) {
            return false;
        }

        return $this->getElement('delete')->hasBeenPressed();
    }

    protected function onSuccess(): void
    {
        if ($this->shouldBeDeleted()) {
            $this->storage->removeFeed($this->feed);
        } elseif ($this->getSubmitButton()->hasBeenPressed() ?? false) {
            $name = $this->getValue('name');
            $url = $this->getValue('url');
            $type = FeedType::fromDisplay($this->getValue('type') ?? 'auto');
            $description = $this->getValue('description');

            if ($this->isCreateForm()) {
                $feed = new FeedDefinition(
                    $name,
                    $url,
                    $description,
                    $type,
                );

                if ($this->storage->getFeedByName($name) !== null) {
                    Notification::error("A feed with the name {$name} already exists");
                    return;
                }

                $this->storage->addFeed($feed);
                return;
            }

            $isRename = $name !== $this->feed->name;

            if ($isRename) {
                if ($this->storage->getFeedByName($name) !== null) {
                    Notification::error("A feed with the name {$name} already exists");
                    return;
                }
                $this->storage->removeFeed($this->feed);
            }

            $this->feed->name = $name;
            $this->feed->url = $url;
            $this->feed->type = $type;
            $this->feed->description = $description;

            if ($isRename) {
                $this->storage->addFeed($this->feed);
            } else {
                $this->storage->flush();
            }
        }
    }
}
