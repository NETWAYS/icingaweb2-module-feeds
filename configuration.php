<?php

$rssMenu = $this->menuSection('RSS')
    ->setIcon('rss')
    ->setUrl('RSS/feeds');

$this->provideCssFile('item.less');
$this->provideCssFile('table.less');
