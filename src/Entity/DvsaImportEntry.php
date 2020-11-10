<?php

namespace App\Entity;

use DateTimeInterface;

class DvsaImportEntry
{
    private $organisationId;
    private $organisationName;
    private $licenseNumber;
    private $licenceType;
    private $licenseStartDate;
    private $licenceContinuationDate;
    private $licenceEndDate;
    private $vehiclesAuthorised;
    private $trailersAuthorised;
    private $vehiclesSpecified;

    private $addressLine1;
    private $addressLine2;
    private $addressLine3;
    private $addressLine4;
    private $town;
    private $postcode;
    private $country;

    public function getOrganisationId(): ?int
    {
        return $this->organisationId;
    }

    public function setOrganisationId(int $organisationId): self
    {
        $this->organisationId = $organisationId;

        return $this;
    }

    public function getOrganisationName(): ?string
    {
        return $this->organisationName;
    }

    public function setOrganisationName(string $organisationName): self
    {
        $this->organisationName = $organisationName;

        return $this;
    }

    public function getLicenseNumber(): ?string
    {
        return $this->licenseNumber;
    }

    public function setLicenseNumber(string $licenseNumber): self
    {
        $this->licenseNumber = $licenseNumber;

        return $this;
    }

    public function getLicenceType(): ?string
    {
        return $this->licenceType;
    }

    public function setLicenceType(string $licenceType): self
    {
        $this->licenceType = $licenceType;

        return $this;
    }

    public function getLicenseStartDate(): ?DateTimeInterface
    {
        return $this->licenseStartDate;
    }

    public function setLicenseStartDate(DateTimeInterface $licenseStartDate): self
    {
        $this->licenseStartDate = $licenseStartDate;

        return $this;
    }

    public function getLicenceContinuationDate(): ?DateTimeInterface
    {
        return $this->licenceContinuationDate;
    }

    public function setLicenceContinuationDate(DateTimeInterface $licenceContinuationDate): self
    {
        $this->licenceContinuationDate = $licenceContinuationDate;

        return $this;
    }

    public function getLicenceEndDate(): ?DateTimeInterface
    {
        return $this->licenceEndDate;
    }

    public function setLicenceEndDate(?DateTimeInterface $licenceEndDate): self
    {
        $this->licenceEndDate = $licenceEndDate;

        return $this;
    }

    public function getVehiclesAuthorised(): ?int
    {
        return $this->vehiclesAuthorised;
    }

    public function setVehiclesAuthorised(int $vehiclesAuthorised): self
    {
        $this->vehiclesAuthorised = $vehiclesAuthorised;

        return $this;
    }

    public function getTrailersAuthorised(): ?int
    {
        return $this->trailersAuthorised;
    }

    public function setTrailersAuthorised(int $trailersAuthorised): self
    {
        $this->trailersAuthorised = $trailersAuthorised;

        return $this;
    }

    public function getVehiclesSpecified(): ?int
    {
        return $this->vehiclesSpecified;
    }

    public function setVehiclesSpecified(int $vehiclesSpecified): self
    {
        $this->vehiclesSpecified = $vehiclesSpecified;

        return $this;
    }

    public function getAddressLine1(): ?string
    {
        return $this->addressLine1;
    }

    public function setAddressLine1(string $addressLine1): self
    {
        $this->addressLine1 = $addressLine1;

        return $this;
    }

    public function getAddressLine2(): ?string
    {
        return $this->addressLine2;
    }

    public function setAddressLine2(string $addressLine2): self
    {
        $this->addressLine2 = $addressLine2;

        return $this;
    }

    public function getAddressLine3(): ?string
    {
        return $this->addressLine3;
    }

    public function setAddressLine3(string $addressLine3): self
    {
        $this->addressLine3 = $addressLine3;

        return $this;
    }

    public function getAddressLine4(): ?string
    {
        return $this->addressLine4;
    }

    public function setAddressLine4(string $addressLine4): self
    {
        $this->addressLine4 = $addressLine4;

        return $this;
    }

    public function getTown(): ?string
    {
        return $this->town;
    }

    public function setTown(string $town): self
    {
        $this->town = $town;

        return $this;
    }

    public function getPostcode(): ?string
    {
        return $this->postcode;
    }

    public function setPostcode(string $postcode): self
    {
        $this->postcode = $postcode;

        return $this;
    }

    public function getCountry(): ?string
    {
        return $this->country;
    }

    public function setCountry(string $country): self
    {
        $this->country = $country;

        return $this;
    }
}
