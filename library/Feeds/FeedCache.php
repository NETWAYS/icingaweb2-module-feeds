<?php

namespace Icinga\Module\Feeds;

use Icinga\Web\FileCache;

class FeedCache extends FileCache
{
    public function clear(string $name): bool
    {
        if ($this->has($name)) {
            return unlink($this->filename($name));
        }

        return false;
    }
}
