<?php

namespace Icinga\Module\Feeds\Forms;

use Icinga\Data\ResourceFactory;
use Icinga\Forms\ConfigForm as IcingaConfigForm;

class ConfigForm extends IcingaConfigForm
{
    public function init()
    {
        $this->setSubmitLabel(t('Save Changes'));
    }

    public function createElements(array $formData)
    {
        $this->addElement('number', 'http_timeout', [
            'label' => t('Timeout for HTTP calls in seconds'),
            'description' => t('Timeout for HTTP calls in seconds'),
            'placeholder' => 10,
        ]);

        $this->addElement('number', 'cache_duration', [
            'label' => t('Lifetime of feed data in the cache in seconds'),
            'description' => t('Lifetime of feed data in the cache in seconds'),
            'placeholder' => 43200,
        ]);
    }
}
