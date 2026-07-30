<?php

namespace Icinga\Module\Feeds\Clicommands;

use Icinga\Module\Feeds\FeedCache;

use Icinga\Cli\Command;

class CacheCommand extends Command
{
    /**
     * Clears the entire FileCache used by the Feeds module
     *
     * USAGE
     *
     *  icingacli feeds cache clear
     */
    public function clearAction(): void
    {
        $cache = FeedCache::instance('feeds');
        $cache->clearAll();
    }
}
