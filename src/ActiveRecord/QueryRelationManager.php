<?php


namespace Smoren\Yii2\QueryRelationManager\ActiveRecord;


use Smoren\Yii2\QueryRelationManager\Base\QueryRelationManagerBase;
use Smoren\Yii2\QueryRelationManager\Base\QueryWrapperInterface;
use Smoren\Yii2\QueryRelationManager\Base\QueryRelationManagerException;

/**
 * Class for making queries for getting data from database with relations and filters
 * @package Smoren\Yii2\QueryRelationManager
 * @author Smoren <ofigate@gmail.com>
 */
class QueryRelationManager extends QueryRelationManagerBase
{

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
}