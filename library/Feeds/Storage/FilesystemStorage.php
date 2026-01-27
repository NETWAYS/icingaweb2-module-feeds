<?php

namespace Icinga\Module\Feeds\Storage;

use Icinga\Application\Icinga;
use Icinga\Exception\NotReadableError;
use Icinga\Exception\NotWritableError;
use Icinga\Exception\SystemPermissionException;
use Icinga\Util\DirectoryIterator;
use Icinga\Util\Json;

/**
 * FilesystemStorage is used to store the feeds configuration locally
 */
class FilesystemStorage implements StorageInterface
{
    const FILE_SUFFIX = ".json";
    const JSON_FLAGS = JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES;

    protected array $feeds = [];
    protected bool $loaded = false;

    /**
     * getConfigDir returns this module's configuration directory
     */
    protected function getConfigDir(): string
    {
        return Icinga::app()
            ->getModuleManager()
            ->getModule('feeds')
            ->getConfigDir();
    }

    /**
     * ensureConfigDir ensures the module's configuration directory exists
     */
    protected function ensureConfigDir(): void
    {
        $dir = $this->getConfigDir();

        if (!is_dir($dir)) {
            if (!is_dir(dirname($dir))) {
                if (!mkdir(dirname($dir))) {
                    throw new SystemPermissionException('Could not create config directory "%s"', dirname($dir));
                }
            }
            if (!mkdir($dir)) {
                throw new SystemPermissionException('Could not create config directory "%s"', $dir);
            }
        }
    }

    /**
     * loadFeedFile loads a feed's file by its name
     */
    protected function loadFeedFile(string $filename): FeedDefinition
    {
        $filePath = $this->getConfigDir() . DIRECTORY_SEPARATOR . $filename;

        if (!is_readable($filePath)) {
            throw new NotReadableError('Could not read file %s', $filePath);
        }

        $data = file_get_contents($filePath);

        if ($data === false) {
            throw new NotReadableError('Could not read file %s', $filePath);
        }

        $json = Json::decode($data, true);
        $feed = FeedDefinition::fromArray($json);

        return $feed;
    }

    /**
     * storeFeedFile stores a feed as JSON in the configuration directory
     */
    protected function storeFeedFile(FeedDefinition $feed): void
    {
        // Note: The frontend form validates the characters in a feed's name
        $filePath = $this->getConfigDir() . DIRECTORY_SEPARATOR . $feed->name . self::FILE_SUFFIX;

        $exists = file_exists($filePath);
        $content = Json::encode($feed->toArray(), static::JSON_FLAGS);
        // Not atomic but that's fine for now
        if (file_put_contents($filePath, $content, LOCK_EX) === false) {
            throw new NotWritableError('Could not save to %s', $filePath);
        }

        // If this is a new file, we make sure to set the mode
        if ($exists === false) {
            $fileMode = intval('0660', 8);
            if (false === @chmod($filePath, $fileMode)) {
                throw new NotWritableError('Failed to set file mode "0660" on file "%s"', $filePath);
            }
        }
    }

    /**
     * removeFeedFile removes a feed's file by its anme
     */
    public function removeFeedFile(string $filename): bool
    {
        $filePath = $this->getConfigDir() . DIRECTORY_SEPARATOR . $filename . self::FILE_SUFFIX;

        if (file_exists($filePath)) {
            return unlink($filePath);
        }

        return false;
    }

    public function getFeeds(): array
    {
        $this->load();
        return $this->feeds;
    }

    public function getFeedByName(string $name): ?FeedDefinition
    {
        $this->load();
        return $this->feeds[$name] ?? null;
    }

    public function removeFeed(string $feedname): bool
    {
        // TODO: This won't work when the feedname and filename don't match
        return $this->removeFeedFile($feedname);
    }

    public function addFeed(FeedDefinition $feed): bool
    {
        $this->load();

        if ($this->getFeedByName($feed->name)) {
            return false;
        }

        $this->feeds[$feed->name] = $feed;

        $this->flush();

        return true;
    }

    public function flush(): void
    {
        $this->ensureConfigDir();

        foreach ($this->getFeeds() as $feed) {
            $this->storeFeedFile($feed);
        }
    }

    protected function load(): void
    {
        if ($this->loaded) {
            return;
        }

        $this->feeds = [];
        $this->ensureConfigDir();

        // Load the JSON files for the feeds from the config directory
        $directory = new DirectoryIterator($this->getConfigDir(), self::FILE_SUFFIX);

        foreach ($directory as $name => $path) {
            if (is_dir($path)) {
                // Do not descend and ignore directories
                continue;
            }

            $feed = $this->loadFeedFile($name);

            if ($feed->name === '') {
                continue;
            }

            $this->feeds[$feed->name] = $feed;
        }

        $this->loaded = true;
    }

    public function reload(): void
    {
        // NOTE: This completely removes the old data before loading the new data.
        // So if the new version is invalid there is no fallback data to rely on.
        $this->loaded = false;
        $this->load();
    }
}
