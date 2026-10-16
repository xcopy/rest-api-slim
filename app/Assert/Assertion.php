<?php

declare(strict_types=1);

namespace App\Assert;

use Assert\Assertion as BaseAssertion;

class Assertion extends BaseAssertion
{
    public static function alphaDash(mixed $value, ?string $propertyPath = null): bool
    {
        return static::regex(
            $value,
            '/^[a-zA-Z0-9-]+$/',
            sprintf('The value of "%s" is not a valid alpha-dash string', humanize($propertyPath)),
            $propertyPath
        );
    }

    public static function notBlank($value, $message = null, ?string $propertyPath = null): bool
    {
        return parent::notBlank(
            $value,
            sprintf('%s cannot be blank', humanize($propertyPath)),
            $propertyPath
        );
    }

    public static function email($value, $message = null, ?string $propertyPath = null): bool
    {
        return parent::email(
            $value,
            sprintf('%s must be a valid email address', humanize($propertyPath)),
            $propertyPath
        );
    }

    public static function minLength($value, $minLength, $message = null, ?string $propertyPath = null, $encoding = 'utf8'): bool
    {
        return parent::minLength(
            $value,
            $minLength,
            sprintf('%s is too short, it should have at least %d characters', humanize($propertyPath), $minLength),
            $propertyPath,
            $encoding
        );
    }

    public static function maxLength($value, $maxLength, $message = null, ?string $propertyPath = null, $encoding = 'utf8'): bool
    {
        return parent::maxLength(
            $value,
            $maxLength,
            sprintf('%s is too long, it should have no more than %d characters', humanize($propertyPath), $maxLength),
            $propertyPath,
            $encoding
        );
    }

    public static function same($value, $value2, $message = null, ?string $propertyPath = null): bool
    {
        $expectedPropertyPath = str_ends_with($propertyPath, '_repeat')
            ? sprintf('expected value of "%s"', humanize(substr($propertyPath, 0, -strlen('_repeat'))))
            : 'expected value';

        return parent::same(
            $value,
            $value2,
            sprintf(
                'The value of "%s" is not the same as %s',
                humanize($propertyPath),
                $expectedPropertyPath
            ),
            $propertyPath
        );
    }
}
