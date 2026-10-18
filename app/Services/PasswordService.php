<?php

declare(strict_types=1);

namespace App\Services;

use App\Concerns\HasFactory;
use App\Db\Adapter;
use App\Exceptions\BadRequestException;
use App\Exceptions\NotFoundException;
use App\Repositories\AccessTokenRepository;
use App\Repositories\PasswordResetTokenRepository;
use App\Repositories\UserRepository;
use App\Services\Concerns\ValidatesData;
use App\Validators\PasswordValidator;
use DI\Attribute\Inject;
use Slim\Interfaces\RouteParserInterface;

final class PasswordService
{
    use HasFactory;
    use ValidatesData;

    #[Inject]
    private Adapter $db;

    #[Inject]
    private EmailService $email;

    #[Inject]
    private PasswordResetTokenRepository $repository;

    #[Inject]
    private AccessTokenRepository $accessTokenRepository;

    #[Inject]
    private UserRepository $userRepository;

    #[Inject]
    private RouteParserInterface $routeParser;

    protected function validatorClass(): string
    {
        return PasswordValidator::class;
    }

    /**
     * @param array<string, mixed> $data
     * @param-out string $token
     */
    public function sendReset(array $data, ?string &$token = null): void
    {
        $data = $this->validate($data, 'forgot');

        $email = $data['email'];

        $user = $this->userRepository->findOne(compact('email'));

        if ($user === null) {
            throw new NotFoundException('User with this email not found');
        }

        $token = generateToken();
        $expiresAt = now()->modify(config('password.reset_ttl'));

        $this->db->transaction(function () use ($user, $token, $expiresAt) {
            $this->repository->delete(['user_id' => $user['id']]);
            $this->repository->insert([
                'user_id' => $user['id'],
                'token_hash' => hashToken($token),
                'expires_at' => $expiresAt->format(DATE_FORMAT_DB),
            ]);
        });

        $url = rtrim(config('app.base_url'), '/') . $this->routeParser->urlFor('password.reset');

        $message = $this->email
            ->compose()
            ->to($email)
            ->subject('Reset your password')
            ->text(sprintf(
                "Hello,\n\nTo reset your password, send a POST request to:\n%s\n\n"
                    . "with this JSON body:\n{\"token\":\"%s\",\"new_password\":\"<new password>\","
                    . "\"new_password_repeat\":\"<new password>\"}\n\n"
                    . "This token expires at %s.\n",
                $url,
                $token,
                $expiresAt->format(DATE_ATOM)
            ));

        $this->email->send($message);
    }

    /** @param array<string, mixed> $data */
    public function reset(array $data): bool
    {
        $data = $this->validate($data, 'reset');

        $where = [
            'token_hash' => hashToken($data['token']),
            'expires_at > ?' => now(true),
        ];

        $resetToken = $this->repository->findOne($where);

        if ($resetToken === null) {
            throw new BadRequestException('The password reset token is invalid or expired');
        }

        $userId = $resetToken['user_id'];

        return $this->db->transaction(function () use ($data, $where, $userId) {
            $consumed = $this->repository->delete($where);

            if ($consumed !== 1) {
                throw new BadRequestException('The password reset token is invalid or expired');
            }

            return $this->updatePassword($userId, $data['new_password']);
        });
    }

    /** @param array<string, mixed> $data */
    public function change(int $userId, array $data): bool
    {
        $user = $this->userRepository
            ->addColumns(['password'])
            ->findOne($userId);

        if ($user === null) {
            throw new NotFoundException();
        }

        $data = $this->validate($data, 'change');

        $password = $user['password'];
        $currentPassword = $data['current_password'];
        $newPassword = $data['new_password'];

        if (!checkPassword($currentPassword, $password)) {
            throw new BadRequestException('Current password is incorrect');
        }

        if (checkPassword($newPassword, $password)) {
            throw new BadRequestException('New password must be different from the current password');
        }

        return $this->db->transaction(function () use ($userId, $newPassword) {
            return $this->updatePassword($userId, $newPassword);
        });
    }

    protected function updatePassword(int $userId, string $password): bool
    {
        $now = now(true);

        $result = $this->userRepository
            ->update(
                [
                    'password' => hashPassword($password),
                    'password_changed_at' => $now,
                    'updated_at' => $now,
                ],
                ['id' => $userId]
            );

        if ($result !== 1) {
            return false;
        }

        $this->accessTokenRepository->delete(['user_id' => $userId]);

        return true;
    }
}
