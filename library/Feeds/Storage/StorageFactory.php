<?php

namespace Icinga\Module\Feeds\Storage;

class StorageFactory
{
    protected static ?StorageInterface $cache = null;

    protected static function getStorageImpl(): StorageInterface
    {
        // NOTE: Once there are multiple storage options they can be determined
        // from the config file
        return new FilesystemStorage();
    }

    public static function getStorage(): StorageInterface
    {
        if (static::$cache === null) {
            static::$cache = static::getStorageImpl();
        }
        return static::$cache;
    }
}
