<?php
namespace App\Services\Zatca;

use App\Services\Zatca\Invoice\AdditionalDocumentReference;
use App\Services\Zatca\Invoice\AllowanceCharge;
use App\Services\Zatca\Invoice\BillingReference;
use App\Services\Zatca\Invoice\Client;
use App\Services\Zatca\Invoice\Delivery;
use App\Services\Zatca\Invoice\InvoiceGenerator;
use App\Services\Zatca\Invoice\InvoiceLine;
use App\Services\Zatca\Invoice\LegalMonetaryTotal;
use App\Services\Zatca\Invoice\LineTaxCategory;
use App\Services\Zatca\Invoice\PaymentType;
use App\Services\Zatca\Invoice\PIH;
use App\Services\Zatca\Invoice\ReturnReason;
use App\Services\Zatca\Invoice\Supplier;
use App\Services\Zatca\Invoice\TaxesTotal;
use App\Services\Zatca\Invoice\TaxSubtotal;
use Carbon\Carbon;
use Illuminate\Support\Str;

/**
 * A class defines zatca required integration defaults
 */
class SendZatcaInvoice
{
    public $invoice;
    public $zatcaDocument;
    public $branch;
    public $zatcaSettings;
    public $invoiceType;
    public $invoiceDocumentType;

    public function __construct($invoice)
    {
        $this->invoice = $invoice;
        $this->branch = $invoice->branch;

        $this->zatcaSettings = $this->branch->zatca_settings;

        if ($this->invoice->customer->tax_number == null) {
            $this->invoiceType = '0200000';
            if ($this->invoice->type == 'sale') {
                $this->invoiceDocumentType = '388';
            } else {
                $this->invoiceDocumentType = '381';
            }
        }
    }

