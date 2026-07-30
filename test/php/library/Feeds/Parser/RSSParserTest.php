<?php

namespace Tests\Icinga\Module\Feeds\Parser;

use Icinga\Module\Feeds\Parser\RSSParser;
use Icinga\Module\Feeds\Parser\Result\Feed;

use PHPUnit\Framework\TestCase;

final class RSSParserTest extends TestCase
{
    public function testParse()
    {
        $raw = file_get_contents(__DIR__ .'/testdata/rss2.xml');

        $actual = RSSParser::parse($raw);

        $this->assertTrue($actual instanceof Feed);
        $this->assertSame($actual->title, 'RSS2 Sample Feed');
        $this->assertSame(count($actual->getItems()), 1);
    }
}
