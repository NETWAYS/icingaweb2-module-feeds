<?php

namespace Icinga\Module\Feeds\Parser;

use Exception;

enum FeedType: int
{
    case Auto = 0;
    case RSS = 1;
    case Atom = 2;
    case Jsonfeed = 3;

    public function display(): string
    {
        return match ($this) {
            self::Auto => 'auto',
            self::RSS => 'rss',
            self::Atom => 'atom',
            self::Jsonfeed => 'jsonfeed',
            default => throw new Exception('Unreachable code')
        };
    }

    public static function fromDisplay(string $display): static
    {
        return match ($display) {
            'auto' => self::Auto,
            'rss' => self::RSS,
            'atom' => self::Atom,
            'jsonfeed' => self::Jsonfeed,
            default => throw new Exception('Invalid FeedType')
        };
    }

    public static function all(): array
    {
        return array_column(self::cases(), 'name');
    }
}
