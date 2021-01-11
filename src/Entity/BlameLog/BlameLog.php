<?php


namespace App\Entity\BlameLog;

use App\Entity\IdTrait;
use DateTime;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * BlameLog
 *
 * @ORM\Entity(repositoryClass="App\Repository\BlameLogRepository")
 */
class BlameLog
{
    const TYPE_INSERT = 'insert';
    const TYPE_DELETE = 'delete';
    const TYPE_UPDATE = 'update';

    use IdTrait;

    /**
     * @var string | null
     * @ORM\Column(type="string", length=255)
     * @Assert\NotBlank()
     */
    protected $class;

    /**
     * @var string
     * @ORM\Column(type="string", length=1023)
     */
    protected $description;

    /**
     * @ORM\Column(type="string")
     * @Assert\NotBlank()
     */
    protected $entityId;

    /**
     * @var string
     * @ORM\Column(type="string")
     */
    protected $userId;

    /**
     * @var DateTime
     * @ORM\Column(type="datetime");
     */
    protected $date;

    /**
     * @var string
     * @ORM\Column(type="string", length=8)
     */
    protected $type;

    /**
     * @var array | null
     * @ORM\Column(type="json", nullable=true)
     */
    protected $properties;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $associatedEntity;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $associatedId;

    /**
     * BlameLogEntry constructor.
     */
    public function __construct()
    {
        $this->date = new DateTime();
    }

    public function getClass(): ?string
    {
        return $this->class;
    }

    public function setClass(?string $class)
    {
        $this->class = $class;
        return $this;
    }

    public function getUserId(): ?string
    {
        return $this->userId;
    }

    public function setUserId(?string $userId)
    {
        $this->userId = $userId;
        return $this;
    }

    public function getDate(): DateTime
    {
        return $this->date;
    }

    public function setDate(DateTime $date)
    {
        $this->date = $date;
        return $this;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function setType(string $type)
    {
        $this->type = $type;
        return $this;
    }

    public function getProperties(): ?array
    {
        return $this->properties;
    }

    public function getPropertiesForDisplay(): ?array
    {
        switch($this->type) {
            case self::TYPE_INSERT :
                return array_map(function($a){return $a[1];}, $this->properties);
//            case self::TYPE_UPDATE :
//                return array_map(function($a){return [$a[0] => $a[1]];}, $this->properties);
        }
        return $this->properties;
    }

    public function setProperties(?array $properties)
    {
        $this->properties = $properties;
        return $this;
    }

    public function getEntityId()
    {
        return $this->entityId;
    }

    public function setEntityId($entityId)
    {
        $this->entityId = $entityId;
        return $this;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function setDescription(string $description)
    {
        $this->description = substr($description, 0, 1023);
        return $this;
    }

    public function getAssociatedEntity(): ?string
    {
        return $this->associatedEntity;
    }

    public function setAssociatedEntity(?string $associatedEntity): self
    {
        $this->associatedEntity = $associatedEntity;

        return $this;
    }

    public function getAssociatedId(): ?string
    {
        return $this->associatedId;
    }

    public function setAssociatedId(?string $associatedId): self
    {
        $this->associatedId = $associatedId;

        return $this;
    }
}
