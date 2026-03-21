<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class JournalEntry extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    public static function boot()
    {
        parent::boot();
        static::creating(function ($model) {
            $checkManual = empty($model->journalable_type) ? true : false;
            $invoiceCount = JournalEntry::where('journalable_type', $model->journalable_type)->where('branch_id', $model->branch_id)->count();
            $branch = $model->branch;
            if ($checkManual) {
                $prefix = 'MJ';
            } else {
                $prefix = 'J';
            }
            $newNumer = str_pad($invoiceCount + 1, 5, '0', STR_PAD_LEFT);
            $model->serial = $prefix . '-' . $branch->id . '-' . $newNumer;
        });
    }

    public function documents()
    {
        return $this->hasMany(JournalEntryDocument::class, 'journal_id');
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function journalable()
    {
        return $this->morphTo();
    }

    public function getCustomNotesAttribute($value)
    {
        return is_null($this->journalable_type) ? __('main.manual_journal', ['journal_id' => $this->id]) : $this->journalable->notes;
    }
}
