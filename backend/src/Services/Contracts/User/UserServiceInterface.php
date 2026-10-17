<?php

declare(strict_types=1);

namespace App\Services\Contracts\User;

use App\Domain\User;
use App\Exceptions\generalExceptions\ConflictException;
use App\Exceptions\generalExceptions\NotFoundException;
use App\Exceptions\Validation\ValidationException;

/**
 * The logged-in user's own account (FR-AUTH-07 … FR-AUTH-10).
 * Reading the profile (`GET /me`) stays in AuthServiceInterface::currentUser().
 */
interface UserServiceInterface
{
    /**
     * Updates name, email and timezone (FR-AUTH-08).
     *
     * - The email is trimmed and lower-cased before it is stored.
     * - The email must not belong to another user (case-insensitive).
     * - The timezone must be a valid IANA identifier (e.g. `Europe/Prague`), checked against
     *   \DateTimeZone::listIdentifiers(), because week boundaries and statistics are computed in it.
     *
     * @throws NotFoundException  the user no longer exists
     * @throws ValidationException the email is already taken (`email` field error) or the timezone is invalid
     */
    public function updateProfile(int $userId, string $name, string $email, string $timezone): User;

    /**
     * Changes the password (FR-AUTH-09).
     *
     * - $currentPassword must match the stored hash, otherwise nothing changes.
     * - The new password is hashed with PASSWORD_ARGON2ID (NFR-06).
     * - All of the user's refresh tokens are revoked, so other devices are logged out;
     *   the caller gets a fresh token pair to stay logged in on the current device.
     *
     * @return array{token: string, refresh_token: string, user: User}
     *
     * @throws ValidationException the current password is wrong (`current_password` field error)
     * @throws NotFoundException   the user no longer exists
     */
    public function changePassword(int $userId, string $currentPassword, string $newPassword): array;

    /**
     * Permanently deletes the account and all its data – subcategories, events, notes,
     * to-do lists and refresh tokens are removed by ON DELETE CASCADE (FR-AUTH-10).
     *
     * @throws ValidationException the password is wrong (`password` field error)
     * @throws ConflictException   the user is the last admin – the system would have no admin left
     * @throws NotFoundException   the user no longer exists
     */
    public function deleteAccount(int $userId, string $password): void;
}
