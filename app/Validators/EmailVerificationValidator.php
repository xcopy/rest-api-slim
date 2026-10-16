<?php

declare(strict_types=1);

namespace App\Validators;

use App\Assert\Assert;
use Assert\LazyAssertion;
use InvalidArgumentException;

final class EmailVerificationValidator extends Validator
{
    /**
     * @inheritDoc
     */
    protected function lazyAssertion(array $data, string $scenario, array $context = []): LazyAssertion
    {
        $lazyAssertion = Assert::lazy();

        match ($scenario) {
            'send' => $this->assertEmail($lazyAssertion, $data['email'] ?? null),
            'verify' => $this->assertToken($lazyAssertion, $data['token'] ?? null),
            default => throw new InvalidArgumentException(sprintf('Unsupported scenario: %s', $scenario)),
        };

        return $lazyAssertion;
    }
}
