<?php

declare(strict_types=1);

namespace App\Services\User;

use App\Domain\Role;
use App\Domain\User;
use App\Exceptions\generalExceptions\ConflictException;
use App\Exceptions\generalExceptions\NotFoundException;
use App\Exceptions\Validation\ValidationException;
use App\Repositories\Contracts\RefreshTokenRepositoryInterface;
use App\Repositories\Contracts\UserRepositoryInterface;
use App\Services\Contracts\Auth\AuthServiceInterface;
use App\Services\Contracts\User\UserServiceInterface;
use DateTimeZone;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;

final readonly class UserService implements UserServiceInterface
{
    public function __construct(
        private Connection $connection,
        private UserRepositoryInterface $users,
        private RefreshTokenRepositoryInterface $refreshTokens,
        private AuthServiceInterface $authService,
    ) {
    }

    public function updateProfile(
        int $userId,
        ?string $name = null,
        ?string $email = null,
        ?string $timezone = null,
    ): User {
        $current = $this->users->findById($userId) ?? throw new NotFoundException('not.found.user');

        $name     = $name !== null ? $this->validName($name) : $current->name;
        $email    = $email !== null ? strtolower(trim($email)) : $current->email;
        $timezone = $timezone !== null ? $this->validTimezone($timezone) : $current->timezone;

        if ($this->users->existsByEmail($email, $userId)) {
            throw new ValidationException(['email' => 'auth.register.email_taken']);
        }

        try {
            return $this->users->updateProfile($userId, $name, $email, $timezone)
                ?? throw new NotFoundException('not.found.user');
        } catch (UniqueConstraintViolationException) {
            // another account took the email between the check and the update
            throw new ValidationException(['email' => 'auth.register.email_taken']);
        }
    }

    /** @return array{token: string, refresh_token: string, user: User} */
    public function changePassword(int $userId, string $currentPassword, string $newPassword): array
    {
        if (!password_verify($currentPassword, $this->passwordHash($userId))) {
            throw new ValidationException(['current_password' => 'user.password.invalid']);
        }

        return $this->connection->transactional(function () use ($userId, $newPassword): array {
            $this->users->updatePassword($userId, password_hash($newPassword, PASSWORD_ARGON2ID));

            // log out every device, then keep the current one logged in with a fresh pair
            $this->refreshTokens->revokeAllForUser($userId);

            $user = $this->users->findById($userId) ?? throw new NotFoundException('not.found.user');

            return [...$this->authService->issueTokens($user), 'user' => $user];
        });
    }

    public function deleteAccount(int $userId, string $password): void
    {
        if (!password_verify($password, $this->passwordHash($userId))) {
            throw new ValidationException(['password' => 'user.password.invalid']);
        }

        $user = $this->users->findById($userId) ?? throw new NotFoundException('not.found.user');

        if ($user->role === Role::ADMIN && $this->users->countByRole(Role::ADMIN) <= 1) {
            throw new ConflictException('user.delete.last_admin');
        }

        if (!$this->users->delete($userId)) {
            throw new NotFoundException('not.found.user');
        }
    }

    private function passwordHash(int $userId): string
    {
        return $this->users->findPasswordHash($userId) ?? throw new NotFoundException('not.found.user');
    }

    /** Trimmed name, counted in characters. */
    private function validName(string $name): string
    {
        $name   = trim($name);
        $length = mb_strlen($name);

        if ($length < User::NAME_MIN_LENGTH || $length > User::NAME_MAX_LENGTH) {
            throw new ValidationException(['name' => 'user.name_length']);
        }

        return $name;
    }

    /** IANA identifier – week boundaries and statistics are computed in it. */
    private function validTimezone(string $timezone): string
    {
        $timezone = trim($timezone);

        if (!in_array($timezone, DateTimeZone::listIdentifiers(), true)) {
            throw new ValidationException(['timezone' => 'user.timezone_invalid']);
        }

        return $timezone;
    }
}
