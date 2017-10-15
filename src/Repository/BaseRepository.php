<?php
declare(strict_types=1);
/**
 * /src/Repository/BaseRepository.php
 *
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
namespace App\Repository;

use App\Repository\Traits\RepositoryMethodsTrait;
use App\Repository\Traits\RepositoryWrappersTrait;
use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\QueryBuilder;

/**
 * Class BaseRepository
 *
 * @package App\Repository
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
abstract class BaseRepository implements BaseRepositoryInterface
{
    // Traits
    use RepositoryMethodsTrait;
    use RepositoryWrappersTrait;

    const INNER_JOIN = 'innerJoin';
    const LEFT_JOIN = 'leftJoin';

    /**
     * Names of search columns.
     *
     * @var string[]
     */
    protected static $searchColumns = [];

    /**
     * @var string
     */
    protected static $entityName;

    /**
     * @var EntityManager
     */
    protected static $entityManager;

    /**
     * Joins that need to attach to queries, this is needed for to prevent duplicate joins on those.
     *
     * @var array
     */
    private static $joins = [
        self::INNER_JOIN => [],
        self::LEFT_JOIN  => [],
    ];

    /**
     * @var array
     */
    private static $processedJoins = [
        self::INNER_JOIN => [],
        self::LEFT_JOIN  => [],
    ];

    /**
     * @var array
     */
    private static $callbacks = [];

    /**
     * @var array
     */
    private static $processedCallbacks = [];

    /**
     * BaseRepository constructor.
     *
     * @param ManagerRegistry $managerRegistry
     */
    public function __construct(ManagerRegistry $managerRegistry)
    {
        $this->managerRegistry = $managerRegistry;
    }

    /**
     * Getter method for entity name.
     *
     * @return string
     */
    public function getEntityName(): string
    {
        return static::$entityName;
    }

    /**
     * Getter method for search columns of current entity.
     *
     * @return string[]
     */
    public function getSearchColumns(): array
    {
        return static::$searchColumns;
    }

    /**
     * With this method you can attach some custom functions for generic REST API find / count queries.
     *
     * @param QueryBuilder $queryBuilder
     *
     * @return void
     */
    public function processQueryBuilder(QueryBuilder $queryBuilder): void
    {
        // Reset processed joins and callbacks
        self::$processedJoins = [self::INNER_JOIN => [], self::LEFT_JOIN  => []];
        self::$processedCallbacks = [];

        $this->processJoins($queryBuilder);
        $this->processCallbacks($queryBuilder);
    }

    /**
     * Adds left join to current QueryBuilder query.
     *
     * @note Requires processJoins() to be run
     *
     * @see QueryBuilder::leftJoin() for parameters
     *
     * @param array $parameters
     *
     * @return BaseRepositoryInterface
     *
     * @throws \InvalidArgumentException
     */
    public function addLeftJoin(array $parameters): BaseRepositoryInterface
    {
        $this->addJoinToQuery('leftJoin', $parameters);

        return $this;
    }

    /**
     * Adds inner join to current QueryBuilder query.
     *
     * @note Requires processJoins() to be run
     *
     * @see QueryBuilder::innerJoin() for parameters
     *
     * @param array $parameters
     *
     * @return BaseRepositoryInterface
     *
     * @throws \InvalidArgumentException
     */
    public function addInnerJoin(array $parameters): BaseRepositoryInterface
    {
        $this->addJoinToQuery('innerJoin', $parameters);

        return $this;
    }

    /**
     * Method to add callback to current query builder instance which is calling 'processQueryBuilder' method. By
     * default this method is called from following core methods:
     *  - countAdvanced
     *  - findByAdvanced
     *  - findIds
     *
     * Note that every callback will get 'QueryBuilder' as in first parameter.
     *
     * @param callable   $callable
     * @param array|null $args
     *
     * @return BaseRepositoryInterface
     */
    public function addCallback(callable $callable, array $args = null): BaseRepositoryInterface
    {
        $args = $args ?? [];
        $hash = \sha1(\serialize(\array_merge([\spl_object_hash((object)$callable)], $args)));

        if (!\in_array($hash, self::$processedCallbacks, true)) {
            self::$callbacks[$hash] = [$callable, $args];
            self::$processedCallbacks[] = $hash;
        }

        return $this;
    }

    /**
     * Process defined joins for current QueryBuilder instance.
     *
     * @param QueryBuilder $queryBuilder
     */
    protected function processJoins(QueryBuilder $queryBuilder): void
    {
        /**
         * @var string $joinType
         * @var array  $joins
         */
        foreach (self::$joins as $joinType => $joins) {
            foreach ($joins as $key => $joinParameters) {
                $queryBuilder->$joinType(...$joinParameters);
            }

            self::$joins[$joinType] = [];
        }
    }

    /**
     * Process defined callbacks for current QueryBuilder instance.
     *
     * @param QueryBuilder $queryBuilder
     */
    protected function processCallbacks(QueryBuilder $queryBuilder): void
    {
        /**
         * @var callable $callback
         * @var array    $args
         */
        foreach (self::$callbacks as [$callback, $args]) {
            \array_unshift($args, $queryBuilder);

            $callback(...$args);
        }

        self::$callbacks = [];
    }

    /**
     * Method to add defined join(s) to current QueryBuilder query. This will keep track of attached join(s) so any of
     * those are not added multiple times to QueryBuilder.
     *
     * @note processJoins() method must be called for joins to actually be added to QueryBuilder. processQueryBuilder()
     *       method calls this method automatically.
     *
     * @see QueryBuilder::leftJoin()
     * @see QueryBuilder::innerJoin()
     *
     * @param string $type       Join type; leftJoin, innerJoin or join
     * @param array  $parameters Query builder join parameters.
     *
     * @throws \InvalidArgumentException
     */
    private function addJoinToQuery(string $type, array $parameters): void
    {
        if (!\array_key_exists($type, self::$joins)) {
            throw new \InvalidArgumentException('Join type \'' . $type . '\' is not supported.');
        }

        $comparision = \implode('|', $parameters);

        if (!\in_array($comparision, self::$processedJoins[$type], true)) {
            self::$joins[$type][] = $parameters;

            self::$processedJoins[$type][] = $comparision;
        }
    }
}