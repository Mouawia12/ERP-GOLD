<?php
namespace App\Services\MigrateOld;

use App\Models\Account;
use App\Models\FinancialYear;
use App\Models\JournalEntry;
use Carbon\Carbon;

/**
 * A class defines zatca required integration defaults
 */
class JournalsMove
{
    public static function move()
    {
        $journals = collect(Variables::getArray('journals'))->where('basedon_no', '')->toArray();
        foreach ($journals as $journal) {
            $journal_entry = JournalEntry::create([
                'journal_date' => $journal['date'],
                'financial_year' => FinancialYear::where('is_active', true)->first()->id,
                'branch_id' => $journal['branch_id'],
            ]);
            $journalDocuments = collect(Variables::getArray('journal_details'))->where('journal_id', $journal['id'])->toArray();
            $lines = [];
            foreach ($journalDocuments ?? [] as $journalDocument) {
                $account = Account::where('old_id', $journalDocument['account_id'])->first();
                $lines[] = [
                    'account_id' => $account->id,
                    'debit' => $journalDocument['debit'],
                    'credit' => $journalDocument['credit'],
                    'document_date' => $journal['date'] ?? Carbon::now()->format('Y-m-d'),
                ];
            }
            $journal_entry->documents()->createMany($lines);
        }
    }
}
