<?php

namespace Smoren\QueryRelationManager\Yii2;

use Smoren\QueryRelationManager\Base\QueryRelationManagerException;

/**
 * Trait for simplifying building queries of QueryRelationManager from ActiveRecord model
 * @author Smoren <ofigate@gmail.com>
 * @method static string tableName()
 */
trait ActiveRecordTrait
{
    /**
     * Creates QueryRelationManager query for ActiveRecord model which uses this trait
     * @param string|null $alias table alias in select query
     * @return QueryRelationManager new instance of manager
     * @throws QueryRelationManagerException
     */
    public static function select(?string $alias = null): QueryRelationManager
    {
        return QueryRelationManager::select(self::class, $alias ?? self::tableName());
    }
}
