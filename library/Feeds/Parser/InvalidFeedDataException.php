<?php

namespace Icinga\Module\Feeds\Parser;

use Icinga\Exception\IcingaException;

/**
 * InvalidFeedDataException is a custom Exception we use in the feed auto detection
 * to differentiate between errors detecting a feed type and a parsing error.
 * Use InvalidFeedDataException to communicate parsing issues to the user.
 */
class InvalidFeedDataException extends IcingaException
{
}
