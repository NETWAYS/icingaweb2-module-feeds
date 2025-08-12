<?php

namespace Icinga\Module\RSS\Parser;

use Icinga\Module\RSS\Parser\Result\RSSChannel;
use Icinga\Module\RSS\Parser\Result\RSSItem;

use \SimpleXMLElement;
use \Exception;
use \DateTime;
use \DateTimeInterface;

class RSSParser
{
    public static function parse(string $raw): RSSChannel
    {
        // FIXME: This assumes that the string is valid xml
        $xmlElement = new SimpleXMLElement($raw);

        if ($xmlElement->getName() !== 'rss') {
            throw new Exception('Invalid RSS-Feed');
        }

        $xmlElement->rewind();

        return static::parseChannel($xmlElement);
    }

    protected static function parseChannel(SimpleXMLElement $xml): RSSChannel
    {
        // TODO: Check if the element is of the right type
        $channel = new RSSChannel();

        foreach ($xml->getChildren() as $elementName => $xmlItemElement) {
            switch($elementName) {
                case 'title':
                    $channel->title = $xmlItemElement->__toString();
                    break;
                case 'link':
                    $channel->link = $xmlItemElement->__toString();
                    break;
                case 'description':
                    $channel->description = $xmlItemElement->__toString();
                    break;
                case 'image':
                    foreach($xmlItemElement as $imgTagName => $imgElement) {
                        if($imgTagName === 'url') {
                            $channel->image = $imgElement->__toString();
                            break;
                        }
                    }
                    break;
                case 'item':
                    $item = static::parseItem($channel, $xmlItemElement);
                    $channel->addItem($item);
                    break;
            }
        }

        return $channel;
    }

    protected static function parseDateTime(string $dateStr): ?DateTime
    {
        $datetime = DateTime::createFromFormat(
            DateTimeInterface::RFC822,
            $dateStr,
        );

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

    protected static function parseItem(RSSChannel $channel, SimpleXMLElement $xml): RSSItem
    {
        // TODO: Check if the element is of the right type
        $item = new RSSItem();
        $item->channel = $channel;

        foreach ($xml->children() as $elementName => $xmlItemElement) {
            switch($elementName) {
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
                case 'image':
                    foreach($xmlItemElement as $imgTagName => $imgElement) {
                        if($imgTagName === 'url') {
                            $item->image = $imgElement->__toString();
                            break;
                        }
                    }
                    break;
            }
        }

        foreach ($xml->children('dc', true) as $elementName => $xmlItemElement) {
            switch($elementName) {
                case 'creator':
                    $item->creator = $xmlItemElement->__toString();
                    break;
            }
        }

        return $item;
    }
}
