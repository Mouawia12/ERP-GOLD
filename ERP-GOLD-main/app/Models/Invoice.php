<?php

namespace App\Models;

use App\Services\Zatca\QRCodeString;
use Endroid\QrCode\Color\Color;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\Writer\PngWriter;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\RoundBlockSizeMode;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Invoice extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    public static function boot()
    {
        parent::boot();

        static::creating(function ($invoice) {
            $lastInvoice = Invoice::where('branch_id', $invoice->branch_id)->where('type', $invoice->type)->orderBy('id', 'desc')->first();
            $branch = $invoice->branch;
            $invoiceType = $invoice->type;
            $prefix = '';
            if ($invoiceType == 'sale') {
                $prefix = 'S';
            } elseif ($invoiceType == 'purchase') {
                $prefix = 'P';
            } elseif ($invoiceType == 'sale_return') {
                $prefix = 'SR';
            } elseif ($invoiceType == 'purchase_return') {
                $prefix = 'PR';
            } elseif ($invoiceType == 'initial_quantities') {
                $prefix = 'INQ';
            } elseif ($invoiceType == 'stock_settlements') {
                $prefix = 'SS';
            } elseif ($invoiceType == 'stock_movement') {
                $prefix = 'SM';
            }
            $invoiceCount = ($lastInvoice?->serial ?? 0) + 1;
            $newNumer = str_pad($invoiceCount, 5, '0', STR_PAD_LEFT);
            $invoice->bill_number = $prefix . '-' . $branch->id . '-' . $newNumer;
            $invoice->serial = $newNumer;
        });
    }

    public function details()
    {
        return $this->hasMany(InvoiceDetail::class);
    }

    public function getTotalQuantityAttribute()
    {
        return $this->details()->join('gold_carats', 'invoice_details.gold_carat_id', '=', 'gold_carats.id')->sum(DB::raw('(invoice_details.in_weight - invoice_details.out_weight) * gold_carats.transform_factor'));
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function purchaseCaratType()
    {
        return $this->belongsTo(GoldCaratType::class, 'purchase_carat_type_id', 'id');
    }

    public function zatcaDocuments()
    {
        return $this->morphMany(ZatcaDocument::class, 'invoiceable');
    }

    public function journalEntry()
    {
        return $this->morphOne(JournalEntry::class, 'journalable');
    }

    public function latestZatcaDocument()
    {
        return $this->morphOne(ZatcaDocument::class, 'invoiceable')->latestOfMany();
    }

    /**
     * Interact with qr code.
     *
     * @return Attribute
     */
    protected function zatcaQrCode(): Attribute
    {
        if (!$this->latestZatcaDocument) {
            $qrString = new QRCodeString([
                $this->branch->name ?? '',
                $this->branch->tax_number ?? '',
                \Carbon\Carbon::parse($this->date)->toIso8601ZuluString(),
                number_format($this->net_total, 2, '.', ''),
                number_format($this->taxes_total, 2, '.', ''),
            ]);
            $generatedString = $qrString->toBase64();
        } else {
            $generatedString = $this->latestZatcaDocument->qr_value;
        }

        $writer = new PngWriter();

        // Create QR code
        $qrCode = new QrCode(
            data: $generatedString,
            encoding: new Encoding('UTF-8'),
            errorCorrectionLevel: ErrorCorrectionLevel::Low,
            size: 300,
            margin: 10,
            roundBlockSizeMode: RoundBlockSizeMode::Margin,
            foregroundColor: new Color(0, 0, 0),
            backgroundColor: new Color(255, 255, 255)
        );
        $result = $writer->write($qrCode);
        return Attribute::make(
            get: fn() => $result->getDataUri(),
        );
    }

    public function parent()
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function returnInvoices()
    {
        return $this->hasMany(self::class, 'parent_id')->where('type', 'sale_return');
    }

    public function getStockCaratWeightAttribute()
    {
        return round($this->details()->join('gold_carats', 'invoice_details.gold_carat_id', '=', 'gold_carats.id')->sum(DB::raw('invoice_details.out_weight * gold_carats.transform_factor')), 3);
    }

    public function getReturnInvoicesDetailsIdsAttribute()
    {
        $ids = [];
        foreach ($this->returnInvoices()->get() as $returnInvoice) {
            $ids = array_merge($ids, $returnInvoice->details()->pluck('parent_id')->toArray());
        }
        return $ids;
    }

    public function getRoundNetTotalAttribute()
    {
        $taxesTotal = round($this->taxes_total, 2);
        $linesTotalAfterDiscount = round($this->lines_total_after_discount, 2);
        return $linesTotalAfterDiscount + $taxesTotal;
    }

    public function getCustomerNameAttribute()
    {
        return $this->bill_client_name ?? $this->customer->name;
    }

    public function getCustomerPhoneAttribute()
    {
        return $this->bill_client_phone ?? $this->customer->phone;
    }
}
