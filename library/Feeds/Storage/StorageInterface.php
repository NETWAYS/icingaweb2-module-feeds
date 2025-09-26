<?php

namespace Icinga\Module\Feeds\Storage;

/**
 * StorageInterface needs to be implemented by a storage engine
 */
interface StorageInterface
{
    public function getFeeds(): array;

    public function getFeedByName(string $name): ?FeedDefinition;

    public function removeFeed(string $name): bool;

    public function addFeed(FeedDefinition $feed): bool;

    public function reload(): void;

    public function flush(): void;
}
