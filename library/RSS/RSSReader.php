<?php

namespace Icinga\Module\RSS;

use Icinga\Module\RSS\Parser\Result\RSSChannel;
use Icinga\Module\RSS\Parser\Result\RSSItem;

use \SimpleXMLElement;
use \Exception;

class RSSReader
{
    public function __construct(
        protected string $url,
    ) {}

    protected function fetchRaw() {
        $headers = [];
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->url);
        curl_setopt($ch, CURLOPT_HEADERFUNCTION,
            function($curl, $header) use (&$headers)
            {
                $len = strlen($header);
                $header = explode(':', $header, 2);
                if (count($header) < 2) // ignore invalid headers
                return $len;

                $headers[strtolower(trim($header[0]))][] = trim($header[1]);

                return $len;
            }
        );
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'User-Agent: IcingaWeb2 RSS Module',
        ]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        $response = curl_exec($ch);
        curl_close($ch);

        return [$headers, $response]; 
    }

    public function fetch(): ?RSSChannel {
        [$headers, $rawResponse] = $this->fetchRaw();

        // FIXME: This assumes the request was successful

        $xmlElement = new SimpleXMLElement($rawResponse);

        if ($xmlElement->getName() !== 'rss') {
            throw new Exception('Invalid RSS-Feed');
        }

        $xmlElement->rewind();

        return $this->parseChannel($xmlElement);
    }

    protected function parseChannel(SimpleXMLElement $xml): RSSChannel
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
                    $item = $this->parseItem($xmlItemElement);
                    $channel->items[] = $item;
                    break;
            }
        }

        return $channel;
    }

    protected function parseItem(SimpleXMLElement $xml): RSSItem
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
