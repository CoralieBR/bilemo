<?php

namespace App\Entity;

use App\Entity\Trait\DateTrait;
use App\Repository\CustomerRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;
#[ORM\Entity(repositoryClass: CustomerRepository::class)]
class Customer
{
    use DateTrait;
    
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['getCustomers'])]
    private ?int $id = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['getCustomers'])]
    #[Assert\Length(max: 255, maxMessage: 'Le prénom ne doit pas excéder {{ limit }} caractères de long.')]
    private ?string $firstName = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['getCustomers'])]
    #[Assert\Length(max: 255, maxMessage: 'Le nom ne doit pas excéder {{ limit }} caractères de long.')]
    private ?string $name = null;

    #[ORM\Column(length: 255)]
    #[Groups(['getCustomers'])]
    #[Assert\NotBlank(message: "L'email est obligatoire.")]
    #[Assert\Email(message: "Le format de l'email n'est pas valide.")]
    #[Assert\Length(max: 255, maxMessage: "L'email ne doit pas excéder {{ limit }} caractères de long.")]
    private ?string $email = null;

    #[ORM\Column(length: 10, nullable: true)]
    #[Groups(['getCustomers'])]
    #[Assert\Length(max: 10, maxMessage: 'Le code postal ne doit pas excéder {{ limit }} caractères de long.')]
    private ?string $postCode = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['getCustomers'])]
    private ?string $gender = null;

    #[ORM\Column(nullable: true)]
    #[Groups(['getCustomers'])]
    private ?int $age = null;

    #[ORM\ManyToOne(inversedBy: 'customers')]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['getCustomers'])]
    private ?Platform $platform = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getFirstName(): ?string
    {
        return $this->firstName;
    }

    public function setFirstName(?string $firstName): static
    {
        $this->firstName = $firstName;

        return $this;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;

        return $this;
    }

    public function getPostCode(): ?string
    {
        return $this->postCode;
    }

    public function setPostCode(?string $postCode): static
    {
        $this->postCode = $postCode;

        return $this;
    }

    public function getGender(): ?string
    {
        return $this->gender;
    }

    public function setGender(?string $gender): static
    {
        $this->gender = $gender;

        return $this;
    }

    public function getAge(): ?int
    {
        return $this->age;
    }

    public function setAge(?int $age): static
    {
        $this->age = $age;

        return $this;
    }

    public function getPlatform(): ?Platform
    {
        return $this->platform;
    }

    public function setPlatform(?Platform $platform): static
    {
        $this->platform = $platform;

        return $this;
    }
}
