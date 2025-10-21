<?php

namespace Icinga\Module\Feeds\Forms;

use Icinga\Application\Config;
use Icinga\Module\Feeds\FeedCache;

use Icinga\Module\Feeds\Parser\FeedType;
use Icinga\Module\Feeds\Storage\FeedDefinition;
use Icinga\Module\Feeds\Storage\StorageInterface;
use Icinga\Web\Notification;
use Icinga\Web\Session;

use ipl\Validator\CallbackValidator;
use ipl\Validator\StringLengthValidator;
use ipl\Web\Common\CsrfCounterMeasure;
use ipl\Web\Compat\CompatForm;

/**
 * FeedForm is used to create and edit feeds in the configuration
 */
class FeedForm extends CompatForm
{
    use CsrfCounterMeasure;

    protected ?string $deleteButtonName = null;

    // Pattern to validate feed names
    protected const VALID_NAME = '/^[a-zA-Z0-9\-_\. ]+$/';

    public function __construct(
        protected Config $config,
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
                    if (!preg_match(static::VALID_NAME, $value)) {
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

        $this->addElement(
            'checkbox',
            'is_visible',
            [
                'label' => $this->translate('Show by default'),
                'value' => true,
                'description' => $this->translate(
                    'Show or hide this feed. Hidden feeds will not be fetched in the feeds view by default. They can still explicitly requested via their name.'
                )
            ]
        );

        $cacheDurationInSeconds = $this->config->get('cache', 'duration', 43200);
        $this->addElement(
            'number',
            'polling_interval',
            [
                'label' => $this->translate('Polling interval'),
                'description' => $this->translate(
                    'How often the feed should be updated from the source (in seconds)'
                ),
                'placeholder' => $cacheDurationInSeconds . " (" . $this->translate('inherited from module configuration') . ")",
            ]
        );

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
        $cache = FeedCache::instance('feeds');

        if ($this->shouldBeDeleted()) {
            $this->storage->removeFeed($this->feed);
            // Clear the cache on a delete
            $cache->clear('feed-' . $this->feed->name);
        } elseif ($this->getSubmitButton()->hasBeenPressed() ?? false) {
            $name = trim($this->getValue('name'));
            $url = trim($this->getValue('url'));
            $isVisible = $this->getElement('is_visible')->isChecked();
            $type = FeedType::fromDisplay($this->getValue('type') ?? 'auto');
            $pollingInterval = $this->getValue('polling_interval') ?? null;
            $description = trim($this->getValue('description'));

            if ($this->isCreateForm()) {
                $feed = new FeedDefinition(
                    $name,
                    $url,
                    $description,
                    $isVisible,
                    $type,
                    $pollingInterval,
                );

                if ($this->storage->getFeedByName($name) !== null) {
                    Notification::error($this->translate(sprintf("A feed with the name %s already exists", $name)));
                    return;
                }

                $this->storage->addFeed($feed);
                return;
            }
            // Always clear the cache on an edit
            $cache->clear('feed-' . $this->feed->name);

            $isRename = $name !== $this->feed->name;

            if ($isRename) {
                if ($this->storage->getFeedByName($name) !== null) {
                    Notification::error($this->translate(sprintf("A feed with the name %s already exists", $name)));
                    return;
                }
                $this->storage->removeFeed($this->feed);
            }

            $this->feed->name = $name;
            $this->feed->url = $url;
            $this->feed->type = $type;
            $this->feed->isVisible = $isVisible;
            $this->feed->description = $description;
            $this->feed->pollingInterval = $pollingInterval;

            if ($isRename) {
                $this->storage->addFeed($this->feed);
            } else {
                $this->storage->flush();
            }
        }
    }
}
