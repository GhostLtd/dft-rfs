<?php


namespace App\Messenger\AlphagovNotify;


use App\Entity\Address;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;

class Letter extends AbstractMessage
{
    /**
     * @var Address
     */
    protected $address;

    /**
     * Email constructor.
     * @param $originatingEntityClass
     * @param $originatingEntityId
     * @param $address
     * @param $templateId
     * @param array $personalisation
     * @param null $reference
     */
    public function __construct($originatingEntityClass, $originatingEntityId, $address, $templateId, $personalisation = [], $reference = null)
    {
        parent::__construct($originatingEntityClass, $originatingEntityId, $templateId, $personalisation, $reference);
        $this->address = $address;
    }

    public function getSendMethodParameters(): array
    {
        return [
            $this->getTemplateId(),
            $this->getPersonalisation(),
            $this->getReference(),
        ];
    }

    public function getPersonalisation()
    {
        return array_merge(parent::getPersonalisation(), $this->getAddressForPersonalisation());
    }

    /**
     * @return string
     */
    public function getAddress(): ?string
    {
        return $this->address;
    }

    public function getAddressForPersonalisation()
    {
        $address = array_values(array_filter((array) $this->address));
        $notifyAddressFields = ['address_line_1', 'address_line_2', 'address_line_3', 'address_line_4', 'address_line_5', 'address_line_6', 'address_line_7'];
        $address = array_merge($address, array_fill(count($address), count($notifyAddressFields) - count($address), null));
        return array_combine(
            $notifyAddressFields,
            $address
        );
    }

    /**
     * @param string|null $address
     * @return self
     */
    public function setAddress(?string $address): self
    {
        $this->address = $address;
    }
}