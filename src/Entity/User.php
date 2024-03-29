<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Tests\Fixtures\Metadata\Get;
use App\Repository\UserRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Validator\Constraints\Expression;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\Regex;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: '`user`')]
#[ORM\UniqueConstraint(name: 'UNIQ_IDENTIFIER_ACCESS_TOKEN', fields: ['accessToken'])]
#[ApiResource(
    operations: [
        new Get(),
        new GetCollection(),
        new Post(securityPostDenormalize: "(is_granted('ROLE_COMPANY_ADMIN') and object.getRole().value == 'ROLE_USER') or is_granted('ROLE_SUPER_ADMIN')"),
        new Delete(security: "is_granted('ROLE_SUPER_ADMIN')"),
    ]
)]
class User implements UserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 100)]
    #[Length(min: 3, max: 100)]
    #[Regex(pattern: "/.*[A-Z]+.*/", message: 'Name must contain at least one uppercase letter.')]
    #[Regex(pattern: "/^[A-Za-z\s]+$/", message: 'Name must contain only letters and spaces.')]
    private ?string $name = null;

    #[ORM\Column(enumType: Role::class)]
    private ?Role $role = Role::ROLE_USER;

    #[ORM\Column(length: 180)]
    private ?string $accessToken = null;

    #[ORM\ManyToOne(inversedBy: 'users')]
    #[ORM\JoinColumn(nullable: true)]
    #[Expression(
        expression: "value == null or this.getRole().value in ['ROLE_USER', 'ROLE_COMPANY_ADMIN']",
        message: "Super Admin role can not have company"
    )]
    private ?Company $company = null;

    public function __construct()
    {
        $this->accessToken = bin2hex(random_bytes(10));
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getRole(): ?Role
    {
        return $this->role;
    }

    public function setRole(Role $role): static
    {
        $this->role = $role;

        return $this;
    }

    /**
     * @see UserInterface
     *
     * @return list<string>
     */
    public function getRoles(): array
    {
        return array($this->getRole()->value);
    }

    public function getAccessToken(): ?string
    {
        return $this->accessToken;
    }

    public function getCompany(): ?Company
    {
        return $this->company;
    }

    public function setCompany(?Company $company): static
    {
        $this->company = $company;

        return $this;
    }

    /**
     * A visual identifier that represents this user.
     *
     * @see UserInterface
     */
    public function getUserIdentifier(): string
    {
        return (string) $this->accessToken;
    }

    /**
     * @see UserInterface
     */
    public function eraseCredentials(): void
    {
        // If you store any temporary, sensitive data on the user, clear it here
        // $this->plainPassword = null;
    }

}
