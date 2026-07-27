<?php

declare(strict_types=1);

namespace AppBundle\IntegrationTests\Association\Entity\Repository;

use Afup\Tests\Support\IntegrationTestCase;
use AppBundle\Association\Entity\PersonneMorale;
use AppBundle\Association\Entity\Repository\PersonneMoraleRepository;
use Doctrine\DBAL\Connection;

final class PersonneMoraleRepositoryTest extends IntegrationTestCase
{
    public function testCountByStatusCountsCompaniesNotPhysicalPersons(): void
    {
        $personneMoraleRepository = self::getContainer()->get(PersonneMoraleRepository::class);
        $connection = self::getContainer()->get(Connection::class);

        // Une seule personne morale active, deux en attente.
        $personneMoraleRepository->save($this->buildPersonneMorale(PersonneMorale::STATUS_ACTIVE));
        $personneMoraleRepository->save($this->buildPersonneMorale(PersonneMorale::STATUS_PENDING));
        $personneMoraleRepository->save($this->buildPersonneMorale(PersonneMorale::STATUS_PENDING));

        // Cinq personnes physiques actives, aucune en attente : des comptes
        // volontairement différents de ceux des personnes morales ci-dessus,
        // pour détecter toute confusion entre les deux tables.
        for ($i = 0; $i < 5; $i++) {
            $this->insertPhysicalPerson($connection, "physique{$i}@example.com", PersonneMorale::STATUS_ACTIVE);
        }

        self::assertSame(1, $personneMoraleRepository->countByStatus(PersonneMorale::STATUS_ACTIVE));
        self::assertSame(2, $personneMoraleRepository->countByStatus(PersonneMorale::STATUS_PENDING));
    }

    private function buildPersonneMorale(int $status): PersonneMorale
    {
        static $counter = 0;
        $counter++;

        $personneMorale = new PersonneMorale();
        $personneMorale->firstName = 'Prénom';
        $personneMorale->lastName = 'Nom';
        $personneMorale->email = "morale{$counter}@example.com";
        $personneMorale->companyName = 'Société de test';
        $personneMorale->siret = '12345678901234';
        $personneMorale->address = '1 rue du Test';
        $personneMorale->zipCode = '75000';
        $personneMorale->city = 'Paris';
        $personneMorale->country = 'FR';
        $personneMorale->status = $status;

        return $personneMorale;
    }

    private function insertPhysicalPerson(Connection $connection, string $email, int $status): void
    {
        $connection->insert('afup_personnes_physiques', [
            'roles' => '[]',
            'adresse' => '1 rue du Test',
            'email' => $email,
            'etat' => $status,
        ]);
    }
}
