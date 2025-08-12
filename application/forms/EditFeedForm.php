<?php

namespace Icinga\Module\RSS\Forms;

use ipl\Sql\Connection;
use ipl\Web\Compat\CompatForm;
use Icinga\Web\Notification;
use Icinga\Module\RSS\Storage\StorageInterface;
use Icinga\Module\RSS\Storage\FeedDefinition;

class EditFeedForm extends CompatForm
{
    protected ?string $deleteButtonName = null;

    public function __construct(
        protected StorageInterface $storage,
        protected FeedDefinition $feed,
    ) {}

    protected function assemble(): void
    {
        // TODO: Add validation
        $this->addElement('text', 'name', [
            'label'      => $this->translate('Name'),
            'required'   => true,
            'description' => $this->translate(
                'This is the unique identifier of this process'
            ),
        ]);

        $this->addElement('text', 'url', [
            'label'       => $this->translate('URL'),
            'required'    => true,
            'description' => $this->translate('The URL to the feed'),
        ]);

        /* $this->addElement('textarea', 'Description', array( */
        /*     'label'       => $this->translate('Description'), */
        /*     'description' => $this->translate( */
        /*         'A slightly more detailed description for this process, about 100-150 characters long' */
        /*     ), */
        /*     'rows' => 4, */
        /* )); */
        /**/
        /* $this->addElement('select', 'Statetype', array( */
        /*     'label'       => $this->translate('State Type'), */
        /*     'required'    => true, */
        /*     'description' => $this->translate( */
        /*         'Whether this process should be based on Icinga hard or soft states' */
        /*     ), */
        /*     'multiOptions' => array( */
        /*         'soft' => $this->translate('Use SOFT states'), */
        /*         'hard' => $this->translate('Use HARD states'), */
        /*     ) */
        /* )); */
        
        $this->addElement('submit', 'submit', [
            'label' => $this->translate('Store')
        ]);

        $label = $this->translate('Delete');
        $this->deleteButtonName = $label;
        $this->addElement('submit', 'submit', [
            'label' => $label,
        ]);
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

        return $this->getElement('submit')->hasBeenPressed();
    }

    protected function onSuccess(): void
    {
        if ($this->shouldBeDeleted()) {
            $this->storage->removeFeed($this->feed);
        } else if ($this->getSubmitButton()->hasBeenPressed() ?? false) {
            $name = $this->getValue('name');
            $url = $this->getValue('url');

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

            if ($isRename) {
                $this->storage->addFeed($this->feed);
            } else {
                $this->storage->flush();
            }
        }
    }
}
