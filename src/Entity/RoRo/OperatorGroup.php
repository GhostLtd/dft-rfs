<?php

namespace App\Entity\RoRo;

use App\Entity\IdTrait;
use App\Form\Validator as AppAssert;
use App\Repository\RoRo\OperatorGroupRepository;
use App\Workflow\FormWizardManager;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity(repositoryClass=OperatorGroupRepository::class)
 * @ORM\Table(name="roro_operator_group")
 * @UniqueEntity(
 *  fields={"name"},
 *  message="operator-groups.name.unique",
 *  groups={"admin_add_operator_group", "admin_edit_operator_group"}
 * )
 */
class OperatorGroup
{
    use IdTrait;

    /**
     * @ORM\Column(type="string", length=255)
     * @Assert\NotNull(message="operator-groups.name.not-null", groups={"admin_add_operator_group", "admin_edit_operator_group"})
     * @Assert\Length(minMessage="operator-groups.name.min-length", min=3, groups={"admin_add_operator_group", "admin_edit_operator_group"})
     * @AppAssert\OperatorGroupNameNotPrefixOfExisting(groups={"admin_add_operator_group", "admin_edit_operator_group"})
     *
     * @Groups({FormWizardManager::NOTIFICATION_BANNER_NORMALIZER_GROUP})
     */
    private ?string $name = null;

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name): self
    {
        $this->name = $name;
        return $this;
    }

    public function merge(?OperatorGroup $group): void
    {
        if (!$group) {
            return;
        }

        $this->name = $group->name;
    }
}
