<?php

declare(strict_types=1);

namespace App\Validators;

use App\Assert\Assert;
use Assert\LazyAssertion;

final class AuthValidator extends Validator
{
    /**
     * @inheritDoc
     */
    protected function lazyAssertion(array $data, string $scenario, array $context = []): LazyAssertion
    {
        $assertion = Assert::lazy();

        $this->assertEmail($assertion, $data['email'] ?? null);
        $this->assertPassword($assertion, $data['password'] ?? null);

        return $assertion;
    }
}
