<?php

declare(strict_types=1);

use App\Repositories\UserRepository;

it('returns a stable, bounded page of repository results', function () {
    $repository = $this->app->getContainer()->get(UserRepository::class);
    $emails = array_map(
        fn() => sprintf('repository-page-%s@example.com', bin2hex(random_bytes(8))),
        range(1, 3)
    );

    foreach ($emails as $email) {
        $repository->insert([
            'email' => $email,
            'password' => hashPassword('test-password'),
            'first_name' => 'Ada',
            'last_name' => 'Lovelace',
            'created_at' => now(true),
            'updated_at' => now(true),
        ]);
    }

    try {
        $firstPage = $repository->findAll(limit: 2);
        $secondPage = $repository->findAll(limit: 2, offset: 2);

        expect($firstPage)->toHaveCount(2);
        expect($secondPage)->not->toBeEmpty();

        $firstPageIds = array_column($firstPage, 'id');
        $secondPageIds = array_column($secondPage, 'id');
        expect(max($firstPageIds))->toBeLessThan(min($secondPageIds));
    } finally {
        foreach ($emails as $email) {
            $repository->delete(['email' => $email]);
        }
    }
});

it('rejects invalid repository limits and offsets', function (int $limit, int $offset) {
    $repository = $this->app->getContainer()->get(UserRepository::class);

    expect(fn() => $repository->findAll(limit: $limit, offset: $offset))
        ->toThrow(InvalidArgumentException::class);
})->with([
    'zero limit' => [0, 0],
    'negative limit' => [-1, 0],
    'oversized limit' => [101, 0],
    'negative offset' => [10, -1],
]);
