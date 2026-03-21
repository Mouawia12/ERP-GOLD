<?php
namespace App\Services\Zatca\Invoice;

/**
 * A class defines zatca phase two payment type
 */
class PaymentType
{
    private string $paymentType;

    /**
     * Set payment type
     *
     * @param string $paymentType
     *
     * @return $this
     */
    public function setPaymentType(string $paymentType): self
    {
        $this->paymentType = $paymentType;

        return $this;
    }

    /**
     * The getElement method is called during xml writing.
     *
     * @return array
     */
    public function getElement(): array
    {
        return [
            'name' => 'PaymentMeansCode',
            'value' => $this->paymentType,
            'namespaced' => true,
            'namespace' => null,
            'prefix' => 'cbc',
        ];
    }
}
