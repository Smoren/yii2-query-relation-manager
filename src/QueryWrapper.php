<?php

namespace Smoren\QueryRelationManager\Yii2;

use Smoren\QueryRelationManager\Base\QueryWrapperInterface;
use yii\db\Connection;
use yii\db\Query;

/**
 * Реализация обертки ActiveQuery для QueryRelationManager
 * @author Smoren <ofigate@gmail.com>
 * @inheritDoc
 */
class QueryWrapper implements QueryWrapperInterface
{
    /**
     * Объект ActiveQuery
     * @var Query
     */
    protected Query $query;

    /**
     * QueryWrapper constructor.
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
     * @param Connection|null $db объект подключения к БД
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
