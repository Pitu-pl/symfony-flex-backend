<?php
declare(strict_types=1);
/**
 * /src/Repository/UserRepository.php
 *
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
namespace App\Repository;

use App\Entity\User as Entity;

/** @noinspection PhpHierarchyChecksInspection */
/**
 * Class UserRepository
 *
 * @package App\Repository
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 *
 * @codingStandardsIgnoreStart
 *
 * @method Entity|null find(string $id, string $lockMode = null, string $lockVersion = null)
 * @method Entity|null findOneBy(array $criteria, array $orderBy = null)
 * @method Entity[]    findBy(array $criteria, array $orderBy = null, int $limit = null, int $offset = null)
 * @method Entity[]    findByAdvanced(array $criteria, array $orderBy = null, int $limit = null, int $offset = null, array $search = null): array
 * @method Entity[]    findAll()
 *
 * @codingStandardsIgnoreEnd
 */
class UserRepository extends BaseRepository
{
    /**
     * @var string
     */
    protected static $entityName = Entity::class;

    /**
     * Names of search columns.
     *
     * @var string[]
     */
    protected static $searchColumns = ['username', 'firstname', 'surname', 'email'];

    /**
     * Method to check if specified username is available or not.
     *
     * @param string      $username
     * @param string|null $id
     *
     * @return bool
     *
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function isUsernameAvailable(string $username, string $id = null): bool
    {
        // Build query
        $query = $this
            ->createQueryBuilder('u')
            ->select('u')
            ->where('u.username = :username')
            ->setParameter('username', $username);

        if ($id !== null) {
            $query
                ->andWhere('u.id <> :id')
                ->setParameter('id', $id);
        }

        return $query->getQuery()->getOneOrNullResult() === null;
    }

    /**
     * Method to check if specified email is available or not.
     *
     * @param string      $email Email to check
     * @param string|null $id    User id to ignore
     *
     * @return bool
     *
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function isEmailAvailable(string $email, string $id = null): bool
    {
        // Build query
        $query = $this
            ->createQueryBuilder('u')
            ->select('u')
            ->where('u.email = :email')
            ->setParameter('email', $email);

        if ($id !== null) {
            $query
                ->andWhere('u.id <> :id')
                ->setParameter('id', $id);
        }

        return $query->getQuery()->getOneOrNullResult() === null;
    }
}
