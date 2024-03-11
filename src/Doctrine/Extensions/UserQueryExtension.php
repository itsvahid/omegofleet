<?php

namespace App\Doctrine\Extensions;

use ApiPlatform\Doctrine\Orm\Extension\QueryCollectionExtensionInterface;
use ApiPlatform\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use ApiPlatform\Metadata\Operation;
use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\QueryBuilder;
use Symfony\Bundle\SecurityBundle\Security;

readonly class UserQueryExtension implements QueryCollectionExtensionInterface
{
    public function __construct(
        private Security $security,
        private UserRepository $userRepository,
    )
    {
    }

    public function applyToCollection(QueryBuilder $queryBuilder, QueryNameGeneratorInterface $queryNameGenerator, string $resourceClass, Operation $operation = null, array $context = []): void
    {
        $this->addWhere($queryBuilder, $resourceClass);
    }

    private function addWhere(QueryBuilder $queryBuilder, string $resourceClass): void
    {
        if (User::class !== $resourceClass || $this->security->isGranted('ROLE_SUPER_ADMIN') || null === $user = $this->security->getUser()) {
            return;
        }

        $userEntity = $this->userRepository->findOneBy(['accessToken' => $user->getUserIdentifier()]);

        $rootAlias = $queryBuilder->getRootAliases()[0];
        $queryBuilder->andWhere(sprintf('%s.company = :company_id', $rootAlias));
        $queryBuilder->setParameter('company_id', $userEntity->getCompany()->getId());
    }
}