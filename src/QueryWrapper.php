<?php

namespace Smoren\QueryRelationManager\Yii2;

use Smoren\QueryRelationManager\Base\QueryWrapperInterface;
use yii\db\Connection;
use yii\db\Query;

/**
 * ActiveQuery wrapper implementation for QueryRelationManager
 * @author Smoren <ofigate@gmail.com>
 * @inheritDoc
 */
class QueryWrapper implements QueryWrapperInterface
{
    /**
     * ActiveQuery instance
     * @var Query
     */
    protected Query $query;

    /**
     * QueryWrapper constructor
     */
    public function __construct()
    {
        $this->query = new Query();
    }

    /**
     * @inheritDoc
     */
    public function select(array $arSelect): QueryWrapperInterface
    {
        $this->query->select($arSelect);

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function from(array $mapFrom): QueryWrapperInterface
    {
        $this->query->from($mapFrom);

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function join(
        string $type,
        array $mapTable,
        string $condition,
        array $extraJoinParams = []
    ): QueryWrapperInterface {
        $this->query->join("{$type} join", $mapTable, $condition, $extraJoinParams);

        return $this;
    }

    /**
     * @inheritDoc
     * @param Connection|null $db DB connection instance
     */
    public function all($db = null): array
    {
        return $this->query->all($db);
    }

    /**
     * @inheritDoc
     */
    public function getRawSql(): string
    {
        return $this->query->createCommand()->getRawSql();
    }

    /**
     * @inheritDoc
     * @return Query
     */
    public function getQuery(): Query
    {
        return $this->query;
    }
}
