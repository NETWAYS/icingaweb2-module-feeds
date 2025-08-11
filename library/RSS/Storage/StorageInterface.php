<?php

namespace Icinga\Module\RSS\Storage;

interface StorageInterface
{
    public function getFeeds(): array;

    public function getFeedByName(string $name): ?FeedDefinition;

    public function removeFeed(string $name): bool;

    public function addFeed(FeedDefinition $feed): bool;

    public function reload(): void;
}