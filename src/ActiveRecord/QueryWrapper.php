<?php


namespace Smoren\Yii2\QueryRelationManager\ActiveRecord;

use Smoren\Yii2\QueryRelationManager\Base\QueryWrapperInterface;


class QueryWrapper implements QueryWrapperInterface
{
    /**
     * @var yii\db\Query
     */
    protected $query;

    public function __construct()
    {
        $this->query = new yii\db\Query();
    }

    public function select(array $arSelect): QueryWrapperInterface
    {
        $this->query->select($arSelect);

        return $this;
    }

    public function from(array $mapFrom): QueryWrapperInterface
    {
        $this->query->from($mapFrom);

        return $this;
    }

    public function join(string $type, array $mapTable, string $condition, array $extraJoinParams = []): QueryWrapperInterface
    {
        $this->query->join("{$type} join", $mapTable, $condition, $extraJoinParams);

        return $this;
    }

    public function all($db = null): array
    {
        return $this->query->all($db);
    }

    public function getRawSql(): string
    {
        return $this->createCommand()->getRawSql();
    }

    public function getQuery()
    {
        return $this->query;
    }
}