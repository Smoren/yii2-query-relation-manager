<?php


namespace Smoren\Yii2\QueryRelationManager\Base;


interface QueryWrapperInterface
{
    public function select(array $arSelect): self;

    public function from(array $mapFrom): self;

    public function join(string $type, array $mapTable, string $condition, array $extraJoinParams = []): self;

    public function all($db = null): array;

    public function getQuery();

    public function getRawSql(): string;
}