<?php

namespace Icinga\Module\Feeds;

use Icinga\Web\FileCache;

use FilesystemIterator;

/**
* FeedCache is a small wrapper around the FileCache
* to provide additional features we require.
*/
class FeedCache extends FileCache
{
    /**
    * clearAll removes all items from the cache
    */
    public function clearAll(): void
    {
        $iterator = new FilesystemIterator($this->basedir);
        foreach ($iterator as $file) {
            if (!$file->isFile()) {
                continue;
            }

            $this->clear($file->getFilename());
        }
    }

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
