<?php

use Icinga\Authentication\Auth;

$auth = Auth::getInstance();
if ($auth->hasPermission('feeds/list')) {
    $rssMenu = $this->menuSection('Feeds')
        ->setIcon('rss')
        ->setUrl('feeds/feeds/list');
} else if ($auth->hasPermission('feeds/view')) {
    $rssMenu = $this->menuSection('Feeds')
        ->setIcon('rss')
        ->setUrl('feeds/feeds');
}

$this->provideCssFile('general.less');
$this->provideCssFile('item.less');
$this->provideCssFile('table.less');
$this->provideCssFile('view-mode-switcher.less');

$this->providePermission(
    'feeds/list',
    $this->translate('Allow to see the list of configured feeds.'),
);
$this->providePermission(
    'feeds/view',
    $this->translate('Allow to view configured feeds.'),
);
$this->providePermission(
    'feeds/view/arbitrary',
    $this->translate('Allow to view an arbitrary feed by providing an url.'),
);
$this->providePermission(
    'feeds/modify',
    $this->translate('Allow creating and modifying feeds.'),
);
