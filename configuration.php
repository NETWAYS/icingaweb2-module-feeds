<?php

$rssMenu = $this->menuSection('RSS')
    ->setIcon('rss')
    ->setUrl('RSS/feeds/list');

$this->provideCssFile('general.less');
$this->provideCssFile('item.less');
$this->provideCssFile('table.less');
$this->provideCssFile('view-mode-switcher.less');
