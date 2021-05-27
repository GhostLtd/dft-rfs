<?php

namespace App\Entity\PreEnquiry;

use App\Entity\International\Company;
use App\Entity\SurveyInterface;
use App\Entity\SurveyTrait;
use App\Entity\PasscodeUser;
use App\Repository\PreEnquiry\PreEnquiryRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity(repositoryClass=PreEnquiryRepository::class)
 * @ORM\Table(name="pre_enquiry")
 */
class PreEnquiry implements SurveyInterface
{
    use SurveyTrait;

    /**
     * @ORM\OneToOne(targetEntity=PreEnquiryResponse::class, mappedBy="preEnquiry", cascade={"persist"})
     */
    private $response;

    /**
     * @ORM\ManyToOne(targetEntity=Company::class, inversedBy="preEnquiries")
     * @ORM\JoinColumn(nullable=false)
     * @Assert\Valid(groups={"add_survey"})
     */
    private $company;

    /**
     * @ORM\OneToOne(targetEntity=PasscodeUser::class, mappedBy="domesticSurvey", cascade={"persist", "remove"})
     */
    private $passcodeUser;

    /**
     * @ORM\OneToMany(targetEntity=PreEnquiryNote::class, mappedBy="survey")
     * @ORM\OrderBy({"createdAt" = "ASC"})
     */
    private $notes;


    public function getResponse(): ?PreEnquiryResponse
    {
        return $this->response;
    }

    public function setResponse(PreEnquiryResponse $response): self
    {
        $this->response = $response;

        // set the owning side of the relation if necessary
        if ($response->getPreEnquiry() !== $this) {
            $response->setPreEnquiry($this);
        }

        return $this;
    }

    public function getCompany(): ?Company
    {
        return $this->company;
    }

    public function setCompany(?Company $company): self
    {
        $this->company = $company;

        return $this;
    }

    public function getNotes()
    {
        return $this->notes;
    }

    public function addNote(PreEnquiryNote $note): self
    {
        if (!$this->notes->contains($note)) {
            $note->setPreEnquiry($this);
            $this->notes[] = $note;
        }

        return $this;
    }

    public function getPasscodeUser(): ?PasscodeUser
    {
        return $this->passcodeUser;
    }

    public function setPasscodeUser(?PasscodeUser $passcodeUser): self
    {
        $this->passcodeUser = $passcodeUser;

        // set (or unset) the owning side of the relation if necessary
        $newPreEnquiry = null === $passcodeUser ? null : $this;
        if ($passcodeUser->getPreEnquiry() !== $newPreEnquiry) {
            $passcodeUser->setPreEnquiry($newPreEnquiry);
        }

        return $this;
    }
}
