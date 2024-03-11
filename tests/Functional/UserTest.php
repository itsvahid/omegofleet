<?php

namespace App\Tests\Functional;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use App\Entity\Company;
use App\Entity\Role;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

class UserTest extends ApiTestCase
{
    private ?User $superAdmin = null;
    private EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        /** @var EntityManagerInterface $entityManager */
        $this->entityManager = self::getContainer()->get('doctrine')->getManager();
        $this->superAdmin = $this->createSuperAdmin();
    }

    public function testSuperAdminCanGetAllUsers(): void
    {
        $this->createUser('User one', Role::ROLE_USER, null);
        $this->createUser('User two', Role::ROLE_COMPANY_ADMIN, null);

        static::createClient()->request('GET', '/api/users', [
            'headers' => [
                'accept' => ['application/ld+json'],
                'content-type' => ['application/ld+json'],
                'Authorization' => ['Bearer ' . $this->superAdmin->getAccessToken()],
            ],
        ]);

        $this->assertResponseIsSuccessful();
        $this->assertJsonContains([
            '@context' => '/api/contexts/User',
            '@id' => '/api/users',
            '@type' => 'hydra:Collection',
            'hydra:totalItems' => 3,
        ]);
    }


    public function testCompanyAdminAndUserCanGetOnlySameCompanyUsers(): void
    {
        $company = $this->createCompany('Company 1');
        $companyAdmin = $this->createUser('User one', Role::ROLE_COMPANY_ADMIN, $company);
        $companyUser = $this->createUser('User two', Role::ROLE_USER, $company);

        $anotherCompany = $this->createCompany('Company 2');
        $this->createUser('User3', Role::ROLE_COMPANY_ADMIN, $anotherCompany);
        $this->createUser('User4', Role::ROLE_USER, $anotherCompany);

        // Company admin can get only users in their company
        static::createClient()->request('GET', '/api/users', [
            'headers' => [
                'accept' => ['application/ld+json'],
                'content-type' => ['application/ld+json'],
                'Authorization' => ['Bearer ' . $companyAdmin->getAccessToken()],
            ],
        ]);

        $this->assertResponseIsSuccessful();
        $this->assertJsonContains([
            '@context' => '/api/contexts/User',
            '@id' => '/api/users',
            '@type' => 'hydra:Collection',
            'hydra:totalItems' => 2,
        ]);

        // User can get only users in their company
        static::createClient()->request('GET', '/api/users', [
            'headers' => [
                'accept' => ['application/ld+json'],
                'content-type' => ['application/ld+json'],
                'Authorization' => ['Bearer ' . $companyUser->getAccessToken()],
            ],
        ]);

        $this->assertResponseIsSuccessful();
        $this->assertJsonContains([
            '@context' => '/api/contexts/User',
            '@id' => '/api/users',
            '@type' => 'hydra:Collection',
            'hydra:totalItems' => 2,
        ]);
    }

    /**
     * @dataProvider providerOnlySuperAdminAndCompanyAdminCanCreateUserData
     */
    public function testOnlySuperAdminAndCompanyAdminCanCreateUserWithRoles(Role $creatorRole, Role $newUserRole, int $statusCode)
    {
        $creator = $this->createUser('creator user', $creatorRole, null);

        $this->requestCreateUser('User', $newUserRole, $creator);
        $this->assertResponseStatusCodeSame($statusCode);
    }

    /**
     * @dataProvider providerOnlySuperAdminCanDeleteUserData
     */
    public function testOnlySuperAdminCanDeleteUser(Role $userDeleterRole, int $statusCode)
    {
        $userDeleter = $this->createUser('deleter', $userDeleterRole);
        $userToDelete = $this->createUser('sample user', Role::ROLE_USER, null);

        $this->requestDeleteUser(userToDelete: $userToDelete, userDeleter: $userDeleter);
        $this->assertResponseStatusCodeSame($statusCode);
    }

    public function testUserNameShouldBeMinThreeCharacters(): void
    {
        $this->requestCreateUser(name: 'Me', role: Role::ROLE_USER, creator: $this->superAdmin);

        $this->assertResponseIsUnprocessable();
    }

    public function testUserNameShouldBeMaxHundredCharacters(): void
    {
        $aVeryLongName = str_repeat('x', 101);
        $this->requestCreateUser(name: $aVeryLongName, role: Role::ROLE_USER, creator: $this->superAdmin);

        $this->assertResponseIsUnprocessable();
    }


    public function testNameRequiresAtLeastOneUppercase(): void
    {
        $this->requestCreateUser(name: 'name', role: Role::ROLE_USER, creator: $this->superAdmin);

        $this->assertResponseIsUnprocessable();
        $this->assertJsonContains([
            "@type" => "ConstraintViolationList",
            "violations" => [
                [
                    "propertyPath" => "name",
                    "message" => "Name must contain at least one uppercase letter.",
                ]
            ],
            "hydra:description" => "name: Name must contain at least one uppercase letter.",
        ]);
    }

    /**
     * @dataProvider providerNameMustConsistOfOnlyLettersAndSpacesData
     */
    public function testNameMustConsistOfOnlyLettersAndSpaces(int $statusCode, string $name): void
    {
        $this->requestCreateUser(name: $name, role: Role::ROLE_USER, creator: $this->superAdmin);

        $this->assertResponseStatusCodeSame($statusCode);
        if ($statusCode === 422) {
            $this->assertJsonContains([
                "@type" => "ConstraintViolationList",
                "violations" => [
                    [
                        "propertyPath" => "name",
                        "message" => "Name must contain only letters and spaces.",
                    ]
                ],
                "hydra:description" => "name: Name must contain only letters and spaces.",
            ]);
        }
    }

    private function createSuperAdmin(): User
    {
        $superAdmin = new User();
        $superAdmin->setRole(Role::ROLE_SUPER_ADMIN);
        $superAdmin->setName('Super Admin');

        $this->entityManager->persist($superAdmin);
        $this->entityManager->flush();

        return $superAdmin;
    }

    private function createUser(string $name, Role $role, ?Company $company = null): User
    {
        $user = new User();
        $user->setRole($role);
        $user->setName($name);
        $user->setCompany($company);

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        return $user;
    }

    private function createCompany(string $name): Company
    {
        $company = new Company();
        $company->setName($name);

        $this->entityManager->persist($company);
        $this->entityManager->flush();

        return $company;
    }

    private function requestCreateUser(string $name, Role $role, User $creator): ResponseInterface
    {
        return static::createClient()->request('POST', '/api/users', [
            'json' => [
                'name' => $name,
                'role' => $role->value,
            ],
            'headers' => [
                'accept' => ['application/ld+json'],
                'content-type' => ['application/ld+json'],
                'Authorization' => ['Bearer ' . $creator->getAccessToken()],
            ],
        ]);
    }

    private function requestDeleteUser(User $userToDelete, User $userDeleter): ResponseInterface
    {
        return static::createClient()->request('DELETE', '/api/users/' . $userToDelete->getId(), [
            'headers' => [
                'accept' => ['application/ld+json'],
                'content-type' => ['application/ld+json'],
                'Authorization' => ['Bearer ' . $userDeleter->getAccessToken()],
            ],
        ]);
    }

    private function providerOnlySuperAdminAndCompanyAdminCanCreateUserData(): array
    {
        return [
            [Role::ROLE_SUPER_ADMIN, Role::ROLE_SUPER_ADMIN, 201],
            [Role::ROLE_SUPER_ADMIN, Role::ROLE_COMPANY_ADMIN, 201],
            [Role::ROLE_SUPER_ADMIN, Role::ROLE_USER, 201],
            [Role::ROLE_COMPANY_ADMIN, Role::ROLE_USER, 201],
            [Role::ROLE_COMPANY_ADMIN, Role::ROLE_SUPER_ADMIN, 403],
            [Role::ROLE_COMPANY_ADMIN, Role::ROLE_COMPANY_ADMIN, 403],
            [Role::ROLE_USER, Role::ROLE_SUPER_ADMIN, 403],
            [Role::ROLE_USER, Role::ROLE_COMPANY_ADMIN, 403],
            [Role::ROLE_USER, Role::ROLE_USER, 403],
        ];
    }

    private function providerOnlySuperAdminCanDeleteUserData(): array
    {
        return [
            [Role::ROLE_SUPER_ADMIN, 204],
            [Role::ROLE_COMPANY_ADMIN, 403],
            [Role::ROLE_USER, 403]
        ];
    }

    private function providerNameMustConsistOfOnlyLettersAndSpacesData(): array
    {
        return [
            [422, 'Name with number 1'],
            [422, 'Name with +'],
            [422, 'Name with -/@$!'],
            [422, 'Name with number 1'],
            [201, 'Valid name'],
            [201, 'ValidName'],
            [201, 'Name'],
        ];
    }
}
