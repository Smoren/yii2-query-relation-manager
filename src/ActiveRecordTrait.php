<?php

namespace Smoren\QueryRelationManager\Yii2;

use Smoren\QueryRelationManager\Base\QueryRelationManagerException;

/**
 * Trait для упрощения построения запросов с помощью QueryRelationManager
 * @author Smoren <ofigate@gmail.com>
 * @method static string tableName()
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
        return QueryRelationManager::select(self::class, $alias ?? self::tableName());
    }
}
