<?php
namespace App\Services\Zatca\Invoice;

/**
 * A class defines zatca phase two compliance steps
 */
class ComplianceSteps
{
    private string $certificate;
    private string $certificateSecret;
    private string $privateKey;
    private string $vatNumber;
    private string $env;
    private array $parentLoopArr;

    public function __construct(string $certificate, string $certificateSecret, string $privateKey, string $vatNumber, string $invoiceType, string $env)
    {
        $this->certificate = $certificate;
        $this->certificateSecret = $certificateSecret;
        $this->privateKey = base64_encode($privateKey);
        $this->vatNumber = $vatNumber;
        $this->env = $env;

        if ($invoiceType == '1100') {
            $this->parentLoopArr = ['0200000', '0100000'];
        } elseif ($invoiceType == '0100') {
            $this->parentLoopArr = ['0200000'];
        } else {
            $this->parentLoopArr = ['0100000'];
        }
    }

    /**
     * Send compliance steps required invoices
     *
     * @return bool
     */
    public function sendComplianceSteps()
    {
        $numberOfFullIterations = 0;
        $numberOfPassedDocuments = 0;
        $documentTypes = ['388', '383', '381'];
        foreach ($this->parentLoopArr as $invoiceType) {
            foreach ($documentTypes as $documentType) {
                $numberOfFullIterations++;
                $client = (new Client())
                    ->setVatNumber('300000000000003')
                    ->setStreetName('STREET')
                    ->setBuildingNumber('1111')
                    ->setPlotIdentification('2223')
                    ->setSubDivisionName('JEDDAH')
                    ->setCityName('JEDDAH')
                    ->setPostalNumber('12222')
                    ->setCountryName('SA')
                    ->setClientName('TSTCO');

                $supplier = (new Supplier())
                    ->setCrn('1000000000')
                    ->setStreetName('RIYADH')
                    ->setBuildingNumber('2322')
                    ->setPlotIdentification('2223')
                    ->setSubDivisionName('RIYADH')
                    ->setCityName('RIYADH')
                    ->setPostalNumber('11633')
                    ->setCountryName('SA')
                    ->setVatNumber($this->vatNumber)
                    ->setVatName('TSTCO');

                $delivery = (new Delivery())
                    ->setDeliveryDateTime('2022-09-07');

                $paymentType = (new PaymentType())
                    ->setPaymentType('10');

                $returnReason = (new ReturnReason())
                    ->setReturnReason('SET_RETURN_REASON');

                $previous_hash = (new PIH())
                    ->setPIH('X+zrZv/IbzjZUnhsbWlsecLbwjndTpG0ZynXOif7V+k=');

                $billingReference = (new BillingReference())
                    ->setBillingReference('23');

                $additionalDocumentReference = (new AdditionalDocumentReference())
                    ->setInvoiceID('55');

                $legalMonetaryTotal = (new LegalMonetaryTotal())
                    ->setTotalCurrency('SAR')
                    ->setLineExtensionAmount(4)
                    ->setTaxExclusiveAmount(4)
                    ->setTaxInclusiveAmount(4.6)
                    ->setAllowanceTotalAmount(0)
                    ->setPrepaidAmount(0)
                    ->setPayableAmount(4.6);

                $taxesTotal = (new TaxesTotal())
                    ->setTaxCurrencyCode('SAR')
                    ->setTaxTotal(0.6);

                $taxSubtotal = (new TaxSubtotal())
                    ->setTaxCurrencyCode('SAR')
                    ->setTaxableAmount(4.0)
                    ->setTaxAmount(0.6)
                    ->setTaxCategory('S')
                    ->setTaxPercentage(15)
                    ->getElement();

                $itemTaxCategory = (new LineTaxCategory())
                    ->setTaxCategory('S')
                    ->setTaxPercentage(15)
                    ->getElement();

                $invoiceLine = (new InvoiceLine())
                    ->setLineID('1')
                    ->setLineName('TST Item')
                    ->setLineCurrency('SAR')
                    ->setLinePrice(2)
                    ->setLineQuantity(2)
                    ->setLineSubTotal(4)
                    ->setLineTaxTotal(0.6)
                    ->setLineNetTotal(4.6)
                    ->setLineTaxCategories($itemTaxCategory)
                    ->setLineDiscountReason('reason')
                    ->setLineDiscountAmount(0)
                    ->getElement();

                $allowanceCharge = (new AllowanceCharge())
                    ->setAllowanceChargeCurrency('SAR')
                    ->setAllowanceChargeIndex('1')
                    ->setAllowanceChargeAmount(0)
                    ->setAllowanceChargeTaxCategory('S')
                    ->setAllowanceChargeTaxPercentage(15)
                    ->getElement();

                $invoiceGenerator = (new InvoiceGenerator())
                    ->setZatcaEnv($this->env)
                    ->setZatcaLang('en')
                    ->setInvoiceNumber('SME00023')
                    ->setInvoiceUuid('8d487816-70b8-4ade-a618-9d620b73814a')
                    ->setInvoiceIssueDate('2022-09-07')
                    ->setInvoiceIssueTime('12:21:28')
                    ->setInvoiceType($invoiceType, $documentType)
                    ->setInvoiceCurrencyCode('SAR')
                    ->setInvoiceTaxCurrencyCode('SAR')
                    ->setInvoiceBillingReference($billingReference)
                    ->setInvoiceAdditionalDocumentReference($additionalDocumentReference)
                    ->setInvoicePIH($previous_hash)
                    ->setInvoiceSupplier($supplier)
                    ->setInvoiceClient($client)
                    ->setInvoiceDelivery($delivery)
                    ->setInvoicePaymentType($paymentType)
                    ->setInvoiceReturnReason($returnReason)
                    ->setInvoiceLegalMonetaryTotal($legalMonetaryTotal)
                    ->setInvoiceTaxesTotal($taxesTotal)
                    ->setInvoiceTaxSubTotal($taxSubtotal)
                    ->setInvoiceAllowanceCharges($allowanceCharge)
                    ->setInvoiceLines($invoiceLine)
                    ->setCertificateEncoded($this->certificate)
                    ->setPrivateKeyEncoded($this->privateKey)
                    ->setCertificateSecret($this->certificateSecret);
                $document = $invoiceGenerator->generateDocument();
                $response = $invoiceGenerator->sendDocument($document['hash'], (string) $document['uuid'], $document['invoice']);
                if ($response['success']) {
                    $numberOfPassedDocuments++;
                }
            }
        }
        return ($numberOfFullIterations == $numberOfPassedDocuments) ? true : false;
    }
}
