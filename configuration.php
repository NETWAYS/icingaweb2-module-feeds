<?php

use Icinga\Authentication\Auth;

$feedMenu = $this->menuSection('Feeds')
    ->setIcon('rss');

$auth = Auth::getInstance();
if ($auth->hasPermission('feeds/list')) {
    $feedMenu->add('List')
        ->setIcon('edit')
        ->setUrl('feeds/feeds/list');
}

if ($auth->hasPermission('feeds/view')) {
    $feedMenu->add('View')
        ->setIcon('bars')
        ->setUrl('feeds/feeds');
}

$this->provideCssFile('item.less');
$this->provideCssFile('view-mode-switcher.less');

$this->providePermission(
    'feeds/list',
    $this->translate('Allow to see the list of configured feeds'),
);
$this->providePermission(
    'feeds/view',
    $this->translate('Allow to view configured feeds'),
);
$this->providePermission(
    'feeds/view/arbitrary',
    $this->translate('Allow to view an arbitrary feed by providing an URL'),
);
$this->providePermission(
    'feeds/modify',
    $this->translate('Allow creating and modifying feeds'),
);
