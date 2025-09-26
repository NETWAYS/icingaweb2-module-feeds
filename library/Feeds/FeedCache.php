<?php

namespace Icinga\Module\Feeds;

use Icinga\Web\FileCache;

/**
* FeedCache is a small wrapper around the FileCache
* to provide additional features we require.
*/
class FeedCache extends FileCache
{
    /**
    * clear removes a single item from the cache by its name
    */
    public function clear(string $name): bool
    {
        if ($this->has($name)) {
            return unlink($this->filename($name));
        }

        return false;
    }
}
