<?php

namespace Icinga\Module\Feeds\Controller;

use GuzzleHttp\Psr7\ServerRequest;
use Icinga\Application\Config;
use Icinga\Application\Logger;
use Icinga\Data\ConfigObject;
use Icinga\Exception\Json\JsonDecodeException;
use Icinga\Module\Feeds\Web\Item;

use Icinga\Module\Feeds\Web\FeedViewModeSwitcher;
use Icinga\User\Preferences;
use Icinga\User\Preferences\PreferencesStore;
use Icinga\Util\Json;
use ipl\Html\Attributes;
use ipl\Html\Form;
use ipl\Html\HtmlElement;
use ipl\Web\Compat\CompatController;

use Exception;
use DateTime;
use ipl\Web\Control\LimitControl;
use ipl\Web\Url;

class BaseController extends CompatController
{
    protected function displayError(string $msg): void
    {
        $this->addContent(HtmlElement::create(
            'p',
            Attributes::create([
                'tabindex' => -1,
                'class' => 'autofocus error-message',
            ]),
            $msg
        ));
    }

    protected function addTitle(string $title, ?HtmlElement $parent = null): HtmlElement
    {
        $titleElement = HtmlElement::create('h1', null, $title);

        if ($parent !== null) {
            $parent->add($titleElement);
        } else {
            $this->addControl($titleElement);
        }

        $this->setTitle($title);

        return $titleElement;
    }

    protected function renderItems(
        array $items,
        ?int $limit,
        ?DateTime $date,
        bool $compact,
    ): void {
        $index = 1;
        $elements = [];

        foreach ($items as $item) {
            if ($date !== null && $item->date < $date) {
                continue;
            }

            $elements[] = new Item($item, $compact);
            $index++;

            if ($index > $limit) {
                break;
            }
        }

        if (count($elements) == 0) {
            $this->displayError($this->translate('No news to display'));
            return;
        }

        $list = HtmlElement::create(
            'ul',
            Attributes::create(['class' => 'feed-items']),
            $elements
        );

        $this->addContent($list);
    }

    protected function getLimitParam(): int
    {
        $limit = $this->params->shift('limit') ?? 200;

        if ($limit <= 0) {
            $limit = 1;
        }

        if ($limit > 5000) {
            $limit = 5000;
        }

        return $limit;
    }

    protected function getDateParam(): DateTime|bool|null
    {
        $date = $this->params->shift('date');

        if ($date !== null) {
            try {
                return new DateTime($date);
            } catch (Exception $ex) {
                $this->displayError($this->translate('Invalid date'));
                return false;
            }
        }

        return null;
    }

    /**
     * Create and return the ViewModeSwitcher
     *
     * This automatically shifts the view mode URL parameter from {@link $params}.
     *
     * @param LimitControl $limitControl
     * @param bool $verticalPagination
     *
     * @return FeedViewModeSwitcher
     */
    public function createViewModeSwitcher(
        LimitControl $limitControl,
        bool $verticalPagination = false
    ): FeedViewModeSwitcher {
        $viewModeSwitcher = new FeedViewModeSwitcher();

        $viewModeSwitcher->setIdProtector([$this->getRequest(), 'protectId']);

        $user = $this->Auth()->getUser();

        if (($preferredModes = $user->getAdditional('feeds.view_modes')) === null) {
            try {
                $preferredModes = Json::decode(
                    $user->getPreferences()->getValue('feeds', 'view_modes', '[]'),
                    true
                );
            } catch (JsonDecodeException $e) {
                Logger::error('Failed to load preferred view modes for user "%s": %s', $user->getUsername(), $e);
                $preferredModes = [];
            }

            $user->setAdditional('feeds.view_modes', $preferredModes);
        }

        $requestRoute = $this->getRequest()->getUrl()->getPath();

        if (isset($preferredModes[$requestRoute])) {
            $viewModeSwitcher->setDefaultViewMode($preferredModes[$requestRoute]);
        }

        $viewModeSwitcher->populate([
            $viewModeSwitcher->getViewModeParam() => $this->params->shift($viewModeSwitcher->getViewModeParam())
        ]);

        $session = $this->Window()->getSessionNamespace(
            'feeds-viewmode-' . $this->Window()->getContainerId()
        );

        $viewModeSwitcher->on(
            Form::ON_SUCCESS,
            function (FeedViewModeSwitcher $viewModeSwitcher) use (
                $user,
                $preferredModes,
                $verticalPagination,
                &$session
            ) {
                $viewMode = $viewModeSwitcher->getValue($viewModeSwitcher->getViewModeParam());
                $requestUrl = Url::fromRequest();

                $preferredModes[$requestUrl->getPath()] = $viewMode;
                $user->setAdditional('feeds.view_modes', $preferredModes);

                try {
                    $preferencesStore = PreferencesStore::create(new ConfigObject([
                        //TODO: Don't set store key as it will no longer be needed once we drop support for
                        // lower version of icingaweb2 then v2.11.
                        //https://github.com/Icinga/icingaweb2/pull/4765
                        'store'     => Config::app()->get('global', 'config_backend', 'db'),
                        'resource'  => Config::app()->get('global', 'config_resource')
                    ]), $user);
                    $preferencesStore->load();
                    $preferencesStore->save(
                        new Preferences(['feeds' => ['view_modes' => Json::encode($preferredModes)]])
                    );
                } catch (Exception $e) {
                    Logger::error('Failed to save preferred view mode for user "%s": %s', $user->getUsername(), $e);
                }

                $limitParam = LimitControl::DEFAULT_LIMIT_PARAM;

                $requestUrl->setParam($viewModeSwitcher->getViewModeParam(), $viewMode);
                if (! $requestUrl->hasParam($limitParam)) {
                    if ($viewMode === 'minimal' || $viewMode === 'grid') {
                        $session->set('request_path', $requestUrl->getPath());
                    } elseif ($viewModeSwitcher->getDefaultViewMode() === 'minimal'
                        || $viewModeSwitcher->getDefaultViewMode() === 'grid'
                    ) {
                        $session->clear();
                    }
                }

                $this->redirectNow($requestUrl);
            }
        )->handleRequest(ServerRequest::fromGlobals());

        $viewMode = $viewModeSwitcher->getViewMode();

        if ($viewMode === 'minimal' || $viewMode === 'grid') {
            $hasLimitParam = Url::fromRequest()->hasParam($limitControl->getLimitParam());

            if (!$hasLimitParam) {
                $limitControl->setDefaultLimit($limitControl->getDefaultLimit() * 2);
            }
        }

        $requestPath =  $session->get('request_path');

        if ($requestPath && $requestPath !== $requestRoute) {
            $session->clear();
        }

        return $viewModeSwitcher;
    }
}
