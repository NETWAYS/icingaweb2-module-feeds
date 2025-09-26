<?php

namespace Icinga\Module\Feeds\Web;

use Icinga\Web\Helper\HtmlPurifier;
use ipl\Html\HtmlString;

/**
 * Feed content represents arbitrary feed body.
 * The raw data of the body is escaped and formated for output in icingaweb2
 */
class FeedContent extends HtmlString
{
    protected const TEXT_PATTERNS = [
        '(\r?\n)',
    ];

    protected const TEXT_REPLACEMENTS = [
        "</br>",
    ];

    /**
     * Create a new FeedContent object
     * @param string $text the raw feed content data
     */
    public function __construct(string $text)
    {
        $text = HtmlPurifier::process($text);
        $text = preg_replace(self::TEXT_PATTERNS, self::TEXT_REPLACEMENTS, $text);
        $text = trim($text);

        // Add zero-width space after commas which are not followed by a whitespace character
        // in oder to help browsers to break words
        $text = preg_replace('/,(?=[^\s])/', ',&#8203;', $text);

        parent::__construct($text);
    }
}
