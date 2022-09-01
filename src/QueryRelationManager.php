<?php

namespace Smoren\QueryRelationManager\Yii2;

use Smoren\QueryRelationManager\Base\QueryRelationManagerBase;
use Smoren\QueryRelationManager\Base\QueryWrapperInterface;
use Smoren\QueryRelationManager\Base\QueryRelationManagerException;
use yii\db\ActiveQuery;
use yii\db\ActiveRecord;

/**
 * Реализация QueryRelationManager для ActiveRecord в Yii2
 * @author Smoren <ofigate@gmail.com>
 * @inheritDoc
 */
class QueryRelationManager extends QueryRelationManagerBase
{
    /**
     * @var QueryWrapper
     */
    protected QueryWrapperInterface $query;

    /**
     * Подключение отношения таблицы к запросу, используя данные из модели ActiveRecord
     * @param string $relationName имя отношения, прописанное в модели ActiveRecord
     * @param string $relationAlias псевдоним присоединяемой таблицы
     * @param string|null $parentAlias псевдоним таблицы, к которой очуществляется присоединение
     * (по умолчанию — основная таблица запроса)
     * @param string $joinType тип присоединения ("inner", "left", "right")
     * @param string|null $extraJoinCondition дополнительные условия присоединения
     * @param array<string, scalar> $extraJoinParams динамические значения дополнительных условий присодинения
     * @return $this
     * @throws QueryRelationManagerException
     */
    public function with(
        string $relationName,
        string $relationAlias,
        ?string $parentAlias = null,
        string $joinType = 'left',
        ?string $extraJoinCondition = null,
        array $extraJoinParams = []
    ): self {
        $mainTable = $this->tableCollection->getMainTable();

        $parentAlias = $parentAlias ?? $mainTable->alias;
        $parentClassName = $this->tableCollection->byAlias($parentAlias)->className;

        if(!class_exists($parentClassName)) {
            throw new QueryRelationManagerException("class {$parentClassName} not exists");
        }

        /** @var ActiveRecord $inst */
        $inst = new $parentClassName();
        if(!($inst instanceof ActiveRecord)) {
            throw new QueryRelationManagerException("class {$parentClassName} is not an instance of ActiveRecord");
        }

        $methodName = 'get'.ucfirst($relationName);
        if(!method_exists($inst, $methodName)) {
            throw new QueryRelationManagerException("method {$parentClassName}::{$methodName}() not exists");
        }

        /** @var ActiveQuery $activeQuery */
        $activeQuery = $inst->$methodName();
        if(!($activeQuery instanceof ActiveQuery)) {
            throw new QueryRelationManagerException(
                "method {$parentClassName}::{$methodName}() returned non-ActiveQuery instance"
            );
        }

        if($activeQuery->via) {
            throw new QueryRelationManagerException('cannot use relations with "via" section yet');
        }
        if(!is_array($activeQuery->link) || !count($activeQuery->link)) {
            throw new QueryRelationManagerException('cannot use relations without "link" section');
        }

        /** @var string $className */
        $className = $activeQuery->modelClass;

        if($activeQuery->multiple) {
            return $this->withMultiple(
                $relationName,
                $className,
                $relationAlias,
                $parentAlias,
                $activeQuery->link,
                $joinType,
                $extraJoinCondition,
                $extraJoinParams
            );
        } else {
            return $this->withSingle(
                $relationName,
                $className,
                $relationAlias,
                $parentAlias,
                $activeQuery->link,
                $joinType,
                $extraJoinCondition,
                $extraJoinParams
            );
        }
    }

    /**
     * @return QueryWrapper
     */
    public function getQuery(): QueryWrapper
    {
        return $this->query;
    }

    public function prepare(): QueryWrapper
    {
        /** @var QueryWrapper $wrapper */
        $wrapper = parent::prepare();
        return $wrapper;
    }

    /**
     * Возвращает имя таблицы по классу сущности ActiveRecord
     * @param string $className имя класса
     * @return string имя таблицы
     * @throws QueryRelationManagerException
     */
    protected function getTableName(string $className): string
    {
        if(!method_exists($className, 'tableName')) {
            throw new QueryRelationManagerException(
                "method {$className}::tableName() is not defined"
            );
        }

        return $className::tableName();
    }

    /**
     * Создает объект запроса
     * @return QueryWrapper
     */
    protected function createQuery(): QueryWrapper
    {
        return new QueryWrapper();
    }

    /**
     * Возвращает список полей таблицы
     * @param string $className имя класса ORM-модели
     * @return array<string>
     * @throws QueryRelationManagerException
     */
    protected function getTableFields(string $className): array
    {
        if(!class_exists($className)) {
            throw new QueryRelationManagerException("class {$className} is not defined");
        }

        if(!method_exists($className, 'getTableSchema')) {
            throw new QueryRelationManagerException("method {$className}::getTableSchema() is not defined");
        }

        /** @var array<string, mixed> $columns */
        $columns = $className::getTableSchema()->columns;
        return array_keys($columns);
    }

    /**
     * Возвращает поля первичного ключа таблицы
     * @param string $className имя класса ORM-модели
     * @return array<string>
     * @throws QueryRelationManagerException
     */
    protected function getPrimaryKey(string $className): array
    {
        if(!class_exists($className)) {
            throw new QueryRelationManagerException("class {$className} is not defined");
        }

        if(!method_exists($className, 'primaryKey')) {
            throw new QueryRelationManagerException("method {$className}::primaryKey() is not defined");
        }

        return $className::primaryKey();
    }
}
