<?php

namespace Icinga\Module\RSS\Forms;

use Icinga\Web\Notification;
use ipl\Web\Compat\CompatForm;
use Icinga\Module\RSS\Storage\StorageInterface;
use Icinga\Module\RSS\Storage\FeedDefinition;

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

        /* $label = $this->translate('Delete'); */
        /* $el = $this->createElement( */
        /*     'submit', */
        /*     $label, */
        /*     [ */
        /*         'data-base-target' => '_main', */
        /*     ]) */
        /*     ->setLabel($label); */
        /* $this->deleteButtonName = $el->getName(); */
        /* $this->addElement($el); */

        $this->addElement('submit', 'submit', [
            'label' => $this->translate('Submit')
        ]);
    }

    protected function onSuccess()
    {
        $name = $this->getValue('name');
        $url = $this->getValue('url');

        $feed = new FeedDefinition($name, $url);

        if ($this->storage->getFeedByName($name) !== null) {
            Notification::error("A feed with the name {$name} already exsits");
            return;
        }

        $this->storage->addFeed($feed);
    }
}
