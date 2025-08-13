<?php

namespace Icinga\Module\RSS;

use Icinga\Application\Icinga;
use Icinga\Application\Version;
use Icinga\Module\RSS\Parser\Result\Feed;
use Icinga\Module\RSS\Parser\RSSParser;
use Icinga\Module\RSS\Parser\AtomParser;
use Icinga\Module\RSS\Parser\JsonfeedParser;
use Icinga\Module\RSS\Parser\FeedType;

use Icinga\Application\Benchmark;

use \SimpleXMLElement;
use \Exception;

class FeedReader
{
    public function __construct(
        protected string $url,
        protected FeedType $type = FeedType::Auto,
        protected bool $trusted = false,
    ) {}

    protected function getUserAgentString(): string
    {
        $rssVersion = Icinga::app()
            ->getModuleManager()
            ->getModule('RSS')
            ->getVersion();

        $phpVersion = PHP_VERSION;

        $icingaWeb2Version = Version::get();

        return "IcingaWeb2 Module RSS/{$rssVersion} (icinga-web={$icingaWeb2Version['appVersion']}; php={$phpVersion})";
    }

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
            "User-Agent: {$this->getUserAgentString()}",
        ]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
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
                    return RSSParser::parse($rawResponse, $this->trusted);
                } catch (Exception $ex) {

                }

                try {
                    return AtomParser::parse($rawResponse, $this->trusted);
                } catch (Exception $ex) {

                }

                try {
                    return JsonfeedParser::parse($rawResponse, $this->trusted);
                } catch (Exception $ex) {

                }

                throw new Exception('Invalid or unsupported feed');
                break;
            case FeedType::RSS: return RSSParser::parse($rawResponse, $this->trusted);
            case FeedType::Atom: return AtomParser::parse($rawResponse, $this->trusted);
            case FeedType::Jsonfeed: return JsonfeedParser::parse($rawResponse, $this->trusted);
            default:
                throw new Exception('Unreachable code');
        }
    
        throw new Exception('Unreachable code');
    }

    public function fetch(): ?Feed {
        [$headers, $rawResponse] = $this->fetchRaw();

        // FIXME: This assumes the request was successful

        return $this->parse($rawResponse);
    }
}
