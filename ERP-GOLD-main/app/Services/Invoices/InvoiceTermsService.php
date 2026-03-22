<?php

namespace App\Services\Invoices;

use App\Models\SystemSetting;

class InvoiceTermsService
{
    public const SETTING_KEY = 'default_invoice_terms';

    public function defaultTerms(): string
    {
        return $this->normalize(SystemSetting::getValue(self::SETTING_KEY, ''));
    }

    public function setDefaultTerms(?string $terms): void
    {
        SystemSetting::putValue(self::SETTING_KEY, $this->normalize($terms));
    }

    public function resolveSnapshot(?string $terms, bool $fieldProvided = true): ?string
    {
        if (! $fieldProvided) {
            return $this->emptyToNull($this->defaultTerms());
        }

        return $this->emptyToNull($this->normalize($terms));
    }

    private function normalize(?string $terms): string
    {
        $terms = str_replace(["\r\n", "\r"], "\n", (string) $terms);

        return trim($terms);
    }

    private function emptyToNull(string $terms): ?string
    {
        return $terms === '' ? null : $terms;
    }
}
