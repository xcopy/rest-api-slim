<?php

declare(strict_types=1);

use App\Repositories\UserRepository;
use App\Services\UserService;

it('creates a user after validating and discarding password confirmation', function () {
    $email = sprintf('create-user-%s@example.com', bin2hex(random_bytes(8)));
    $userService = $this->app->getContainer()->get(UserService::class);
    $userRepository = $this->app->getContainer()->get(UserRepository::class);

    try {
        $user = $userService->create([
            'first_name' => 'Ada',
            'last_name' => 'Lovelace',
            'email' => $email,
            'password' => 'long-enough',
            'password_repeat' => 'long-enough',
        ]);

        expect($user['email'])->toBe($email);
        expect($user)->not->toHaveKey('password_repeat');
    } finally {
        $userRepository->delete(['email' => $email]);
    }
});
