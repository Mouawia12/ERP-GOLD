<?php

use App\Models\BankAccount;
use App\Models\Branch;
use App\Models\Subscriber;
use App\Services\Accounts\SubscriberChartProvisioner;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        $provisioner = app(SubscriberChartProvisioner::class);

        Subscriber::query()
            ->with('branches')
            ->each(function (Subscriber $subscriber) use ($provisioner) {
                $provisioner->ensureProvisioned($subscriber);

                $subscriber->branches->each(function (Branch $branch) use ($subscriber, $provisioner) {
                    $provisioner->ensureBranchAccountSettings($subscriber, $branch);

                    BankAccount::query()
                        ->withoutGlobalScopes()
                        ->where('branch_id', $branch->id)
                        ->get()
                        ->each(function (BankAccount $bankAccount) use ($subscriber, $provisioner) {
                            $bankAccount->update([
                                'subscriber_id' => $subscriber->id,
                                'ledger_account_id' => $provisioner->remapLedgerAccountForSubscriber($subscriber, $bankAccount->ledger_account_id)
                                    ?? $bankAccount->ledger_account_id,
                            ]);
                        });
                });
            });
    }

    public function down(): void
    {
    }
};
