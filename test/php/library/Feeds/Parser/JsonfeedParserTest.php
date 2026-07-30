<?php

namespace Tests\Icinga\Module\Feeds\Parser;

use Icinga\Module\Feeds\Parser\JsonfeedParser;
use Icinga\Module\Feeds\Parser\Result\Feed;

use PHPUnit\Framework\TestCase;

final class JsonfeedParserTest extends TestCase
{
    public function testParse()
    {
        $raw = file_get_contents(__DIR__ .'/testdata/jsonfeed.json');

        $actual = JsonfeedParser::parse($raw);

        $this->assertTrue($actual instanceof Feed);
        $this->assertSame($actual->title, 'JSONFeed Sample Feed');
        $this->assertSame(count($actual->getItems()), 1);
    }
}
