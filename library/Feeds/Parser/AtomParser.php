<?php

namespace Icinga\Module\Feeds\Parser;

use Icinga\Module\Feeds\Parser\Result\Feed;
use Icinga\Module\Feeds\Parser\Result\FeedItem;

use \SimpleXMLElement;
use \Exception;
use \DateTime;
use \DateTimeInterface;

class AtomParser
{
    public static function parse(string $raw): Feed
    {
        // FIXME: This assumes that the string is valid xml
        $xmlElement = new SimpleXMLElement($raw);

        if ($xmlElement->getName() !== 'feed') {
            throw new Exception('Invalid Atom-Feed');
        }

        $xmlElement->rewind();

        return static::parseFeed($xmlElement);
    }

    protected static function parseFeed(SimpleXMLElement $xml): Feed
    {
        $feed = new Feed();

        $linkType = null;

        foreach ($xml->children() as $elementName => $xmlItemElement) {
            switch ($elementName) {
                case 'title':
                    $feed->title = $xmlItemElement->__toString();
                    break;
                case 'link':
                    [$link, $linkType] = static::parseLink($xmlItemElement, $linkType);
                    if ($link !== null) {
                        $feed->link = $link;
                    }
                    break;
                case 'icon':
                    $feed->image = $xmlItemElement->__toString();
                    break;
                case 'logo':
                    if ($feed->image === null) {
                        $feed->image = $xmlItemElement->__toString();
                    }
                    break;
                case 'entry':
                    $item = static::parseEntry($feed, $xmlItemElement);
                    $feed->addItem($item);
                    break;
            }
        }

        return $feed;
    }

    protected static function linkRelToType(?string $rel): int
    {
        return match ($rel) {
            null => 0,
            "alternate" => 0,
            "related" => 2,
            "via" => 1,
            "self" => 3,
            default => 4,
        };
    }

    protected static function parseLink(SimpleXMLElement $xml, ?int $oldLinkType): array
    {
        $rel = null;
        $href = null;
        foreach ($xml->attributes() as $key => $value) {
            switch ($key) {
                case 'rel':
                    $rel = $value;
                    break;
                case 'href':
                    $href = $value;
                    break;
            }
        }

        $newLinkType = static::linkRelToType($rel);

        if ($oldLinkType !== null && $newLinkType > $oldLinkType) {
            return [null, $oldLinkType];
        }

        return [$href, $newLinkType];
    }

    protected static function parseCategory(SimpleXMLElement $xml): ?string
    {
        $term = null;
        $label = null;
        foreach ($xml->attributes() as $key => $value) {
            switch ($key) {
                case 'term':
                    $term = $value;
                    break;
                case 'label':
                    $label = $value;
                    break;
            }
        }

        if ($label !== null) {
            return $label;
        }

        return $term;
    }

    protected static function parsePerson(SimpleXMLElement $xml): ?string
    {
        $name = null;
        $email = null;
        foreach ($xml->children() as $elementName => $element) {
            switch ($elementName) {
                case 'name':
                    $name = $element->__toString();
                    break;
                case 'email':
                    $email = $element->__toString();
                    break;
            }
        }

        if ($name !== null) {
            return $name;
        }

        return $email;
    }

    protected static function parseDateTime(string $dateStr): ?DateTime
    {
        $datetime = DateTime::createFromFormat(
            DateTimeInterface::RFC3339,
            $dateStr,
        );

        if ($datetime === false) {
            $datetime = DateTime::createFromFormat(
                DateTimeInterface::RFC3339_EXTENDED,
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

    protected static function parseEntry(Feed $feed, SimpleXMLElement $xml): FeedItem
    {
        // TODO: Check if the element is of the right type
        $item = new FeedItem();
        $item->feed = $feed;

        $linkType = null;

        foreach ($xml->children() as $elementName => $xmlItemElement) {
            switch ($elementName) {
                case 'title':
                    $item->title = $xmlItemElement->__toString();
                    break;
                case 'link':
                    [$link, $linkType] = static::parseLink($xmlItemElement, $linkType);
                    if ($link !== null) {
                        $item->link = $link;
                    }
                    break;
                case 'summary':
                    $item->description = $xmlItemElement->__toString();
                    break;
                case 'content':
                    // FIXME: this could be just a link or even a base64
                    // encoded full content
                    if ($item->description === null) {
                        $item->description = $xmlItemElement->__toString();
                    }
                    break;
                case 'updated':
                    $dateString = $xmlItemElement->__toString();
                    $item->date = static::parseDateTime($dateString);
                    break;
                case 'published':
                    if ($item->date === null) {
                        $dateString = $xmlItemElement->__toString();
                        $item->date = static::parseDateTime($dateString);
                    }
                    break;
                case 'category':
                    $category = static::parseCategory($xmlItemElement);
                    if ($category !== null) {
                        $item->categories[] = $category;
                    }
                    break;
                case 'author':
                    $item->creator = static::parsePerson($xmlItemElement);
                    break;
            }
        }

        // Atom Media extension for YouTube
        foreach ($xml->children('media', true) as $elementName => $xmlItemElement) {
            switch ($elementName) {
                case 'group':
                    foreach ($xmlItemElement->children('media', true) as $mediaElementName => $xmlMediaElement) {
                        switch ($mediaElementName) {
                            case 'description':
                                if ($item->description === null) {
                                    $item->description = $xmlMediaElement->__toString();
                                }
                                break;
                            case 'thumbnail':
                                if ($item->image === null) {
                                    $item->image = $xmlMediaElement->attributes()['url'] ?? null;
                                }
                                break;
                        }
                    }
                    break;
            }
        }

        return $item;
    }
}
