<?php

$rssMenu = $this->menuSection('RSS')
    ->setIcon('rss')
    ->setUrl('RSS/feeds/list');

$this->provideCssFile('general.less');
$this->provideCssFile('item.less');
$this->provideCssFile('table.less');
$this->provideCssFile('view-mode-switcher.less');

$this->providePermission(
    'RSS/list',
    $this->translate('Allow to see the list of configured RSS feeds.'),
);
$this->providePermission(
    'RSS/view',
    $this->translate('Allow to view configured RSS feeds.'),
);
$this->providePermission(
    'RSS/view/arbitrary',
    $this->translate('Allow to view an arbitrary RSS feed by providing an url.'),
);
$this->providePermission(
    'RSS/modify',
    $this->translate('Allow creating and modifying RSS feeds.'),
);
