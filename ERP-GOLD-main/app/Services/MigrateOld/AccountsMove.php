<?php
namespace App\Services\MigrateOld;

use App\Models\Account;
use Illuminate\Support\Facades\DB;

/**
 * A class defines zatca required integration defaults
 */
class AccountsMove
{
    public static function move()
    {
        $accounts = collect(Variables::getArray('accounts_trees'))->toArray();
        usort($accounts, function ($a, $b) {
            if ($a['level'] == $b['level']) {
                return $a['parent_id'] <=> $b['parent_id'];
            }
            return $a['level'] <=> $b['level'];
        });
        foreach ($accounts as $account) {
            $parentAccount = Account::where('old_id', $account['parent_id'])->first();
            $account = Account::create([
                'old_id' => $account['id'],
                'name' => ['ar' => $account['name'], 'en' => $account['name']],
                'parent_account_id' => $parentAccount ? $parentAccount->id : null,
                'account_type' => 'not_have',
                'transfer_side' => 'not_have',
            ]);
        }
    }
}
