<?php

declare(strict_types=1);

use App\Repositories\UserRepository;
use App\Validators\UserValidator;

it('rejects an email address that is already registered', function () {
    $email = sprintf('duplicate-%s@example.com', bin2hex(random_bytes(8)));
    $userRepository = $this->app->getContainer()->get(UserRepository::class);
    $userRepository->insert([
        'email' => $email,
        'password' => hashPassword('test-password'),
        'first_name' => 'Ada',
        'last_name' => 'Lovelace',
        'created_at' => now(true),
        'updated_at' => now(true),
    ]);

    try {
        $validator = $this->app->getContainer()->get(UserValidator::class);

        expect($validator->validate([
            'first_name' => 'Ada',
            'last_name' => 'Lovelace',
            'email' => $email,
            'password' => 'long-enough',
            'password_repeat' => 'long-enough',
        ], 'create'))->toBeFalse();
        expect($validator->getErrors())->toHaveKey('email');
        expect($validator->getErrors()['email'])->toContain('This email address has already been taken');
    } finally {
        $userRepository->delete(compact('email'));
    }
});

it('accepts a unique email and validates the registration password', function () {
    $validator = $this->app->getContainer()->get(UserValidator::class);
    $email = sprintf('unique-%s@example.com', bin2hex(random_bytes(8)));

    expect($validator->validate([
        'first_name' => 'Ada',
        'last_name' => 'Lovelace',
        'email' => $email,
        'password' => 'long-enough',
        'password_repeat' => 'long-enough',
    ], 'create'))->toBeTrue();
    expect($validator->getErrors())->toBeEmpty();

    $validator = $this->app->getContainer()->get(UserValidator::class);
    $email = sprintf('short-password-%s@example.com', bin2hex(random_bytes(8)));

    expect($validator->validate([
        'first_name' => 'Ada',
        'last_name' => 'Lovelace',
        'email' => $email,
        'password' => 'short',
        'password_repeat' => 'short',
    ], 'create'))->toBeFalse();
    expect($validator->getErrors())->toHaveKey('password');
});

it('requires registration password confirmation to match', function (array $data) {
    $validator = $this->app->getContainer()->get(UserValidator::class);
    $data['email'] = sprintf('confirmation-%s@example.com', bin2hex(random_bytes(8)));

    expect($validator->validate($data, 'create'))->toBeFalse();
    expect($validator->getErrors())->toHaveKey('password_repeat');
})->with([
    'missing confirmation' => [
        [
            'first_name' => 'Ada',
            'last_name' => 'Lovelace',
            'password' => 'long-enough',
        ],
    ],
    'mismatched confirmation' => [
        [
            'first_name' => 'Ada',
            'last_name' => 'Lovelace',
            'password' => 'long-enough',
            'password_repeat' => 'other-long-password',
        ],
    ],
]);
