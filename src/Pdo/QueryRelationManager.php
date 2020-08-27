<?php


namespace Smoren\Yii2\QueryRelationManager\Pdo;


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
     */
    protected function getTableName(string $className): string
    {
        return $className;
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
     */
    protected function getTableFields(string $className): array
    {
        $qw = new QueryWrapper();
        $qw->setRawSql('SHOW COLUMNS FROM '.addslashes($className));
        $rows = $qw->all();

        $result = [];
        foreach($rows as $row) {
            $result[] = $row['Field'];
        }

        return $result;
    }
}