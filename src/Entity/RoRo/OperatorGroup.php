<?php

namespace App\Entity\RoRo;

use App\Entity\IdTrait;
use App\Form\Validator as AppAssert;
use App\Repository\RoRo\OperatorGroupRepository;
use App\Workflow\FormWizardManager;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[UniqueEntity(fields: ['name'], message: 'operator-groups.name.unique', groups: ['admin_add_operator_group', 'admin_edit_operator_group'])]
#[ORM\Table(name: 'roro_operator_group')]
#[ORM\Entity(repositoryClass: OperatorGroupRepository::class)]
class OperatorGroup
{
    use IdTrait;

    #[Assert\NotNull(message: 'operator-groups.name.not-null', groups: ['admin_add_operator_group', 'admin_edit_operator_group'])]
    #[Assert\Length(min: 3, minMessage: 'operator-groups.name.min-length', groups: ['admin_add_operator_group', 'admin_edit_operator_group'])]
    #[AppAssert\OperatorGroupNameNotPrefixOfExisting(groups: ["admin_add_operator_group", "admin_edit_operator_group"])]
    #[Groups([FormWizardManager::NOTIFICATION_BANNER_NORMALIZER_GROUP])]
    #[ORM\Column(type: Types::STRING, length: 255)]
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
