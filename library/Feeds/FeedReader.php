<?php

namespace Icinga\Module\Feeds;

use Icinga\Application\Icinga;
use Icinga\Application\Version;
use Icinga\Module\Feeds\Parser\Result\Feed;
use Icinga\Module\Feeds\Parser\RSSParser;
use Icinga\Module\Feeds\Parser\AtomParser;
use Icinga\Module\Feeds\Parser\JsonfeedParser;
use Icinga\Module\Feeds\Parser\FeedType;

use Icinga\Application\Benchmark;

use \Exception;

class FeedReader
{
    public function __construct(
        protected string $url,
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

        return "IcingaWeb2 Module Feeds/{$moduleVersion} (icinga-web={$icingaWeb2Version['appVersion']}; php={$phpVersion})";
    }

    protected function fetchRaw()
    {
        $headers = [];
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->url);
        curl_setopt(
            $ch,
            CURLOPT_HEADERFUNCTION,
            function ($curl, $header) use (&$headers) {
                $len = strlen($header);
                $header = explode(':', $header, 2);
                if (count($header) < 2) { // ignore invalid headers
                    return $len;
                }

                $headers[strtolower(trim($header[0]))][] = trim($header[1]);

                return $len;
            }
        );
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "User-Agent: {$this->getUserAgentString()}",
        ]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $response = curl_exec($ch);
        curl_close($ch);

        return [$headers, $response];
    }

    protected function parse(string $rawResponse): ?Feed
    {
        Benchmark::measure('Started parsing feed');
        switch ($this->type) {
            case FeedType::Auto:
                try {
                    return RSSParser::parse($rawResponse);
                } catch (Exception $ex) {
                }

                try {
                    return AtomParser::parse($rawResponse);
                } catch (Exception $ex) {
                }

                try {
                    return JsonfeedParser::parse($rawResponse);
                } catch (Exception $ex) {
                }

                throw new Exception('Invalid or unsupported feed');
                break;
            case FeedType::RSS:
                return RSSParser::parse($rawResponse);
            case FeedType::Atom:
                return AtomParser::parse($rawResponse);
            case FeedType::Jsonfeed:
                return JsonfeedParser::parse($rawResponse);
            default:
                throw new Exception('Unreachable code');
        }
    
        throw new Exception('Unreachable code');
    }

    public function fetch(): ?Feed
    {
        [$headers, $rawResponse] = $this->fetchRaw();

        // FIXME: This assumes the request was successful

        return $this->parse($rawResponse);
    }
}
