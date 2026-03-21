<?php
namespace App\Services\Zatca\Invoice;

use Exception;

/**
 * A class defines zatca phase two client
 */
class Client
{
    private ?string $vatNumber = '';
    private ?string $streetName = '';
    private ?string $buildingNumber = '';
    private ?string $plotIdentification = '';
    private ?string $subDivisionName = '';
    private ?string $cityName = '';
    private ?string $postalNumber = '';
    private string $countryName;
    private string $clientIdentification;
    private string $clientName;

    public function __construct()
    {
        $this->clientIdentification = 'OTH';
    }

    /**
     * Set client vat number
     *
     * @param string|null $vatNumber
     *
     * @return $this
     */
    public function setVatNumber(string|null $vatNumber): self
    {
        $this->vatNumber = $vatNumber;

        return $this;
    }

    /**
     * Set client street name
     *
     * @param string|null $streetName
     *
     * @return $this
     */
    public function setStreetName(string|null $streetName): self
    {
        $this->streetName = $streetName;

        return $this;
    }

    /**
     * Set client building number
     *
     * @param string|null $buildingNumber
     *
     * @return $this
     */
    public function setBuildingNumber(string|null $buildingNumber): self
    {
        $this->buildingNumber = $buildingNumber;

        return $this;
    }

    /**
     * Set client plot identification
     *
     * @param string|null $plotIdentification
     *
     * @return $this
     */
    public function setPlotIdentification(string|null $plotIdentification): self
    {
        $this->plotIdentification = $plotIdentification;

        return $this;
    }

    /**
     * Set client sub divisionName
     *
     * @param string|null $subDivisionName
     *
     * @return $this
     */
    public function setSubDivisionName(string|null $subDivisionName): self
    {
        $this->subDivisionName = $subDivisionName;

        return $this;
    }

    /**
     * Set client city name
     *
     * @param string|null $cityName
     *
     * @return $this
     */
    public function setCityName(string|null $cityName): self
    {
        $this->cityName = $cityName;

        return $this;
    }

    /**
     * Set client postal number
     *
     * @param string|null $postalNumber
     *
     * @return $this
     */
    public function setPostalNumber(string|null $postalNumber): self
    {
        $this->postalNumber = $postalNumber;

        return $this;
    }

    /**
     * Set client country name
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
     * Set client identification
     *
     * @param string $clientIdentification
     *
     * @return $this
     */
    public function setClientIdentification(string $clientIdentification): self
    {
        $this->clientIdentification = $clientIdentification;

        return $this;
    }

    /**
     * Set client name
     *
     * @param string $clientName
     *
     * @return $this
     */
    public function setClientName(string $clientName): self
    {
        $this->clientName = $clientName;

        return $this;
    }

    /**
     * The getElement method is called during xml writing.
     *
     * @return array
     */
    public function getElement(): array
    {
        if (!empty($this->vatNumber)) {
            if (empty($this->streetName)) {
                throw new Exception('Client street name required .');
            }
            if (empty($this->buildingNumber)) {
                throw new Exception('Client building number required .');
            }
            if (empty($this->plotIdentification)) {
                throw new Exception('Client plot identification required .');
            }
            if (empty($this->subDivisionName)) {
                throw new Exception('Client region name required .');
            }
            if (empty($this->cityName)) {
                throw new Exception('Client city name required .');
            }
            if (empty($this->postalNumber)) {
                throw new Exception('Client postal number required .');
            }
        }
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
                            'value' => ($this->clientIdentification == 'TIN') ? str_replace('00003', '', $this->vatNumber) : $this->vatNumber,
                            'namespaced' => true,
                            'namespace' => null,
                            'prefix' => 'cbc',
                            'attributes' => [
                                [
                                    'name' => 'schemeID',
                                    'value' => $this->clientIdentification,
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
                            'value' => $this->clientName,
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
