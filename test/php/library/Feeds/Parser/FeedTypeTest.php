<?php

namespace Tests\Icinga\Module\Feeds\Parser;

use Icinga\Module\Feeds\Parser\FeedType;

use PHPUnit\Framework\TestCase;

final class FeedTypeTest extends TestCase
{
    public function testRss()
    {
        $actual = FeedType::fromDisplay('auto');
        $this->assertSame($actual->display(), 'auto');

        $actual = FeedType::fromDisplay('rss');
        $this->assertSame($actual->display(), 'rss');

        $actual = FeedType::fromDisplay('atom');
        $this->assertSame($actual->display(), 'atom');
    }
}
