<?php

namespace Icinga\Module\RSS\Parser;

use Icinga\Module\RSS\Parser\Result\RSSChannel;
use Icinga\Module\RSS\Parser\Result\RSSItem;

use \SimpleXMLElement;
use \Exception;

class RSS1Parser
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
                    $item = static::parseItem($xmlItemElement);
                    $channel->items[] = $item;
                    break;
            }
        }

        return $channel;
    }

    protected static function parseItem(SimpleXMLElement $xml): RSSItem
    {
        // TODO: Check if the element is of the right type
        // TODO: Implement creator
        // TODO: Implement date
        $item = new RSSItem();

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

        return $item;
    }
}
