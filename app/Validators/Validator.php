<?php

declare(strict_types=1);

namespace App\Validators;

use Assert\LazyAssertion;
use Assert\LazyAssertionException;

abstract class Validator
{
    /** @var array<string, string|list<string>> */
    private array $errors = [];

    /**
     * @param array<string, mixed> $data
     * @param array<string, mixed> $context
     */
    abstract protected function lazyAssertion(array $data, string $scenario, array $context = []): LazyAssertion;

    protected function assertName(
        LazyAssertion $lazyAssertion,
        mixed $value,
        string $property
    ): void {
        $lazyAssertion
            ->that($value, $property)
            ->notBlank()
            ->alphaDash() // @phpstan-ignore method.notFound
            ->minLength(3)
            ->maxLength(100);
    }

    protected function assertEmail(
        LazyAssertion $lazyAssertion,
        mixed $value,
        string $property = 'email'
    ): void {
        $lazyAssertion
            ->that($value, $property)
            ->notBlank()
            ->email();
    }

    protected function assertPassword(
        LazyAssertion $lazyAssertion,
        mixed $value,
        string $property = 'password'
    ): void {
        $lazyAssertion
            ->that($value, $property)
            ->notBlank();
    }

    protected function assertToken(
        LazyAssertion $lazyAssertion,
        mixed $value,
        string $property = 'token'
    ): void {
        $lazyAssertion
            ->that($value, $property)
            ->notBlank()
            ->string();
    }

    /**
     * @param array<string, mixed> $data
     * @param array<string, mixed> $context
     */
    public function validate(array $data, string $scenario, array $context = []): bool
    {
        try {
            return $this
                ->lazyAssertion($data, $scenario, $context)
                ->verifyNow();
        } catch (LazyAssertionException $e) {
            foreach ($e->getErrorExceptions() as $error) {
                $this->errors[$error->getPropertyPath()][] = $error->getMessage();
            }
        }

        return false;
    }

    /** @return array<string, string|list<string>> */
    public function getErrors(): array
    {
        return $this->errors;
    }
}
