<?php


namespace Melodyx\Ajax\Twig;


class PieceIdentifierCollector
{
    /** @var string[] */
    private static array $identifiers = [];

    public static function hasIdentifier(string $name): bool
    {
        return array_search($name, self::$identifiers) !== false;
    }

    public static function addIdentifier(string $name): void
    {
        self::$identifiers[] = $name;
    }

    /**
     * @return string[]
     */
    public static function getIdentifiers(): array
    {
        return self::$identifiers;
    }
}