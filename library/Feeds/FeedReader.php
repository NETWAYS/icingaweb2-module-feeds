<?php

namespace Icinga\Module\Feeds;

use Icinga\Module\Feeds\Parser\AtomParser;
use Icinga\Module\Feeds\Parser\FeedType;
use Icinga\Module\Feeds\Parser\JsonfeedParser;
use Icinga\Module\Feeds\Parser\RSSParser;
use Icinga\Module\Feeds\Parser\RSS1Parser;
use Icinga\Module\Feeds\Parser\Result\Feed;
use Icinga\Module\Feeds\Parser\InvalidFeedDataException;
use Icinga\Module\Feeds\Parser\InvalidFeedTypeException;

use Icinga\Application\Config;
use Icinga\Application\Icinga;
use Icinga\Application\Version;
use Icinga\Application\Benchmark;

use Exception;
use GuzzleHttp\Client;

/**
 * FeedReader handles fetching and parsing the feeds
 */
class FeedReader
{
    public function __construct(
        protected string $url,
        protected Config $config,
        protected FeedType $type = FeedType::Auto,
        protected ?Client $client = null,
    ) {
    }

    /**
     * getUserAgentString returns the User Agent we use for the HTTP call
     */
    protected function getUserAgentString(): string
    {
        $moduleVersion = Icinga::app()
            ->getModuleManager()
            ->getModule('feeds')
            ->getVersion();

        $phpVersion = PHP_VERSION;

        $icingaWeb2Version = Version::get();

        // Note: Use single space to separate key-value pairs, use slash to separate keys and values
        return "icingaweb2-module-feeds/{$moduleVersion} icinga-web-version/{$icingaWeb2Version['appVersion']} php-version/{$phpVersion}";
    }

    /**
     * fetchFeed calls the defined URL and returns the response body content
     */
    protected function fetchFeed(): string
    {
        $timeoutInSeconds = $this->config->get('http', 'timeout', 5);

        $client = $this->client ?? new Client(['timeout' => $timeoutInSeconds]);

        $response = $client->request('GET', $this->url, [
            'headers' => [
                'User-Agent' => $this->getUserAgentString(),
            ],
        ]);

        return $response->getBody()->getContents();
    }

    /**
     * parse tries to parse the response body
     */
    protected function parse(string $rawResponse): ?Feed
    {
        Benchmark::measure('Started parsing feed');

        return match ($this->type) {
            FeedType::Auto => $this->parseAuto($rawResponse),
            FeedType::RSS => RSSParser::parse($rawResponse),
            FeedType::RSS1 => RSS1Parser::parse($rawResponse),
            FeedType::Atom => AtomParser::parse($rawResponse),
            FeedType::Jsonfeed => JsonfeedParser::parse($rawResponse),
            default => throw new InvalidFeedTypeException('Unsupported feed type'),
        };
    }


    protected function parseAuto(string $rawResponse): ?Feed
    {
        $parsers = [RSSParser::class, RSS1Parser::class, AtomParser::class, JsonfeedParser::class];

        foreach ($parsers as $parser) {
            try {
                return $parser::parse($rawResponse);
            } catch (InvalidFeedDataException $e) {
                throw new Exception('Invalid data in feed: ' . $e->getMessage(), $e->getCode(), $e);
            } catch (Exception) {
                // Let's try the next format
            }
        }

        throw new Exception('Unsupported feed type');
    }

    /**
    * fetchAndParse is just a small wrapper to fetch and parse a feed
    */
    protected function fetchAndParse(): ?Feed
    {
        try {
            $response = $this->fetchFeed();
        } catch (Exception $ex) {
            throw new Exception('Could not fetch feed: ' . $ex->getMessage(), $ex->getCode(), $ex);
        }

        return $this->parse($response);
    }

    /**
    * fetch loads a feed either from the cache or from its URL
    */
    public function fetch(?string $cacheKey = null, ?int $cacheDurationInSeconds = null): ?Feed
    {
        // We don't expect feeds to update very frequently, to avoid
        // 429 errors we set the default to 12 hours
        if ($cacheDurationInSeconds === null) {
            $cacheDurationInSeconds = $this->config->get('cache', 'duration', 43200);
        }
        $cache = FeedCache::instance('feeds');

        if ($cacheKey !== null && $cacheDurationInSeconds > 0) {
            if (!$cache->has($cacheKey, time() - $cacheDurationInSeconds)) {
                $data = $this->fetchAndParse();
                $cache->store($cacheKey, serialize($data));
            } else {
                $data = unserialize($cache->get($cacheKey));
            }
            return $data;
        }

        return $this->fetchAndParse();
    }
}