    public function send()
    {
        if (!$this->branch || !$this->branch->zatca_settings) {
            return;
        }
        $this->zatcaDocument = $this->invoice->zatcaDocuments()->create(['branch_id' => $this->invoice->branch_id]);
        if ($this->invoiceType == '0200000') {
            $client = (new Client())
                ->setCountryName('SA')
                ->setClientName($this->invoice->customer->name);
        } else {
            $client = (new Client())
                ->setVatNumber($this->invoice->customer->tax_number)
                ->setStreetName(!empty($this->invoice->customer->street_name) ? $this->invoice->customer->street_name : 'street')
                ->setBuildingNumber(!empty($this->invoice->customer->building_number) ? $this->invoice->customer->building_number : '1234')
                ->setPlotIdentification(!empty($this->invoice->customer->plot_identification) ? $this->invoice->customer->plot_identification : '1234')
                ->setSubDivisionName(!empty($this->invoice->customer->district) ? $this->invoice->customer->district : 'district')
                ->setCityName(!empty($this->invoice->customer->city) ? $this->invoice->customer->city : 'city')
                ->setPostalNumber(!empty($this->invoice->customer->postal_code) ? $this->invoice->customer->postal_code : '12345')
                ->setCountryName('SA')
                ->setClientIdentification('TIN')
                ->setClientName($this->invoice->customer->name);
        }

        $supplier = (new Supplier())
            ->setCrn($this->branch->commercial_register)
            ->setStreetName($this->branch->street_name)
            ->setBuildingNumber($this->branch->building_number)
            ->setPlotIdentification($this->branch->plot_identification)
            ->setSubDivisionName($this->branch->district)
            ->setCityName($this->branch->city)
            ->setPostalNumber($this->branch->postal_code)
            ->setCountryName('SA')
            ->setVatNumber($this->branch->tax_number)
            ->setVatName($this->branch->name);

        $delivery = (new Delivery())
            ->setDeliveryDateTime(Carbon::parse($this->invoice->date)->format('Y-m-d'));

        $paymentType = (new PaymentType())
            ->setPaymentType('10');

        $returnReason = (new ReturnReason())
            ->setReturnReason('return items');

        $previous_hash = (new PIH())
            ->setPIH($this->zatcaDocument->Pih);

        $billingReference = (new BillingReference())
            ->setBillingReference('1');  // note this used when type credit or debit this value of parent invoice id

        $additionalDocumentReference = (new AdditionalDocumentReference())
            ->setInvoiceID($this->zatcaDocument->icv);

        $itemTaxCategory = (new LineTaxCategory())
            ->setTaxCategory('S')
            ->setTaxPercentage(15)
            ->getElement();

        $subTotal = 0;
        $taxTotal = 0;
        $netTotal = 0;
        foreach ($this->invoice->details as $index => $detail) {
            $weight = ($this->invoice->type == 'sale') ? $detail->out_weight : $detail->in_weight;
            $quantity = $weight;
            $subTotalLine = $detail->line_total;
            $subTotal += $subTotalLine;
            $taxTotalLine = $detail->line_tax;
            $taxTotal += $taxTotalLine;
            $netTotalLine = $subTotalLine + $taxTotalLine;
            $netTotal += $netTotalLine;
            $price = $detail->unit_price;
            $invoiceLines[] = (new InvoiceLine())
                ->setLineID($index + 1)
                ->setLineName($detail->item->title)
                ->setLineUnitCode('GRM')
                ->setLineCurrency('SAR')
                ->setLinePrice($price)
                ->setLineQuantity($quantity)
                ->setLineSubTotal($subTotalLine)
                ->setLineTaxTotal($taxTotalLine)
                ->setLineNetTotal($netTotalLine)
                ->setLineTaxCategories($itemTaxCategory)
                ->setLineDiscountReason('reason')
                ->setLineDiscountAmount(0)
                ->getElement();
        }

        $legalMonetaryTotal = (new LegalMonetaryTotal())
            ->setTotalCurrency('SAR')
            ->setLineExtensionAmount($subTotal)
            ->setTaxExclusiveAmount($subTotal)
            ->setTaxInclusiveAmount($netTotal)
            ->setAllowanceTotalAmount(0)
            ->setPrepaidAmount(0)
            ->setPayableAmount($netTotal);

        $taxesTotal = (new TaxesTotal())
            ->setTaxCurrencyCode('SAR')
            ->setTaxTotal($taxTotal);

        $taxSubtotal = (new TaxSubtotal())
            ->setTaxCurrencyCode('SAR')
            ->setTaxableAmount($subTotal)
            ->setTaxAmount($taxTotal)
            ->setTaxCategory('S')
            ->setTaxPercentage(15)
            ->getElement();
        $allowanceCharge = (new AllowanceCharge())
            ->setAllowanceChargeCurrency('SAR')
            ->setAllowanceChargeIndex('1')
            ->setAllowanceChargeAmount(0)
            ->setAllowanceChargeTaxCategory('S')
            ->setAllowanceChargeTaxPercentage(15)
            ->getElement();

        $zatca_stage = $this->zatcaSettings->zatca_stage;
        $isProduction = ($zatca_stage == 'developer-portal') ? false : true;
        $certificate = (!$isProduction) ? $this->zatcaSettings->certificate : $this->zatcaSettings->production_certificate;
        $privateKey = $this->zatcaSettings->private_key;
        $certificateSecret = (!$isProduction) ? $this->zatcaSettings->secret : $this->zatcaSettings->production_secret;
        $generator = (new InvoiceGenerator())
            ->setZatcaEnv($zatca_stage)
            ->setZatcaLang('en')
            ->setInvoiceNumber($this->invoice->bill_number)
            ->setInvoiceUuid($this->zatcaDocument->uuid)
            ->setInvoiceIssueDate(Carbon::parse($this->invoice->date)->format('Y-m-d'))
            ->setInvoiceIssueTime(Carbon::parse($this->invoice->time)->format('H:i:s'))
            ->setInvoiceType($this->invoiceType, $this->invoiceDocumentType)
            ->setInvoiceCurrencyCode('SAR')
            ->setInvoiceTaxCurrencyCode('SAR')
            ->setInvoiceAdditionalDocumentReference($additionalDocumentReference);
        if ($this->invoiceDocumentType == '381') {
            $generator = $generator->setInvoiceBillingReference($billingReference);
        }

        $generator = $generator
            ->setInvoicePIH($previous_hash)
            ->setInvoiceSupplier($supplier)
            ->setInvoiceClient($client)
            ->setInvoiceDelivery($delivery)
            ->setInvoicePaymentType($paymentType);
        if ($this->invoiceDocumentType == '381') {
            $generator = $generator->setInvoiceReturnReason($returnReason);
        }
        $generator = $generator
            ->setInvoiceLegalMonetaryTotal($legalMonetaryTotal)
            ->setInvoiceTaxesTotal($taxesTotal)
            ->setInvoiceTaxSubTotal($taxSubtotal)
            ->setInvoiceAllowanceCharges($allowanceCharge)
            ->setInvoiceLines(...$invoiceLines)
            ->setCertificateEncoded($certificate)
            ->setPrivateKeyEncoded($privateKey)
            ->setCertificateSecret($certificateSecret);
        $document = $generator->generateDocument();
        $this->zatcaDocument->update([
            'xml' => $document['invoice'],
            'hash' => $document['hash'],
            'qr_value' => $document['qr_value'],
            'signing_time' => \Carbon\Carbon::parse($document['signing_time']),
        ]);
        $response = $generator->sendDocument($this->zatcaDocument->hash, (string) $this->zatcaDocument->uuid, $this->zatcaDocument->xml, $isProduction);

        if ($response['success']) {
            $this->zatcaDocument->update([
                'xml' => $response['xml'],
                'qr_value' => $response['qr_value'],
            ]);
        }

        $this->zatcaDocument->update([
            'sent_to_zatca' => 1,
            'sent_to_zatca_status' => $response['response']->validationResults->status,
            'response' => json_encode($response, JSON_UNESCAPED_UNICODE),
        ]);
    }
}
