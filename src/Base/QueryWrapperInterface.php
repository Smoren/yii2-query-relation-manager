<?php


namespace Smoren\Yii2\QueryRelationManager\Base;


interface QueryWrapperInterface
{
    /**
     * @param array $arSelect
     * @return $this
     */
    public function select(array $arSelect): self;

    /**
     * @param array $mapFrom
     * @return $this
     */
    public function from(array $mapFrom): self;

    /**
     * @param string $type
     * @param array $mapTable
     * @param string $condition
     * @param array $extraJoinParams
     * @return $this
     */
    public function join(string $type, array $mapTable, string $condition, array $extraJoinParams = []): self;

    /**
     * @param null $db
     * @return array
     */
    public function all($db = null): array;

    /**
     * @return mixed
     */
    public function getQuery();

    /**
     * @return string
     */
    public function getRawSql(): string;
}