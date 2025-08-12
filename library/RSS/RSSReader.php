<?php

namespace Icinga\Module\RSS;

use Icinga\Module\RSS\Parser\Result\RSSChannel;
use Icinga\Module\RSS\Parser\RSSParser;

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

        return RSSParser::parse($rawResponse);
    }
}
