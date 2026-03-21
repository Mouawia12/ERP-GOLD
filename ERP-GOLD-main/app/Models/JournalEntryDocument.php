<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class JournalEntryDocument extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    public function account()
    {
        return $this->belongsTo(Account::class);
    }

    public function journal_entry()
    {
        return $this->belongsTo(JournalEntry::class, 'journal_id');
    }
}
