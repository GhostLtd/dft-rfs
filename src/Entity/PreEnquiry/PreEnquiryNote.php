<?php

namespace App\Entity\PreEnquiry;

use App\Entity\NoteTrait;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity()
 * @ORM\Table("pre_enquiry_note")
 */
class PreEnquiryNote
{
    use NoteTrait;

    /**
     * @ORM\ManyToOne(targetEntity=PreEnquiry::class, inversedBy="notes")
     * @ORM\JoinColumn(nullable=false)
     */
    private ?PreEnquiry $preEnquiry;

    public function getPreEnquiry(): ?PreEnquiry
    {
        return $this->preEnquiry;
    }

    public function setPreEnquiry(?PreEnquiry $preEnquiry): PreEnquiryNote
    {
        $this->preEnquiry = $preEnquiry;
        return $this;
    }
}