<?php

namespace App\Entity\PreEnquiry;

use App\Entity\NoteInterface;
use App\Entity\NoteTrait;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Table('pre_enquiry_note')]
#[ORM\Entity]
class PreEnquiryNote implements NoteInterface
{
    use NoteTrait;

    #[ORM\JoinColumn(nullable: false)]
    #[ORM\ManyToOne(targetEntity: PreEnquiry::class, inversedBy: 'notes')]
    private ?PreEnquiry $preEnquiry = null;

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
