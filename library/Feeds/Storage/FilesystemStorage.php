<?php

namespace Icinga\Module\Feeds\Storage;

use Icinga\Application\Icinga;
use Icinga\Exception\SystemPermissionException;

class FilesystemStorage implements StorageInterface
{
    const FILE_NAME = "feeds.json";
    const JSON_FLAGS = JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES;
    const VERSION = 1;

    protected array $feeds = [];
    protected bool $loaded = false;

    protected function getConfigFile(): string
    {
        return Icinga::app()
            ->getModuleManager()
            ->getModule('feeds')
            ->getConfigDir() . DIRECTORY_SEPARATOR . self::FILE_NAME;
    }

    protected function ensureConfigDir(): void
    {
        $file = $this->getConfigFile();
        $dir = dirname($file);

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

    protected function ensureConfigFile(): void
    {
        $file = $this->getConfigFile();
        $this->ensureConfigDir();

        if (!is_file($file)) {
            $data = [
                'version' => self::VERSION,
            ];
            if (file_put_contents($file, json_encode($data, static::JSON_FLAGS)) === false) {
                throw new SystemPermissionException('Could not write config file "%s"', dirname($file));
            }
        }
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

    public function removeFeed(string|FeedDefinition $feed): bool
    {
        if (!is_string($feed)) {
            return $this->removeFeed($feed->name);
        }

        if (!$this->getFeedByName($feed)) {
            return false;
        }

        unset($this->feeds[$feed]);
        $this->flush();
        return true;
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
        $data = [
            'version' => self::VERSION,
            'feeds' => []
        ];
        foreach ($this->getFeeds() as $feed) {
            $data['feeds'][] = $feed->toArray();
        }
        $this->ensureConfigFile();

        $file = $this->getConfigFile();
        if (file_put_contents($file, json_encode($data, static::JSON_FLAGS)) === false) {
            throw new SystemPermissionException('Could not write config file "%s"', dirname($file));
        }
    }

    protected function load(): void
    {
        if ($this->loaded) {
            return;
        }

        $this->feeds = [];

        $this->ensureConfigFile();
        $rawData = file_get_contents($this->getConfigFile());
        if ($rawData === false) {
            throw new SystemPermissionException('Could not read config file "%s"', $this->getConfigFile());
        }
        $json = json_decode($rawData, true);
        if ($json === null) {
            throw new SystemPermissionException('Could not read config file "%s"', $this->getConfigFile());
        }

        if (!array_key_exists('version', $json)) {
            throw new SystemPermissionException("Config file doesn't contain a version number. File: %s", $this->getConfigFile());
        }

        if (array_key_exists('feeds', $json)) {
            foreach ($json['feeds'] as $feedData) {
                $feed = FeedDefinition::fromArray($feedData);
                $this->feeds[$feed->name] = $feed;
            }
        }

        $this->loaded = true;
    }

    public function reload(): void
    {
        // NOTE: This completely removes the old data before loading the new data.
        // So if the new version is invalid there is no fallback data to rely on.
        $this->loaded = true;
        $this->load();
    }
}
