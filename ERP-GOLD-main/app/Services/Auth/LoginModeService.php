<?php

namespace App\Services\Auth;

use App\Models\SystemSetting;
use App\Models\User;

class LoginModeService
{
    public const SETTING_KEY = 'login_mode';
    public const MODE_MULTI_DEVICE = 'multi_device';
    public const MODE_SINGLE_DEVICE = 'single_device';
    public const WEB_REFERENCE_PREFIX = 'web:';
    public const API_REFERENCE_PREFIX = 'api:';

    /**
     * @return array<int, string>
     */
    public function availableModes(): array
    {
        return [
            self::MODE_MULTI_DEVICE,
            self::MODE_SINGLE_DEVICE,
        ];
    }

    public function currentMode(): string
    {
        $mode = SystemSetting::getValue(self::SETTING_KEY, self::MODE_MULTI_DEVICE);

        return in_array($mode, $this->availableModes(), true)
            ? $mode
            : self::MODE_MULTI_DEVICE;
    }

    public function isSingleDeviceMode(): bool
    {
        return $this->currentMode() === self::MODE_SINGLE_DEVICE;
    }

    public function setMode(string $mode): void
    {
        SystemSetting::putValue(self::SETTING_KEY, $mode);
    }

    public function syncAuthenticatedSession(User $user, ?string $sessionId): void
    {
        if (!$this->isSingleDeviceMode() || blank($sessionId)) {
            return;
        }

        $user->forceFill([
            'active_session_id' => $this->formatReference(self::WEB_REFERENCE_PREFIX, $sessionId),
        ])->save();
    }

    public function clearAuthenticatedSession(?User $user, ?string $sessionId): void
    {
        if (!$user || blank($sessionId)) {
            return;
        }

        if (!$this->matchesReference($user->active_session_id, self::WEB_REFERENCE_PREFIX, $sessionId)) {
            return;
        }

        $user->forceFill([
            'active_session_id' => null,
        ])->save();
    }

    public function isRequestSessionValid(User $user, ?string $sessionId): bool
    {
        if (!$this->isSingleDeviceMode()) {
            return true;
        }

        if (blank($user->active_session_id) || blank($sessionId)) {
            return true;
        }

        return $this->matchesReference($user->active_session_id, self::WEB_REFERENCE_PREFIX, $sessionId);
    }

    public function syncApiToken(User $user, ?int $tokenId): void
    {
        if (!$this->isSingleDeviceMode() || blank($tokenId)) {
            return;
        }

        $user->forceFill([
            'active_session_id' => $this->formatReference(self::API_REFERENCE_PREFIX, (string) $tokenId),
        ])->save();
    }

    public function clearApiToken(?User $user, ?int $tokenId): void
    {
        if (!$user || blank($tokenId)) {
            return;
        }

        if (!$this->matchesReference($user->active_session_id, self::API_REFERENCE_PREFIX, (string) $tokenId)) {
            return;
        }

        $user->forceFill([
            'active_session_id' => null,
        ])->save();
    }

    public function isApiTokenValid(User $user, ?int $tokenId): bool
    {
        if (!$this->isSingleDeviceMode()) {
            return true;
        }

        if (blank($user->active_session_id) || blank($tokenId)) {
            return true;
        }

        return $this->matchesReference($user->active_session_id, self::API_REFERENCE_PREFIX, (string) $tokenId);
    }

    private function formatReference(string $prefix, string $value): string
    {
        return $prefix . $value;
    }

    private function matchesReference(?string $storedValue, string $prefix, string $value): bool
    {
        if (blank($storedValue)) {
            return false;
        }

        $expectedValue = $this->formatReference($prefix, $value);

        return hash_equals($storedValue, $expectedValue) || hash_equals($storedValue, $value);
    }
}
