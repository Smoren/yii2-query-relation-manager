<?php


namespace Smoren\Yii2\QueryRelationManager\ActiveRecord;

use Smoren\Yii2\QueryRelationManager\Base\QueryWrapperInterface;


class QueryWrapper implements QueryWrapperInterface
{
    /**
     * @var \yii\db\Query
     */
    protected $query;

    /**
     * QueryWrapper constructor.
     */
    public function __construct()
    {
        $this->query = new \yii\db\Query();
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
    public function join(string $type, array $mapTable, string $condition, array $extraJoinParams = []): QueryWrapperInterface
    {
        $this->query->join("{$type} join", $mapTable, $condition, $extraJoinParams);

        return $this;
    }

    /**
     * @inheritDoc
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
        return $this->createCommand()->getRawSql();
    }

    /**
     * @inheritDoc
     */
    public function getQuery()
    {
        return $this->query;
    }
}