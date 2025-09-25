<?php

namespace Icinga\Module\Feeds\Parser;

use Icinga\Module\Feeds\Parser\Result\Feed;
use Icinga\Module\Feeds\Parser\Result\FeedItem;

use SimpleXMLElement;
use Exception;
use DateTime;
use DateTimeInterface;

class RSSParser
{
    public static function parse(string $raw): Feed
    {
        // FIXME: This assumes that the string is valid xml
        $xmlElement = new SimpleXMLElement($raw);

        if ($xmlElement->getName() !== 'rss') {
            throw new Exception('Invalid RSS-Feed');
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
                case 'item':
                    $item = static::parseItem($feed, $xmlItemElement);
                    $feed->addItem($item);
                    break;
            }
        }

        return $feed;
    }

    protected static function parseDateTime(string $dateStr): ?DateTime
    {
        $datetime = DateTime::createFromFormat(
            DateTimeInterface::RSS,
            $dateStr,
        );

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
        // TODO: Check if the element is of the right type
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
                case 'pubDate':
                    $dateString = $xmlItemElement->__toString();
                    $item->date = static::parseDateTime($dateString);
                    break;
                case 'category':
                    $category = $xmlItemElement->__toString();
                    $item->categories[] = $category;
                    break;
                case 'image':
                    foreach ($xmlItemElement as $imgTagName => $imgElement) {
                        if ($imgTagName === 'url') {
                            $item->image = $imgElement->__toString();
                            break;
                        }
                    }
                    break;
            }
        }

        foreach ($xml->children('dc', true) as $elementName => $xmlItemElement) {
            switch ($elementName) {
                case 'creator':
                    $item->creator = $xmlItemElement->__toString();
                    break;
            }
        }

        return $item;
    }
}
