<?php

namespace Smoren\QueryRelationManager\Yii2;

use Smoren\QueryRelationManager\Base\QueryRelationManagerBase;
use Smoren\QueryRelationManager\Base\QueryWrapperInterface;
use Smoren\QueryRelationManager\Base\QueryRelationManagerException;
use yii\base\InvalidConfigException;
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
     * @inheritDoc
     */
    public static function select(string $className, string $tableAlias): QueryRelationManagerBase
    {
        static::checkClassIsActiveRecord($className);
        return parent::select($className, $tableAlias);
    }

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
        static::checkClassIsActiveRecord($parentClassName);

        /** @var ActiveRecord $inst */
        $inst = new $parentClassName();
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
     * @param string $className
     * @return void
     * @throws QueryRelationManagerException
     */
    protected static function checkClassIsActiveRecord(string $className)
    {
        if(!class_exists($className)) {
            throw new QueryRelationManagerException(
                "class '{$className}' does not exist"
            );
        }

        if(!(new $className() instanceof ActiveRecord)) {
            throw new QueryRelationManagerException(
                "class {$className} is not an instance of ActiveRecord"
            );
        }
    }

    /**
     * Returns table name by it's ActiveRecord class name
     * @param string $className ActiveRecord class name
     * @return string table name
     * @throws QueryRelationManagerException
     */
    protected function getTableName(string $className): string
    {
        static::checkClassIsActiveRecord($className);
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
     * @throws QueryRelationManagerException|InvalidConfigException
     */
    protected function getTableFields(string $className): array
    {
        static::checkClassIsActiveRecord($className);
        /**
         * @var ActiveRecord $className
         * @var array<string, mixed> $columns
         */
        $columns = $className::getTableSchema()->columns;
        return array_keys($columns);
    }

    /**
     * @inheritDoc
     * @throws QueryRelationManagerException
     */
    protected function getPrimaryKey(string $className): array
    {
        static::checkClassIsActiveRecord($className);
        /** @var ActiveRecord $className */
        return $className::primaryKey();
    }
}
