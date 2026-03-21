<?php
namespace App\Services\Zatca\Invoice;

/**
 * A class defines zatca phase two supplier
 */
class Supplier
{
    private string $crn;
    private string $streetName;
    private string $buildingNumber;
    private string $plotIdentification;
    private string $subDivisionName;
    private string $cityName;
    private string $postalNumber;
    private string $countryName;
    private string $vatNumber;
    private string $vatName;

    /**
     * Set supplier commercial registration number
     *
     * @param string $crn
     *
     * @return $this
     */
    public function setCrn(string $crn): self
    {
        $this->crn = $crn;

        return $this;
    }

    /**
     * Set supplier street name
     *
     * @param string $streetName
     *
     * @return $this
     */
    public function setStreetName(string $streetName): self
    {
        $this->streetName = $streetName;

        return $this;
    }

    /**
     * Set supplier building number
     *
     * @param string $buildingNumber
     *
     * @return $this
     */
    public function setBuildingNumber(string $buildingNumber): self
    {
        $this->buildingNumber = $buildingNumber;

        return $this;
    }

    /**
     * Set supplier plot identification
     *
     * @param string $plotIdentification
     *
     * @return $this
     */
    public function setPlotIdentification(string $plotIdentification): self
    {
        $this->plotIdentification = $plotIdentification;

        return $this;
    }

    /**
     * Set supplier sub divisionName
     *
     * @param string $subDivisionName
     *
     * @return $this
     */
    public function setSubDivisionName(string $subDivisionName): self
    {
        $this->subDivisionName = $subDivisionName;

        return $this;
    }

    /**
     * Set supplier city name
     *
     * @param string $cityName
     *
     * @return $this
     */
    public function setCityName(string $cityName): self
    {
        $this->cityName = $cityName;

        return $this;
    }

    /**
     * Set supplier postal number
     *
     * @param string $postalNumber
     *
     * @return $this
     */
    public function setPostalNumber(string $postalNumber): self
    {
        $this->postalNumber = $postalNumber;

        return $this;
    }

    /**
     * Set supplier country name
     *
     * @param string $countryName
     *
     * @return $this
     */
    public function setCountryName(string $countryName): self
    {
        $this->countryName = $countryName;

        return $this;
    }

    /**
     * Set supplier vat number
     *
     * @param string $vatNumber
     *
     * @return $this
     */
    public function setVatNumber(string $vatNumber): self
    {
        $this->vatNumber = $vatNumber;

        return $this;
    }

    /**
     * Get supplier vat number
     *
     * @return string
     */
    public function getVatNumber(): string
    {
        return $this->vatNumber;
    }

    /**
     * Set supplier name
     *
     * @param string $vatName
     *
     * @return $this
     */
    public function setVatName(string $vatName): self
    {
        $this->vatName = $vatName;

        return $this;
    }

    /**
     * Get supplier name
     *
     * @return string
     */
    public function getVatName(): string
    {
        return $this->vatName;
    }

    /**
     * The getElement method is called during xml writing.
     *
     * @return array
     */
    public function getElement(): array
    {
        return [
            'name' => 'Party',
            'value' => null,
            'namespaced' => true,
            'namespace' => null,
            'prefix' => 'cac',
            'childs' => [
                [
                    'name' => 'PartyIdentification',
                    'value' => null,
                    'namespaced' => true,
                    'namespace' => null,
                    'prefix' => 'cac',
                    'childs' => [
                        [
                            'name' => 'ID',
                            'value' => $this->crn,
                            'namespaced' => true,
                            'namespace' => null,
                            'prefix' => 'cbc',
                            'attributes' => [
                                [
                                    'name' => 'schemeID',
                                    'value' => 'CRN',
                                    'namespaced' => false,
                                    'namespace' => null,
                                    'prefix' => null,
                                ],
                            ],
                        ]
                    ]
                ],
                [
                    'name' => 'PostalAddress',
                    'value' => null,
                    'namespaced' => true,
                    'namespace' => null,
                    'prefix' => 'cac',
                    'childs' => [
                        [
                            'name' => 'StreetName',
                            'value' => $this->streetName,
                            'namespaced' => true,
                            'namespace' => null,
                            'prefix' => 'cbc',
                        ],
                        [
                            'name' => 'BuildingNumber',
                            'value' => $this->buildingNumber,
                            'namespaced' => true,
                            'namespace' => null,
                            'prefix' => 'cbc',
                        ],
                        [
                            'name' => 'PlotIdentification',
                            'value' => $this->plotIdentification,
                            'namespaced' => true,
                            'namespace' => null,
                            'prefix' => 'cbc',
                        ],
                        [
                            'name' => 'CitySubdivisionName',
                            'value' => $this->subDivisionName,
                            'namespaced' => true,
                            'namespace' => null,
                            'prefix' => 'cbc',
                        ],
                        [
                            'name' => 'CityName',
                            'value' => $this->cityName,
                            'namespaced' => true,
                            'namespace' => null,
                            'prefix' => 'cbc',
                        ],
                        [
                            'name' => 'PostalZone',
                            'value' => $this->postalNumber,
                            'namespaced' => true,
                            'namespace' => null,
                            'prefix' => 'cbc',
                        ],
                        [
                            'name' => 'Country',
                            'value' => null,
                            'namespaced' => true,
                            'namespace' => null,
                            'prefix' => 'cac',
                            'childs' => [
                                [
                                    'name' => 'IdentificationCode',
                                    'value' => $this->countryName,
                                    'namespaced' => true,
                                    'namespace' => null,
                                    'prefix' => 'cbc',
                                ]
                            ]
                        ]
                    ]
                ],
                [
                    'name' => 'PartyTaxScheme',
                    'value' => null,
                    'namespaced' => true,
                    'namespace' => null,
                    'prefix' => 'cac',
                    'childs' => [
                        [
                            'name' => 'CompanyID',
                            'value' => $this->vatNumber,
                            'namespaced' => true,
                            'namespace' => null,
                            'prefix' => 'cbc',
                        ],
                        [
                            'name' => 'TaxScheme',
                            'value' => null,
                            'namespaced' => true,
                            'namespace' => null,
                            'prefix' => 'cac',
                            'childs' => [
                                [
                                    'name' => 'ID',
                                    'value' => 'VAT',
                                    'namespaced' => true,
                                    'namespace' => null,
                                    'prefix' => 'cbc',
                                ]
                            ]
                        ]
                    ]
                ],
                [
                    'name' => 'PartyLegalEntity',
                    'value' => null,
                    'namespaced' => true,
                    'namespace' => null,
                    'prefix' => 'cac',
                    'childs' => [
                        [
                            'name' => 'RegistrationName',
                            'value' => $this->vatName,
                            'namespaced' => true,
                            'namespace' => null,
                            'prefix' => 'cbc',
                        ]
                    ]
                ]
            ]
        ];
    }
}
