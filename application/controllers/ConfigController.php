<?php

namespace Icinga\Module\Feeds\Controllers;

use Icinga\Module\Feeds\Forms\ConfigForm;

use Icinga\Application\Config;
use Icinga\Web\Form;
use Icinga\Web\Widget\Tab;
use Icinga\Web\Widget\Tabs;

use ipl\Web\Compat\CompatController;
use ipl\Html\HtmlString;

class ConfigController extends CompatController
{
    protected bool $disableDefaultAutoRefresh = true;

    /**
     * Initialize the Controller.
     */
    public function init(): void
    {
        // Assert the user has access to this controller.
        $this->assertPermission('config/modules');
        parent::init();
    }

    /**
     * generalAction provides the configuration form.
     * For now we have everything on a single Tab, might be extended in the future.
     */
    public function generalAction(): void
    {
        $config = Config::module('feeds');

        $form = new ConfigForm();
        $form->setIniConfig($config);
        $form->handleRequest();

        $this->mergeTabs($this->Module()->getConfigTabs()->activate('general'));

        $this->addContent(new HtmlString($form->render()));
    }

    protected function addFormToContent(Form $form)
    {
        $this->addContent(new HtmlString($form->render()));
    }

    protected function mergeTabs(Tabs $tabs): self
    {
        /** @var Tab $tab */
        foreach ($tabs->getTabs() as $tab) {
            $this->tabs->add($tab->getName(), $tab);
        }

        return $this;
    }
}
