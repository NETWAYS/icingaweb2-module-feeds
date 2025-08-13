<?php

namespace Icinga\Module\RSS\Storage;

class StorageFactory
{
    public static function getStorage(): StorageInterface
    {
        // TODO: Once there are multiple storage options they can be determined
        // from the config file
        return new Filesystem();
    }
}
