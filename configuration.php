<?php

$rssMenu = $this->menuSection('RSS')
    ->setIcon('rss')
    ->setUrl('RSS/feed/single');

$this->provideCssFile('item.less');
