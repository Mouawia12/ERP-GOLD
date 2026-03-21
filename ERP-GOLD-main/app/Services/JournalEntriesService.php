<?php
namespace App\Services;

use App\Models\JournalEntry;

class JournalEntriesService
{
    public static function invoiceGenerateJournalEntries($invoice, $journalLines)
    {
        if (count($journalLines) == 0) {
            return;
        }
        $journalEntry = $invoice->journalEntry()->create([
            'journal_date' => $invoice->date,
            'financial_year' => $invoice->financial_year,
            'branch_id' => $invoice->branch_id,
        ]);

        $journalEntry->documents()->createMany($journalLines);
    }
}
