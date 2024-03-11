<?php

namespace App\Tests;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;

class CompanyTest extends ApiTestCase
{
    public function testGetCompanies(): void
    {
        $response = static::createClient()->request('GET', '/api/companies.jsonld');

        $this->assertResponseIsSuccessful();
        $this->assertJsonContains([
            '@context' => '/api/contexts/Company',
            '@id' => '/api/companies',
            '@type' => 'hydra:Collection',
            'hydra:totalItems' => 0,
            'hydra:member' => []
        ]);
    }


    public function testCanCreateCompany(): void
    {
        $response = static::createClient()->request('POST', '/api/companies', [
            'json' => [
                'name' => 'McDonalds',
                'users' => [],
            ],
            'headers' => ['accept' => ['application/ld+json'], 'content-type' => ['application/ld+json']],
        ]);

        $this->assertResponseStatusCodeSame(201);
        $this->assertJsonContains([
            "@context" => "/api/contexts/Company",
            "@id" => "/api/companies/4",
            "@type" => "Company",
//            "id" => 1,
            "name" => "Apple",
            "users" => [],
        ]);

    }


//    public function testGetCompanyById(): void
//    {
//        $response = static::createClient()->request('GET', '/api/companies/1.jsonld');
//
//        $this->assertResponseIsSuccessful();
//        $this->assertJsonContains([
//            '@context' => '/api/contexts/Company',
//            '@id' => '/api/companies',
//            '@type' => 'hydra:Collection',
//            'hydra:totalItems' => 0,
//            'hydra:member' => []
//        ]);
//    }
}
