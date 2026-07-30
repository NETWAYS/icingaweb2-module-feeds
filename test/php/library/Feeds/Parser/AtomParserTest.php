<?php

namespace Tests\Icinga\Module\Feeds\Parser;

use Icinga\Module\Feeds\Parser\AtomParser;
use Icinga\Module\Feeds\Parser\Result\Feed;

use PHPUnit\Framework\TestCase;

final class AtomParserTest extends TestCase
{
    public function testParse()
    {
        $raw = file_get_contents(__DIR__ .'/testdata/atom.xml');

        $actual = AtomParser::parse($raw);

        $this->assertTrue($actual instanceof Feed);
        $this->assertSame($actual->title, 'Atom Sample Feed');
        $this->assertSame(count($actual->getItems()), 1);
    }
}
