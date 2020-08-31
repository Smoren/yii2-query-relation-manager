<?php


namespace Smoren\Yii2\QueryRelationManager\ActiveRecord;


use Smoren\Yii2\QueryRelationManager\Base\QueryRelationManagerException;

/**
 * Trait для упрощения построения запросов с помощью QueryRelationManager
 * @package Smoren\Yii2\QueryRelationManager\ActiveRecord
 * @author Smoren <ofigate@gmail.com>
 */
trait ActiveRecordTrait
{
    /**
     * Создает запрос QueryRelationManager к таблице модели ActiveRecord, к которой применен трейт
     * @param string|null $alias псевдоним таблицы в запросе
     * @return QueryRelationManager
     * @throws QueryRelationManagerException
     */
    public static function select(?string $alias = null): QueryRelationManager
    {
        return QueryRelationManager::select(
            self::class, $alias ?? self::tableName()
        );
    }
}