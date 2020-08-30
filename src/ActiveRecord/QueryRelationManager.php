<?php


namespace Smoren\Yii2\QueryRelationManager\ActiveRecord;


use Smoren\Yii2\QueryRelationManager\Base\QueryRelationManagerBase;
use Smoren\Yii2\QueryRelationManager\Base\QueryWrapperInterface;
use Smoren\Yii2\QueryRelationManager\Base\QueryRelationManagerException;
use yii\db\ActiveQuery;
use yii\db\ActiveRecord;

/**
 * Class for making queries for getting data from database with relations and filters
 * @package Smoren\Yii2\QueryRelationManager
 * @author Smoren <ofigate@gmail.com>
 */
class QueryRelationManager extends QueryRelationManagerBase
{
    public function with(
        string $relationName, string $relationAlias, ?string $parentClassName = null, string $joinType = 'left',
        ?string $extraJoinCondition = null, ?array $extraJoinParams = []
    ): self
    {
        $parentClassName = $parentClassName ?? $this->mainClassName;

        if(!class_exists($parentClassName)) {
            throw new QueryRelationManagerException("class {$parentClassName} not exists");
        }

        if(!isset($this->mapClassNameToTableAlias[$parentClassName])) {
            throw new QueryRelationManagerException("class {$parentClassName} not used in query");
        }
        $parentAlias = $this->mapClassNameToTableAlias[$parentClassName];

        /** @var ActiveRecord $inst */
        $inst = new $parentClassName;
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
            throw new QueryRelationManagerException("method {$parentClassName}::{$methodName}() returned non-ActiveQuery instance");
        }

        if($activeQuery->via) {
            throw new QueryRelationManagerException('cannot use relations with "via" section yet');
        }
        if(!$activeQuery->link || !count($activeQuery->link)) {
            throw new QueryRelationManagerException('cannot use relations without "link" section');
        }

        $fieldJoinBy = null;
        $fieldJoinTo = null;
        $extraConditions = [];
        foreach($activeQuery->link as $key => $val) {
            if($fieldJoinBy === null) {
                $fieldJoinBy = $key;
                $fieldJoinTo = $val;
            } else {
                $extraConditions[] = "{$relationAlias}.{$key} = {$parentAlias}.{$val}";
            }
        }
        if(count($extraConditions)) {
            $extraJoinCondition = implode(' AND ', $extraConditions)." {$extraJoinCondition}";
        }

        if($activeQuery->multiple) {
            return $this->withMultiple(
                $relationName, $activeQuery->modelClass, $relationAlias,
                $parentAlias, $fieldJoinBy, $fieldJoinTo, $joinType,
                $extraJoinCondition, $extraJoinParams, $activeQuery->modelClass::primaryKey()[0]
            );
        } else {
            return $this->withSingle(
                $relationName, $activeQuery->modelClass, $relationAlias,
                $parentAlias, $fieldJoinBy, $fieldJoinTo, $joinType,
                $extraJoinCondition, $extraJoinParams, $activeQuery->modelClass::primaryKey()[0]
            );
        }
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
            throw new QueryRelationManagerException("method {$className}::tableName() is not defined");
        }

        return $className::tableName();
    }

    /**
     * Создает объект запроса
     * @return QueryWrapperInterface
     */
    protected function createQuery(): QueryWrapperInterface
    {
        return new QueryWrapper();
    }

    /**
     * Возвращает список полей таблицы
     * @param string $className
     * @return array
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

        return array_keys($className::getTableSchema()->columns);
    }
}