<?php


namespace App\Entity;


use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

trait NoteTrait
{
    use IdTrait;

    /**
     * @ORM\Column(type="text", nullable=false)
     * @Assert\NotNull(message="common.choice.not-null", groups={"admin_comment"})
     */
    private ?string $note;

    /**
     * username of creator
     * @ORM\Column(type="string", length=255, nullable=false)
     */
    private ?string $createdBy;

    /**
     * @ORM\Column(type="datetime", nullable=false)
     */
    private ?\DateTime $createdAt;

    public function getNote(): ?string
    {
        return $this->note;
    }

    public function setNote(?string $note): self
    {
        $this->note = $note;
        return $this;
    }

    /**
     * @param string|null $createdBy
     * @return NoteTrait
     */
    public function setCreatedBy(?string $createdBy): self
    {
        $this->createdBy = $createdBy;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getCreatedBy(): ?string
    {
        return $this->createdBy;
    }

    /**
     * @param \DateTime|null $createdAt
     * @return NoteTrait
     */
    public function setCreatedAt(?\DateTime $createdAt): self
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    /**
     * @return \DateTime|null
     */
    public function getCreatedAt(): ?\DateTime
    {
        return $this->createdAt;
    }
}