<?php

namespace Icinga\Module\RSS;

use Icinga\Module\RSS\Parser\Result\RSSChannel;
use Icinga\Module\RSS\Parser\RSSParser;
use Icinga\Module\RSS\Parser\AtomParser;
use Icinga\Module\RSS\Parser\FeedType;

use \SimpleXMLElement;
use \Exception;

class RSSReader
{
    public function __construct(
        protected string $url,
        protected FeedType $type = FeedType::Auto,
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

    protected function parse(string $rawResponse): ?RSSChannel
    {
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

                throw new Exception('Invalid or unsupported feed');
                break;
            case FeedType::RSS: return RSSParser::parse($rawResponse);
            case FeedType::Atom: return AtomParser::parse($rawResponse);
            default:
                throw new Exception('Unreachable code');
        }
    
        throw new Exception('Unreachable code');
    }

    public function fetch(): ?RSSChannel {
        [$headers, $rawResponse] = $this->fetchRaw();

        // FIXME: This assumes the request was successful

        return $this->parse($rawResponse);
    }
}
