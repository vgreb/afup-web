<?php

declare(strict_types=1);

namespace AppBundle\Association\Entity\Repository;

use AppBundle\Association\Entity\PersonneMorale;
use AppBundle\Association\MemberType;
use AppBundle\Doctrine\EntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use InvalidArgumentException;
use Webmozart\Assert\Assert;

/**
 * @extends EntityRepository<PersonneMorale>
 */
class PersonneMoraleRepository extends EntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PersonneMorale::class);
    }

    /**
     * @return PersonneMorale[]
     */
    public function findDisplayableCompanies(): array
    {
        $companies = $this->findBy(['publicProfileEnabled' => true]);
        $this->hydrateLastSubscriptions($companies);

        return array_values(array_filter(
            $companies,
            static fn(PersonneMorale $personneMorale): bool => $personneMorale->hasUpToDateMembershipFee(),
        ));
    }

    public function findById(int $id): ?PersonneMorale
    {
        $personneMorale = $this->find($id);
        if ($personneMorale instanceof PersonneMorale) {
            $this->hydrateLastSubscriptions([$personneMorale]);
        }

        return $personneMorale;
    }

    /**
     * @return PersonneMorale[]
     */
    public function loadAll(): array
    {
        $companies = $this->findAll();
        $this->hydrateLastSubscriptions($companies);

        return $companies;
    }

    /**
     * @return PersonneMorale[]
     */
    public function search(string $sort = 'name', string $direction = 'asc', ?string $filter = null, bool $onlyDisplayActive = true): array
    {
        Assert::inArray($direction, ['asc', 'desc']);
        $sorts = [
            'name' => ['raison_sociale'],
            'status' => ['etat', 'raison_sociale'],
        ];
        Assert::keyExists($sorts, $sort);

        $orderBy = implode(', ', array_map(static fn(string $field): string => $field . ' ' . $direction, $sorts[$sort]));

        $wheres = [];
        $params = [];

        if ($filter) {
            $filters = array_values(array_filter(array_map(trim(...), explode(' ', $filter))));
            $ors = [];
            foreach ($filters as $i => $value) {
                $ors[] = "LOWER(raison_sociale) LIKE LOWER(:filter$i) OR ville LIKE :filter$i";
                $params["filter$i"] = '%' . $value . '%';
            }
            if ($ors !== []) {
                $wheres[] = '(' . implode(' OR ', $ors) . ')';
            }
        }
        if ($onlyDisplayActive) {
            $wheres[] = 'etat = :status';
            $params['status'] = PersonneMorale::STATUS_ACTIVE;
        }

        $sql = 'SELECT id FROM afup_personnes_morales';
        if ($wheres !== []) {
            $sql .= ' WHERE ' . implode(' AND ', $wheres);
        }
        $sql .= ' ORDER BY ' . $orderBy;

        $ids = array_map(self::toInt(...), $this->getEntityManager()->getConnection()->executeQuery($sql, $params)->fetchFirstColumn());

        if ($ids === []) {
            return [];
        }

        $entitiesById = [];
        foreach ($this->findBy(['id' => $ids]) as $entity) {
            $entitiesById[(int) $entity->id] = $entity;
        }

        return array_values(array_filter(array_map(static fn(int $id): ?PersonneMorale => $entitiesById[$id] ?? null, $ids)));
    }

    /**
     * @return array<int, int>
     */
    public function countActiveByCompany(): array
    {
        $rows = $this->getEntityManager()->getConnection()->executeQuery(
            'SELECT id_personne_morale, COUNT(id) AS nb FROM afup_personnes_physiques GROUP BY id_personne_morale',
        )->fetchAllAssociative();

        $result = [];
        foreach ($rows as $row) {
            $result[self::toInt($row['id_personne_morale'])] = self::toInt($row['nb']);
        }

        return $result;
    }

    public function remove(PersonneMorale $personneMorale): void
    {
        $nbCotisations = self::toInt($this->getEntityManager()->getConnection()->executeQuery(
            'SELECT COUNT(*) AS nb FROM afup_cotisations WHERE type_personne = :memberType AND id_personne = :id',
            ['memberType' => MemberType::MemberCompany->value, 'id' => $personneMorale->id],
        )->fetchOne());
        if (0 < $nbCotisations) {
            throw new InvalidArgumentException('Impossible de supprimer une personne morale qui a des cotisations');
        }

        $nbUsers = self::toInt($this->getEntityManager()->getConnection()->executeQuery(
            'SELECT COUNT(*) AS nb FROM afup_personnes_physiques WHERE id_personne_morale = :id',
            ['id' => $personneMorale->id],
        )->fetchOne());
        if (0 < $nbUsers) {
            throw new InvalidArgumentException('Impossible de supprimer une personne morale qui a des membres');
        }

        $this->delete($personneMorale);
    }

    /**
     * @return array<int, string>
     */
    public function getList(): array
    {
        $rows = $this->getEntityManager()->getConnection()->executeQuery(
            'SELECT id, raison_sociale FROM afup_personnes_morales ORDER BY raison_sociale',
        )->fetchAllAssociative();

        $result = [];
        foreach ($rows as $row) {
            $id = self::toInt($row['id']);
            $result[$id] = sprintf('%s (id : %d)', self::toStringValue($row['raison_sociale']), $id);
        }

        return $result;
    }

    public function countByStatus(int $status): int
    {
        return self::toInt($this->getEntityManager()->getConnection()->executeQuery(
            'SELECT COUNT(id) AS nb FROM afup_personnes_morales WHERE etat = :status',
            ['status' => $status],
        )->fetchOne());
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function searchCompanyMemberSubscriptions(string $search): array
    {
        return $this->getEntityManager()->getConnection()->executeQuery(
            <<<'SQL'
SELECT pers.nom, pers.prenom, pers.email, pers.raison_sociale, cotis.*
FROM afup_personnes_morales AS pers
LEFT JOIN afup_cotisations AS cotis
  ON pers.id = cotis.id_personne
WHERE
  cotis.type_personne = 1
  AND (
    cotis.informations_reglement LIKE :like
    OR cotis.numero_facture LIKE :like
    OR cotis.commentaires LIKE :like
    OR pers.email LIKE :like
    OR pers.nom LIKE :like
    OR pers.prenom LIKE :like
  )
SQL,
            ['like' => "%{$search}%"],
        )->fetchAllAssociative();
    }

    /**
     * @param PersonneMorale[] $personnesMorales
     */
    private function hydrateLastSubscriptions(array $personnesMorales): void
    {
        if ($personnesMorales === []) {
            return;
        }

        $dates = $this->getLastSubscriptionEndDates();
        foreach ($personnesMorales as $personneMorale) {
            $personneMorale->lastSubscription = $dates[(int) $personneMorale->id] ?? null;
        }
    }

    /**
     * @return array<int, \DateTimeImmutable>
     */
    private function getLastSubscriptionEndDates(): array
    {
        $rows = $this->getEntityManager()->getConnection()->executeQuery(
            'SELECT id_personne, MAX(date_fin) AS date_fin FROM afup_cotisations WHERE type_personne = :type GROUP BY id_personne',
            ['type' => MemberType::MemberCompany->value],
        )->fetchAllAssociative();

        $dates = [];
        foreach ($rows as $row) {
            if (!is_numeric($row['date_fin'])) {
                continue;
            }
            $dates[self::toInt($row['id_personne'])] = new \DateTimeImmutable('@' . (string) $row['date_fin']);
        }

        return $dates;
    }

    private static function toInt(mixed $value): int
    {
        return is_numeric($value) ? (int) $value : 0;
    }

    private static function toStringValue(mixed $value): string
    {
        return is_string($value) ? $value : '';
    }
}
