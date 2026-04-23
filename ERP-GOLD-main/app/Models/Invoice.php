<?php

namespace App\Models;

use App\Services\Invoices\InvoiceNumberService;
use App\Services\Payments\InvoicePaymentService;
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
            $numberData = app(InvoiceNumberService::class)->assign($invoice);
            $invoice->bill_number = $numberData['bill_number'];
            $invoice->serial = $numberData['serial'];
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

    public function account()
    {
        return $this->belongsTo(Account::class);
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function shift()
    {
        return $this->belongsTo(Shift::class);
    }

    public function paymentLines()
    {
        return $this->hasMany(InvoicePaymentLine::class)->with('bankAccount');
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
        $returnType = $this->type === 'purchase' ? 'purchase_return' : 'sale_return';

        return $this->hasMany(self::class, 'parent_id')->where('type', $returnType);
    }

    public function manufacturingReceipts()
    {
        return $this->hasMany(self::class, 'parent_id')->where('type', 'manufacturing_receipt');
    }

    public function manufacturingReturns()
    {
        return $this->hasMany(self::class, 'parent_id')->where('type', 'manufacturing_return');
    }

    public function manufacturingLossSettlements()
    {
        return $this->hasMany(self::class, 'parent_id')->where('type', 'manufacturing_loss_settlement');
    }

    public function manufacturingLossSettlementLines()
    {
        return $this->hasMany(ManufacturingLossSettlementLine::class, 'invoice_id');
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

    public function getReturnedTotalAttribute(): float
    {
        return round((float) $this->returnInvoices()->sum('net_total'), 2);
    }

    public function getNetAfterReturnsAttribute(): float
    {
        return round($this->round_net_total - $this->returned_total, 2);
    }

    public function getCustomerNameAttribute()
    {
        return $this->bill_client_name ?? $this->customer?->name;
    }

    public function getCustomerPhoneAttribute()
    {
        return $this->bill_client_phone ?? $this->customer?->phone;
    }

    public function getCustomerIdentityNumberAttribute()
    {
        return $this->bill_client_identity_number ?? $this->customer?->identity_number;
    }

    public function getCashPaidTotalAttribute(): float
    {
        return app(InvoicePaymentService::class)->totalsForInvoice($this)['cash'];
    }

    public function getCreditCardPaidTotalAttribute(): float
    {
        return app(InvoicePaymentService::class)->totalsForInvoice($this)['credit_card'];
    }

    public function getBankTransferPaidTotalAttribute(): float
    {
        return app(InvoicePaymentService::class)->totalsForInvoice($this)['bank_transfer'];
    }

    public function getPaymentTypeLabelAttribute(): string
    {
        return app(InvoicePaymentService::class)->paymentTypeLabelForInvoice($this);
    }

    public function getPaymentLinesBreakdownAttribute(): array
    {
        return app(InvoicePaymentService::class)->paymentBreakdown($this);
    }

    public function getManufacturingReturnDirectionLabelAttribute(): string
    {
        return match ($this->manufacturing_return_direction) {
            'from_manufacturer' => __('main.manufacturing_return_from_manufacturer'),
            'to_manufacturer' => __('main.manufacturing_return_to_manufacturer'),
            default => __('main.manufacturing_return'),
        };
    }
}
