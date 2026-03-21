<?php
namespace App\Services\Zatca\Invoice;

/**
 * A class defines zatca phase two delivery date & time
 */
class Delivery
{
    private string $deliveryDateTime;

    /**
     * Set delivery date & time
     *
     * @param string $deliveryDateTime
     *
     * @return $this
     */
    public function setDeliveryDateTime(string $deliveryDateTime): self
    {
        $this->deliveryDateTime = $deliveryDateTime;

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
            'name' => 'ActualDeliveryDate',
            'value' => $this->deliveryDateTime,
            'namespaced' => true,
            'namespace' => null,
            'prefix' => 'cbc'
        ];
    }
}
