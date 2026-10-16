<?php

declare(strict_types=1);

namespace App\Validators;

use App\Assert\Assert;
use Assert\LazyAssertion;
use InvalidArgumentException;

final class PasswordValidator extends Validator
{
    /**
     * @inheritDoc
     */
    protected function lazyAssertion(array $data, string $scenario, array $context = []): LazyAssertion
    {
        $lazyAssertion = Assert::lazy();

        if ($scenario === 'forgot') {
            $this->assertEmail($lazyAssertion, $data['email'] ?? null);
            return $lazyAssertion;
        }

        if ($scenario === 'reset') {
            $this->assertToken($lazyAssertion, $data['token'] ?? null);
        } elseif ($scenario === 'change') {
            $this->assertPassword(
                $lazyAssertion,
                $data['current_password'] ?? null,
                'current_password'
            );
        } else {
            throw new InvalidArgumentException(sprintf('Unsupported scenario: %s', $scenario));
        }

        $this->assertPassword(
            $lazyAssertion,
            $data['new_password'] ?? null,
            'new_password'
        );
        $lazyAssertion->minLength(8);

        $this->assertPassword(
            $lazyAssertion,
            $data['new_password_repeat'] ?? null,
            'new_password_repeat'
        );
        $lazyAssertion->same($data['new_password'] ?? null);

        return $lazyAssertion;
    }
}
