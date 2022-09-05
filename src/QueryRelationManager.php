<?php

namespace Smoren\QueryRelationManager\Yii2;

use Smoren\QueryRelationManager\Base\QueryRelationManagerBase;
use Smoren\QueryRelationManager\Base\QueryWrapperInterface;
use Smoren\QueryRelationManager\Base\QueryRelationManagerException;
use yii\db\ActiveQuery;
use yii\db\ActiveRecord;

/**
 * QueryRelationManager implementation for ActiveRecord в Yii2
 * @author Smoren <ofigate@gmail.com>
 * @inheritDoc
 */
class QueryRelationManager extends QueryRelationManagerBase
{
    /**
     * @var QueryWrapper ActiveQuery wrapper instance
     */
    protected QueryWrapperInterface $query;

    /**
     * Connects table to query as a relation using ActiveRecord relation config
     * @param string $relationName relation name from ActiveRecord relation config
     * @param string $relationAlias alias of joined table
     * @param string|null $parentAlias alias of table to join to (by default: main table of query)
     * @param string $joinType join type ("inner", "left", "right")
     * @param string|null $extraJoinCondition extra join conditions
     * @param array<string, scalar> $extraJoinParams values of dynamic properties of extra join conditions
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
     * Returns ActiveQuery wrapper instance
     * @return QueryWrapper
     */
    public function getQuery(): QueryWrapper
    {
        return $this->query;
    }

    /**
     * @inheritDoc
     * @return QueryWrapper
     */
    public function prepare(): QueryWrapper
    {
        /** @var QueryWrapper $wrapper */
        $wrapper = parent::prepare();
        return $wrapper;
    }

    /**
     * Returns table name by it's ActiveRecord class name
     * @param string $className ActiveRecord class name
     * @return string table name
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
     * @inheritDoc
     * @return QueryWrapper
     */
    protected function createQuery(): QueryWrapper
    {
        return new QueryWrapper();
    }

    /**
     * @inheritDoc
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
     * @inheritDoc
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
