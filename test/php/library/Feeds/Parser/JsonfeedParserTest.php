<?php

namespace Tests\Icinga\Module\Feeds\Parser;

use Icinga\Module\Feeds\Parser\JsonfeedParser;
use Icinga\Module\Feeds\Parser\InvalidFeedDataException;
use Icinga\Module\Feeds\Parser\InvalidFeedTypeException;
use Icinga\Module\Feeds\Parser\Result\Feed;

use PHPUnit\Framework\TestCase;

final class JsonfeedParserTest extends TestCase
{
    // https://jsonfeed.org/version/1.0
    public function testParseJsonFeed10()
    {
        $raw = file_get_contents(__DIR__ .'/testdata/jsonfeed1-0.json');

        $actual = JsonfeedParser::parse($raw);

        $this->assertTrue($actual instanceof Feed);
        $this->assertSame($actual->title, 'JSONFeed 1.0 Sample Feed');
        $this->assertSame(count($actual->getItems()), 1);
    }

    // https://jsonfeed.org/version/1.1
    public function testParseJsonFeed11()
    {
        $raw = file_get_contents(__DIR__ .'/testdata/jsonfeed1-1.json');

        $actual = JsonfeedParser::parse($raw);

        $this->assertTrue($actual instanceof Feed);
        $this->assertSame($actual->title, 'JSONFeed 1.1 Sample Feed');
        $this->assertSame(count($actual->getItems()), 2);
    }

    // Does not exist (yet)
    public function testParseJsonFeed20()
    {
        $raw = file_get_contents(__DIR__ .'/testdata/jsonfeed2-0.json');

        $this->expectException(InvalidFeedDataException::class);

        $actual = JsonfeedParser::parse($raw);
    }

    // With invalid input
    public function testParseJsonFeed_NoData()
    {
        $this->expectException(InvalidFeedTypeException::class);

        $actual = JsonfeedParser::parse('');
    }
}
