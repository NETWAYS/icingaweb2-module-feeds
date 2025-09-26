<?php

namespace Icinga\Module\Feeds;

use Icinga\Application\Config;
use Icinga\Application\Icinga;
use Icinga\Application\Version;
use Icinga\Module\Feeds\Parser\Result\Feed;
use Icinga\Module\Feeds\Parser\RSSParser;
use Icinga\Module\Feeds\Parser\AtomParser;
use Icinga\Module\Feeds\Parser\JsonfeedParser;
use Icinga\Module\Feeds\Parser\FeedType;

use Icinga\Application\Benchmark;

use Exception;

use GuzzleHttp\Client;

class FeedReader
{
    public function __construct(
        protected string $url,
        protected Config $config,
        protected FeedType $type = FeedType::Auto,
    ) {
    }

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

    protected function fetchFeed(): string
    {
        $timeoutInSeconds = $this->config->get('http', 'timeout', 5);

        $client = new Client([
            'timeout' => $timeoutInSeconds,
        ]);

        $response = $client->request('GET', $this->url, [
            'headers' => [
                'User-Agent' => $this->getUserAgentString(),
            ],
        ]);

        return $response->getBody()->getContents();
    }

    protected function parse(string $rawResponse): ?Feed
    {
        Benchmark::measure('Started parsing feed');

        switch ($this->type) {
            case FeedType::Auto:
                try {
                    return RSSParser::parse($rawResponse);
                } catch (Exception $ex) {
                    // Not an RSS feed
                }

                try {
                    return AtomParser::parse($rawResponse);
                } catch (Exception $ex) {
                    // Not an Atom feed
                }

                try {
                    return JsonfeedParser::parse($rawResponse);
                } catch (Exception $ex) {
                    // Not an JSONFeed feed
                }

                throw new Exception('Unsupported feed type or invalid data in feed');
            case FeedType::RSS:
                return RSSParser::parse($rawResponse);
            case FeedType::Atom:
                return AtomParser::parse($rawResponse);
            case FeedType::Jsonfeed:
                return JsonfeedParser::parse($rawResponse);
            default:
                throw new Exception('Unsupported feed type');
        }
    }

    protected function fetchImpl(): ?Feed
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
    public function fetch(?string $cacheKey = null): ?Feed
    {
        $cacheDurationInSeconds = $this->config->get('cache', 'duration', 900);
        $cache = FeedCache::instance('feeds');

        if ($cacheKey !== null && $cacheDurationInSeconds > 0) {
            if (!$cache->has($cacheKey, time() - $cacheDurationInSeconds)) {
                $data = $this->fetchImpl();
                $cache->store($cacheKey, serialize($data));
            } else {
                $data = unserialize($cache->get($cacheKey));
            }
            return $data;
        }
        return $this->fetchImpl();
    }
}
