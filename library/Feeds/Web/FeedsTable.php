<?php

namespace Icinga\Module\Feeds\Web;

use Icinga\Authentication\Auth;

use ipl\Html\Table;
use ipl\Web\Url;
use ipl\Html\Html;
use ipl\Html\Text;
use ipl\Web\Widget\Link;
use ipl\I18n\Translation;

/**
 * FeedsTable lists all configured feeds and their quick actions (e.g. edit)
 * when the user has the permission.
 */
class FeedsTable extends Table
{
    use Translation;

    protected $defaultAttributes = ['class' => 'feed-table common-table table-row-selectable', 'data-base-target' => '_next'];

    protected Auth $auth;

    protected array $feeds;

    public function __construct(array $feeds)
    {
        $this->feeds = $feeds;
        $this->auth = Auth::getInstance();
    }

    protected function assemble(): void
    {
        $this->getHeader()->addHtml(self::row([
            $this->translate('Name'),
            $this->translate('Type'),
            '', // For QuickActions
        ], null, 'th'));

        $tbody = $this->getBody();

        foreach ($this->feeds as $feed) {
            $name = $feed->name;

            // If you are allowed to view the feed, add a link to the feed
            if ($this->auth->hasPermission('feeds/view')) {
                $name = new Link($feed->name, Url::fromPath('feeds/feed', ['feed' => $feed->name]), ['title' => $feed->description]);
                $name->setBaseTarget('_next');
            }

            $quickActions = Html::tag('ul', ['class' => 'quick-actions']);

            // If you are allowed to edit the feed, add a link to form feed
            if ($this->auth->hasPermission('feeds/modify')) {
                $l = new Link(
                    $this->translate('Edit'),
                    Url::fromPath('feeds/feed/edit', ['feed' => $feed->name]),
                    ['class' => 'action-link']
                );
                $edit = Html::tag('li')->add($l);
                $quickActions->add($edit);
            }

            $r = Table::row([
                $name,
                Text::create($feed->type->display()),
                $quickActions,
            ]);

            $tbody->addHtml($r);
        }
    }
}
