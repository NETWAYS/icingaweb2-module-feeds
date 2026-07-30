<?php

namespace Tests\Icinga\Module\Feeds\Parser;

use Icinga\Module\Feeds\Parser\RSS1Parser;
use Icinga\Module\Feeds\Parser\Result\Feed;

use PHPUnit\Framework\TestCase;

final class RSS1ParserTest extends TestCase
{
    public function testParse()
    {
        $raw = file_get_contents(__DIR__ .'/testdata/rss1.xml');

        $actual = RSS1Parser::parse($raw);

        $this->assertTrue($actual instanceof Feed);
        $this->assertSame($actual->title, 'RSS1 Sample Feed');
        $this->assertSame(count($actual->getItems()), 1);
    }
}
