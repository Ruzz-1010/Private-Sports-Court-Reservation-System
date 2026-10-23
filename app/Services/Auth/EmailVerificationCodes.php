<?php

namespace App\Services\Auth;

use App\Models\User;
use Illuminate\Support\Facades\Cache;

/**
 * Six-digit email verification codes.
 *
 * Codes are kept in the cache, never in the database, so no migration is
 * needed and an expired code disappears on its own. Only a hash of the code is
 * stored, the same way a password is stored: someone who can read the cache
 * still cannot log in with what they found. A code is burned after the
 * configured number of wrong guesses, so a six-digit code cannot be beaten by
 * guessing.
 */
class EmailVerificationCodes
{
    /**
     * Create a fresh code for the user, store its hash, and return the plain
     * code so the caller can email it.
     *
     * Any earlier code is replaced, so only the newest email works.
     */
    public function issue(User $user): string
    {
        $length = max(4, min(8, (int) config('reservation.email_verification.code_length', 6)));
        $code = $this->randomDigits($length);

        Cache::put($this->codeKey($user), [
            'hash' => hash('sha256', $code),
            'attempts' => 0,
        ], now()->addMinutes($this->expiresMinutes()));

        Cache::put($this->sentKey($user), now()->getTimestamp(), now()->addMinutes($this->expiresMinutes()));

        return $code;
    }

    /**
     * Check a submitted code.
     *
     * Returns true only once: a correct code is deleted, and a wrong one counts
     * against the attempt limit.
     */
    public function verify(User $user, string $code): bool
    {
        $record = Cache::get($this->codeKey($user));

        if (! is_array($record) || ! isset($record['hash'])) {
            return false;
        }

        if (hash_equals($record['hash'], hash('sha256', trim($code)))) {
            $this->forget($user);

            return true;
        }

        $attempts = (int) ($record['attempts'] ?? 0) + 1;

        if ($attempts >= $this->maxAttempts()) {
            $this->forget($user);

            return false;
        }

        $record['attempts'] = $attempts;
        Cache::put($this->codeKey($user), $record, now()->addMinutes($this->expiresMinutes()));

        return false;
    }

    /**
     * How many seconds must pass before another code may be requested.
     *
     * Returns 0 when a new code may be requested right now.
     */
    public function secondsUntilResend(User $user): int
    {
        $sentAt = Cache::get($this->sentKey($user));

        if (! is_int($sentAt) && ! is_numeric($sentAt)) {
            return 0;
        }

        $wait = ((int) $sentAt + $this->resendSeconds()) - now()->getTimestamp();

        return max(0, $wait);
    }

    public function forget(User $user): void
    {
        Cache::forget($this->codeKey($user));
        Cache::forget($this->sentKey($user));
    }

    private function codeKey(User $user): string
    {
        return 'email-verification:'.$user->getKey().':'.sha1($user->email);
    }

    private function sentKey(User $user): string
    {
        return 'email-verification-sent:'.$user->getKey();
    }

    private function expiresMinutes(): int
    {
        return max(1, (int) config('reservation.email_verification.expires_minutes', 15));
    }

    private function maxAttempts(): int
    {
        return max(1, (int) config('reservation.email_verification.max_attempts', 5));
    }

    private function resendSeconds(): int
    {
        return max(0, (int) config('reservation.email_verification.resend_seconds', 60));
    }

    /**
     * Random digits from a cryptographic source, padded so leading zeros stay.
     */
    private function randomDigits(int $length): string
    {
        $digits = '';

        for ($i = 0; $i < $length; $i++) {
            $digits .= (string) random_int(0, 9);
        }

        return $digits;
    }
}
