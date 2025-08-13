<?php

namespace Icinga\Module\RSS\Parser;

use Icinga\Module\RSS\Parser\Result\RSSChannel;
use Icinga\Module\RSS\Parser\Result\RSSItem;

use \Exception;
use \DateTime;
use \DateTimeInterface;

class JsonfeedParser
{
    public static function parse(string $raw): RSSChannel
    {
        $json = json_decode($raw, true);
        if ($json === null) {
            throw new Exception('Invalid Jsonfeed');
        }

        // TODO: validate version field

        return static::parseChannel($json);
    }

    protected static function parseChannel(array $json): RSSChannel
    {
        $channel = new RSSChannel();

        $channel->title = $json['title'] ?? null;
        $channel->link = $json['home_page_url'] ?? $json['feed_url'] ?? null;
        $channel->image = $json['icon'] ?? $json['favicon'] ?? null;
        $channel->description = $json['description'] ?? null;

        $items = $json['items'] ?? null;
        if ($items === null) {
            throw new Exception('Jsonfeed contains no items');
        }

        foreach ($items as $jsonItem) {
            $item = static::parseItem($channel, $jsonItem);
            $channel->addItem($item);
        }

        return $channel;
    }

    protected static function parseDateTime(string $dateStr): ?DateTime
    {
        $datetime = DateTime::createFromFormat(
            DateTimeInterface::RFC3339,
            $dateStr,
        );

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

    protected static function parseItem(RSSChannel $channel, array $json): RSSItem
    {
        $item = new RSSItem();
        $item->channel = $channel;

        $item->title = $json['title'] ?? null;
        $item->link = $json['url'] ?? $json['external_url'] ?? null;
        $item->description = $json['summary'] ?? $json['content_html'] ?? $json['content_text'] ?? null;
        $item->categories = $json['tags'] ?? [];
        if (array_key_exists('author', $json)) {
            $item->creator = $json['author']['name'] ?? null;
        } else if(array_key_exists('authors', $json)) {
            $item->creator = $json['authors'][0]['name'] ?? null;
        }
        $item->image = $json['image'] ?? null;
        $item->date = static::parseDateTime($json['date_modified'] ?? '') ?? static::parseDateTime($json['date_published'] ?? '') ?? null;

        return $item;
    }
}
