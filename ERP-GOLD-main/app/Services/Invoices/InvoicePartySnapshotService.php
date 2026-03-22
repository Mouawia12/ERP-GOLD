<?php

namespace App\Services\Invoices;

use App\Models\Customer;
use App\Models\Invoice;

class InvoicePartySnapshotService
{
    /**
     * @return array<string, string>
     */
    public function resolve(?Customer $party, ?string $name, ?string $phone, ?string $identityNumber = null): array
    {
        $payload = [];

        $resolvedName = $this->normalize($name) ?? $party?->name;
        $resolvedPhone = $this->normalize($phone) ?? $party?->phone;
        $resolvedIdentityNumber = $this->normalize($identityNumber) ?? $party?->identity_number;

        if ($resolvedName !== null) {
            $payload['bill_client_name'] = $resolvedName;
        }

        if ($resolvedPhone !== null) {
            $payload['bill_client_phone'] = $resolvedPhone;
        }

        if ($resolvedIdentityNumber !== null) {
            $payload['bill_client_identity_number'] = $resolvedIdentityNumber;
        }

        return $payload;
    }

    /**
     * @return array<string, string>
     */
    public function fromInvoice(Invoice $invoice): array
    {
        return $this->resolve(
            $invoice->customer,
            $invoice->bill_client_name,
            $invoice->bill_client_phone,
            $invoice->bill_client_identity_number,
        );
    }

    private function normalize(?string $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }
}
