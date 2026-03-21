<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class ZatcaDocument extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    public static function boot()
    {
        parent::boot();
        static::creating(function (ZatcaDocument $document) {
            $last_document = ZatcaDocument::where('branch_id', $document->branch_id)->where('sent_to_zatca', true)->orderBy('id', 'desc')->first();
            $document->{'icv'} = ($last_document) ? $last_document->icv + 1 : 1;
            $document->{'uuid'} = Str::uuid();
        });
    }

    public function getPihAttribute()
    {
        $last_document = ZatcaDocument::where('branch_id', $this->branch_id)->where('sent_to_zatca', true)->where('id', '<', $this->id)->orderBy('id', 'desc')->first();
        return $last_document->hash ?? base64_encode(hash('sha256', '0', true));
    }

    public function invoiceable()
    {
        return $this->morphTo();
    }
}
