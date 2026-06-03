<?php

namespace Icinga\Module\Feeds\Parser;

use Icinga\Module\Feeds\Parser\Result\Feed;
use Icinga\Module\Feeds\Parser\Result\FeedItem;

use SimpleXMLElement;
use Exception;
use DateTime;
use DateTimeInterface;

/**
 * RSS1Parser is used to parse RSS 1.0 feeds
 */
class RSS1Parser
{
    /**
     * parse tries to parse the given string into a Feed object
     */
    public static function parse(string $raw): Feed
    {
        $xmlElement = new SimpleXMLElement($raw);
        $xmlElement->registerXPathNamespace('rss', 'http://purl.org/rss/1.0/');
        $xmlElement->registerXPathNamespace('dc', 'http://purl.org/dc/elements/1.1/');

        if ($xmlElement->getName() !== 'RDF') {
            throw new Exception('Invalid RSS1.0-Feed');
        }

        $xmlElement->rewind();

        return static::parseFeed($xmlElement);
    }

    protected static function parseFeed(SimpleXMLElement $xml): Feed
    {
        $feed = new Feed();

        foreach ($xml->getChildren() as $elementName => $xmlItemElement) {
            switch ($elementName) {
                case 'title':
                    $feed->title = $xmlItemElement->__toString();
                    break;
                case 'link':
                    $feed->link = $xmlItemElement->__toString();
                    break;
                case 'description':
                    $feed->description = $xmlItemElement->__toString();
                    break;
                case 'image':
                    foreach ($xmlItemElement as $imgTagName => $imgElement) {
                        if ($imgTagName === 'url') {
                            $feed->image = $imgElement->__toString();
                            break;
                        }
                    }
                    break;
            }
        }

        $items = $xml->xpath('//rss:item');

        foreach ($items as $xmlItemElement) {
            $item = static::parseItem($feed, $xmlItemElement);
            $feed->addItem($item);
        }

        return $feed;
    }

    protected static function parseDateTime(string $dateStr): ?DateTime
    {
        $datetime = DateTime::createFromFormat(
            DateTimeInterface::RSS,
            $dateStr,
        );

        // <dc:date> should be ISO 8601: https://web.resource.org/rss/1.0/modules/dc/
        if ($datetime === false) {
            $datetime = DateTime::createFromFormat(
                DateTimeInterface::ISO8601,
                $dateStr,
            );
        }

        // We probably don't need this, but let's keep it just in case
        if ($datetime === false) {
            $datetime = DateTime::createFromFormat(
                DateTimeInterface::RFC822,
                $dateStr,
            );
        }

        if ($datetime === false) {
            $datetime = DateTime::createFromFormat(
                DateTimeInterface::RFC7231,
                $dateStr,
            );
        }

        if ($datetime === false) {
            try {
                $datetime = new DateTime($dateStr);
            } catch (Exception $ex) {
                // NOTE: Nothing to do here, but be ultimately failed to parse
                // the time
                $datetime = false;
            }
        }

        if ($datetime === false) {
            return null;
        }

        return $datetime;
    }

    protected static function parseItem(Feed $feed, SimpleXMLElement $xml): FeedItem
    {
        $item = new FeedItem();
        $item->feed = $feed;

        foreach ($xml->children() as $elementName => $xmlItemElement) {
            switch ($elementName) {
                case 'title':
                    $item->title = $xmlItemElement->__toString();
                    break;
                case 'link':
                    $item->link = $xmlItemElement->__toString();
                    break;
                case 'description':
                    $item->description = $xmlItemElement->__toString();
                    break;
            }
        }

        foreach ($xml->children('dc', true) as $elementName => $xmlItemElement) {
            switch ($elementName) {
                case 'date':
                    $dateString = $xmlItemElement->__toString();
                    $item->date = static::parseDateTime($dateString);
                    break;
                case 'creator':
                    $item->creator = $xmlItemElement->__toString();
                    break;
            }
        }

        return $item;
    }
}
