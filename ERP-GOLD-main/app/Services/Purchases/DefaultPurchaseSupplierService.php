<?php

namespace App\Services\Purchases;

use App\Models\Customer;
use App\Models\SystemSetting;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

class DefaultPurchaseSupplierService
{
    public const SETTING_KEY = 'default_purchase_supplier_id';

    /**
     * @return Collection<int, Customer>
     */
    public function supplierOptions(?User $user): Collection
    {
        return Customer::query()
            ->visibleToUser($user)
            ->where('type', 'supplier')
            ->orderBy('name')
            ->get();
    }

    public function currentSupplierId(?User $user): ?int
    {
        $supplierId = $this->storedSupplierId();

        if ($supplierId === null) {
            return null;
        }

        return $this->supplierIsVisibleToUser($user, $supplierId) ? $supplierId : null;
    }

    public function setSupplierId(?int $supplierId): void
    {
        SystemSetting::putValue(self::SETTING_KEY, $supplierId ? (string) $supplierId : '');
    }

    public function supplierIsVisibleToUser(?User $user, int $supplierId): bool
    {
        return Customer::query()
            ->visibleToUser($user)
            ->where('type', 'supplier')
            ->whereKey($supplierId)
            ->exists();
    }

    private function storedSupplierId(): ?int
    {
        $value = SystemSetting::getValue(self::SETTING_KEY, '');

        if (! is_numeric($value) || (int) $value <= 0) {
            return null;
        }

        return (int) $value;
    }
}
