<?php

declare(strict_types=1);

namespace App\Validators;

use App\Assert\Assert;
use App\Repositories\UserRepository;
use Assert\LazyAssertion;
use DI\Attribute\Inject;

final class UserValidator extends Validator
{
    #[Inject]
    private UserRepository $userRepository;

    /**
     * @inheritDoc
     */
    protected function lazyAssertion(array $data, string $scenario, array $context = []): LazyAssertion
    {
        $lazyAssertion = Assert::lazy();

        foreach (['first_name', 'last_name'] as $key) {
            $this->assertName($lazyAssertion, $data[$key] ?? null, $key);
        }

        if ($scenario === 'create') {
            // DO NOT change the order
            $this->assertEmail($lazyAssertion, $data['email'] ?? null);
            $lazyAssertion->satisfy(
                fn(string $email) => $this->userRepository->findOne(compact('email')) === null,
                'This email address has already been taken'
            );

            $this->assertPassword($lazyAssertion, $data['password'] ?? null);
            $lazyAssertion->minLength(8);

            $this->assertPassword($lazyAssertion, $data['password_repeat'] ?? null, 'password_repeat');
            $lazyAssertion->same($data['password'] ?? null);
        }

        return $lazyAssertion;
    }
}
