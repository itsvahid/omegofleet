<?php

namespace Functional;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;

class CompanyTest extends ApiTestCase
{
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

    // TODO only ROLE_SUPER_ADMIN can create a company
    public function testCanCreateCompany(): void
    {
        static::createClient()->request('POST', '/api/companies', [
            'json' => [
                'name' => 'Apple',
                'users' => [],
            ],
            'headers' => ['accept' => ['application/ld+json'], 'content-type' => ['application/ld+json']],
        ]);

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
        static::createClient()->request('POST', '/api/companies', [
            'json' => [
                'name' => 'BMW',
                'users' => [],
            ],
            'headers' => ['accept' => ['application/ld+json'], 'content-type' => ['application/ld+json']],
        ]);

        $this->assertResponseIsUnprocessable();

    }

    public function testCompanyNameShouldBeMaxHundredCharacters(): void
    {
        $aVeryLongName = str_repeat('x', 101);
        static::createClient()->request('POST', '/api/companies', [
            'json' => [
                'name' => $aVeryLongName,
                'users' => [],
            ],
            'headers' => ['accept' => ['application/ld+json'], 'content-type' => ['application/ld+json']],
        ]);

        $this->assertResponseIsUnprocessable();
    }

    public function testGetCompanyById(): void
    {
        // Create a sample company
        $response = static::createClient()->request('POST', '/api/companies', [
            'json' => [
                'name' => 'Apple',
                'users' => [],
            ],
            'headers' => ['accept' => ['application/ld+json'], 'content-type' => ['application/ld+json']],
        ]);
        $company = json_decode($response->getContent(), true);

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

    // TODO testCompanyNameMustBeUnique

}
