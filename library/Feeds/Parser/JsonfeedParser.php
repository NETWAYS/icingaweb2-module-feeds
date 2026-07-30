<?php

namespace Icinga\Module\Feeds\Parser;

use Icinga\Module\Feeds\Parser\Result\Feed;
use Icinga\Module\Feeds\Parser\Result\FeedItem;

use Exception;
use JsonException;
use DateTime;
use DateTimeInterface;

/**
 * JsonfeedParser is used to parse JSONFeed feeds
 */
class JsonfeedParser
{
    protected const JSONFEED_V10 = 'https://jsonfeed.org/version/1';
    protected const JSONFEED_V11 = 'https://jsonfeed.org/version/1.1';

    /**
     * parse tries to parse the given string into a Feed object
     */
    public static function parse(string $raw): Feed
    {
        try {
            $json = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            throw new InvalidFeedTypeException('Invalid JSONfeed');
        }

        if ($json === null) {
            throw new InvalidFeedTypeException('Invalid JSONfeed');
        }

        $version = $json['version'] ?? '';
        if (!str_contains($version, self::JSONFEED_V10)) {
            throw new InvalidFeedDataException('Unsupported JSONfeed version');
        }

        return static::parseFeed($json);
    }

    protected static function parseFeed(array $json): Feed
    {
        $feed = new Feed();

        $feed->title = $json['title'] ?? null;
        $feed->link = $json['home_page_url'] ?? $json['feed_url'] ?? null;
        $feed->image = $json['icon'] ?? $json['favicon'] ?? null;
        $feed->description = $json['description'] ?? null;

        $items = $json['items'] ?? null;

        if ($items === null) {
            throw new InvalidFeedDataException('JSONfeed contains no items');
        }

        foreach ($items as $jsonItem) {
            $item = static::parseItem($feed, $jsonItem);
            $feed->addItem($item);
        }

        return $feed;
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
                // NOTE: Nothing to do here, but be ultimately failed to parse the time
                $datetime = false;
            }
        }

        if ($datetime === false) {
            return null;
        }

        return $datetime;
    }

    protected static function parseItem(Feed $feed, array $json): FeedItem
    {
        $item = new FeedItem();
        $item->feed = $feed;

        $item->title = $json['title'] ?? null;
        $item->link = $json['url'] ?? $json['external_url'] ?? null;

        $item->description = $json['content_html'] ?? $json['content_text'] ?? $json['summary'] ?? null;

        $item->categories = $json['tags'] ?? [];

        if (array_key_exists('author', $json)) {
            $item->creator = $json['author']['name'] ?? null;
        } elseif (array_key_exists('authors', $json)) {
            $item->creator = $json['authors'][0]['name'] ?? null;
        }

        $item->image = $json['image'] ?? null;
        $item->date = static::parseDateTime($json['date_modified'] ?? '') ?? static::parseDateTime($json['date_published'] ?? '') ?? null;

        return $item;
    }
}
