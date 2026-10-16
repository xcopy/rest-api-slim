<?php

declare(strict_types=1);

use App\Validators\AuthValidator;
use App\Validators\EmailVerificationValidator;
use App\Validators\PasswordValidator;
use App\Validators\UserValidator;
use App\Validators\Validator;

it('validates emails consistently across validators', function (Validator $validator, array $data, string $scenario) {
    expect($validator->validate($data, $scenario))->toBeFalse();
    expect($validator->getErrors())->toHaveKey('email');
})->with([
    'login' => [new AuthValidator(), ['email' => 'invalid', 'password' => 'secret'], 'login'],
    'registration' => [
        new UserValidator(),
        [
            'first_name' => 'Ada',
            'last_name' => 'Lovelace',
            'email' => 'invalid',
            'password' => 'secret',
            'password_repeat' => 'secret',
        ],
        'create',
    ],
    'password reset request' => [new PasswordValidator(), ['email' => 'invalid'], 'forgot'],
    'verification email' => [new EmailVerificationValidator(), ['email' => 'invalid'], 'send'],
]);

it('keeps sign-in password validation separate from new-password requirements', function () {
    $authValidator = new AuthValidator();
    $passwordValidator = new PasswordValidator();

    expect($authValidator->validate(['email' => 'ada@example.com', 'password' => 'short'], 'login'))->toBeTrue();
    expect($passwordValidator->validate([
        'token' => 'token',
        'new_password' => 'short',
        'new_password_repeat' => 'short',
    ], 'reset'))->toBeFalse();
    expect($passwordValidator->getErrors())->toHaveKey('new_password');
});

it('validates new passwords and their repeated value', function () {
    $validator = new PasswordValidator();

    expect($validator->validate([
        'token' => 'token',
        'new_password' => 'long-enough',
        'new_password_repeat' => 'different',
    ], 'reset'))->toBeFalse();
    expect($validator->getErrors())->toHaveKey('new_password_repeat');
    expect($validator->getErrors()['new_password_repeat'])->toContain(
        'The value of "New password repeat" is not the same as expected value of "New password"'
    );
    expect(implode(' ', $validator->getErrors()['new_password_repeat']))
        ->not->toContain('long-enough')
        ->not->toContain('different');
});

it('accepts valid input for supported validator scenarios', function (
    Validator $validator,
    array $data,
    string $scenario
) {
    expect($validator->validate($data, $scenario))->toBeTrue();
    expect($validator->getErrors())->toBeEmpty();
})->with([
    'login' => [new AuthValidator(), ['email' => 'ada@example.com', 'password' => 'secret'], 'login'],
    'user update without credentials' => [
        new UserValidator(),
        ['first_name' => 'Ada', 'last_name' => 'Lovelace'],
        'update',
    ],
    'password reset request' => [
        new PasswordValidator(),
        ['email' => 'ada@example.com'],
        'forgot',
    ],
    'password reset' => [
        new PasswordValidator(),
        ['token' => 'reset-token', 'new_password' => 'long-enough', 'new_password_repeat' => 'long-enough'],
        'reset',
    ],
    'password change' => [
        new PasswordValidator(),
        [
            'current_password' => 'current-password',
            'new_password' => 'long-enough',
            'new_password_repeat' => 'long-enough',
        ],
        'change',
    ],
    'send verification email' => [
        new EmailVerificationValidator(),
        ['email' => 'ada@example.com'],
        'send',
    ],
    'verify email' => [new EmailVerificationValidator(), ['token' => 'verification-token'], 'verify'],
]);

it('validates user names and token types', function (Validator $validator, array $data, string $scenario, string $property) {
    expect($validator->validate($data, $scenario))->toBeFalse();
    expect($validator->getErrors())->toHaveKey($property);
})->with([
    'short first name' => [
        new UserValidator(),
        ['first_name' => 'Al', 'last_name' => 'Lovelace'],
        'update',
        'first_name',
    ],
    'long last name' => [
        new UserValidator(),
        ['first_name' => 'Ada', 'last_name' => str_repeat('a', 101)],
        'update',
        'last_name',
    ],
    'non-string password reset token' => [
        new PasswordValidator(),
        ['token' => 123, 'new_password' => 'long-enough', 'new_password_repeat' => 'long-enough'],
        'reset',
        'token',
    ],
    'non-string verification token' => [
        new EmailVerificationValidator(),
        ['token' => 123],
        'verify',
        'token',
    ],
]);

it('rejects unsupported validator scenarios', function (Validator $validator, string $scenario) {
    expect(fn() => $validator->validate([], $scenario))
        ->toThrow(InvalidArgumentException::class, "Unsupported scenario: $scenario");
})->with([
    'password' => [new PasswordValidator(), 'unknown'],
    'email verification' => [new EmailVerificationValidator(), 'unknown'],
]);
