<?php

namespace Functional;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use ApiPlatform\Validator\Exception\ValidationException;
use App\Entity\Company;
use App\Entity\Role;
use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpClient\Exception\ClientException;
use Symfony\Contracts\HttpClient\ResponseInterface;

class CompanyTest extends ApiTestCase
{

    private ?User $superAdmin = null;

    protected function setUp(): void
    {
        $this->superAdmin = $this->createSuperAdmin();
    }

    public function testGetCompanies(): void
    {
        static::createClient()->request('GET', '/api/companies.jsonld');

        $this->assertResponseIsSuccessful();
        $this->assertJsonContains([
            '@context' => '/api/contexts/Company',
            '@id' => '/api/companies',
            '@type' => 'hydra:Collection',
            'hydra:totalItems' => 0,
            'hydra:member' => []
        ]);
    }

    public function testOnlyRoleSuperAdminCanCreateCompany(): void
    {
        $this->requestCreateCompany(name: 'Apple', userAccessToken: '');

        $this->assertResponseStatusCodeSame(401);
    }

    public function testCanCreateCompany(): void
    {
        $this->requestCreateCompany(name: 'Apple', userAccessToken: $this->superAdmin->getAccessToken());

        $this->assertResponseStatusCodeSame(201);
        $this->assertJsonContains([
            "@context" => "/api/contexts/Company",
            "@type" => "Company",
            "name" => "Apple",
            "users" => [],
        ]);

    }

    public function testCompanyNameShouldBeMinFiveCharacters(): void
    {
        $this->requestCreateCompany(name: 'BMW', userAccessToken: $this->superAdmin->getAccessToken());

        $this->assertResponseIsUnprocessable();
    }

    public function testCompanyNameShouldBeMaxHundredCharacters(): void
    {
        $aVeryLongName = str_repeat('x', 101);
        $this->requestCreateCompany(name: $aVeryLongName, userAccessToken: $this->superAdmin->getAccessToken());

        $this->assertResponseIsUnprocessable();
    }

    public function testGetCompanyById(): void
    {
        // Create a sample company
        $response = $this->requestCreateCompany(name: 'Apple', userAccessToken: $this->superAdmin->getAccessToken())->getContent();
        $company = json_decode($response, true);

        static::createClient()->request('GET', $company['@id'] . '.jsonld');

        $this->assertResponseIsSuccessful();
        $this->assertJsonContains([
            "@context" => "/api/contexts/Company",
            "@type" => "Company",
            "@id" => $company['@id'],
            "id" => $company['id'],
            "name" => "Apple",
            "users" => [],
        ]);
    }

    private function createSuperAdmin(): User
    {
        /** @var EntityManagerInterface $entityManager */
        $entityManager = self::getContainer()->get('doctrine')->getManager();

        $superAdmin = new User();
        $superAdmin->setRole(Role::ROLE_SUPER_ADMIN);
        $superAdmin->setName('Super Admin');

        $entityManager->persist($superAdmin);
        $entityManager->flush();

        return $superAdmin;
    }

    private function requestCreateCompany(string $name, array $users = [], $userAccessToken = ''): ResponseInterface
    {
        return static::createClient()->request('POST', '/api/companies', [
            'json' => [
                'name' => $name,
                'users' => $users,
            ],
            'headers' => [
                'accept' => ['application/ld+json'],
                'content-type' => ['application/ld+json'],
                'Authorization' => ['Bearer ' . $userAccessToken],
            ],
        ]);
    }

    // TODO testCompanyNameMustBeUnique

}
