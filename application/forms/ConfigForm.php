<?php

namespace Icinga\Module\Feeds\Forms;

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
            'label' => t('Update interval'),
            'description' => t('How often to check for new feed updates (in seconds)'),
            'placeholder' => 43200,
        ]);
    }
}
