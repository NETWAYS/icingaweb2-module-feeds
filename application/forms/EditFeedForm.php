<?php

namespace Icinga\Module\Feeds\Forms;

use ipl\Web\Compat\CompatForm;
use Icinga\Web\Notification;
use Icinga\Module\Feeds\Storage\StorageInterface;
use Icinga\Module\Feeds\Storage\FeedDefinition;
use Icinga\Module\Feeds\Parser\FeedType;

class EditFeedForm extends CompatForm
{
    protected ?string $deleteButtonName = null;

    public function __construct(
        protected StorageInterface $storage,
        protected FeedDefinition $feed,
    ) {
    }

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

        $this->addElement('textarea', 'description', [
            'label'       => $this->translate('Description'),
            'description' => $this->translate(
                'A slightly more detailed description for this feed, '
                . 'about 100-150 characters long'
            ),
            'rows' => 4,
        ]);

        $this->addElement('submit', 'submit', [
            'label' => $this->translate('Store')
        ]);

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

    public function hasBeenSubmitted(): bool
    {
        if (! $this->hasBeenSent()) {
            return false;
        }

        return true;
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
