<?php

namespace Icinga\Module\Feeds\Parser;

use Icinga\Exception\IcingaException;

/**
 * InvalidFeedTypeException is a custom Exception we use in the feed auto detection
 * to differentiate between errors detecting a feed type and a parsing error.
 * Use InvalidFeedTypeException to tell the auto detection to try the next parser.
 */
class InvalidFeedTypeException extends IcingaException
{
}
