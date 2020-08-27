<?php


namespace Smoren\Yii2\QueryRelationManager\ActiveRecord;


use yii\data\BaseDataProvider;
use yii\db\Connection;
use yii\db\Query;

class QueryRelationDataProvider extends BaseDataProvider
{
    /**
     * @var QueryRelationManager
     */
    public $queryRelationManager;

    /**
     * @var Connection|array|string the DB connection object or the application component ID of the DB connection.
     * If not set, the default DB connection will be used.
     * Starting from version 2.0.2, this can also be a configuration array for creating the object.
     */
    public $db;

    /**
     * @var string|callable имя столбца с ключом или callback-функция, возвращающие его
     */
    public $key;

    /**
     * Prepares the data models that will be made available in the current page.
     * @return array the available data models
     * @throws QueryRelationManagerException
     */
    protected function prepareModels()
    {
        $pagination = $this->getPagination();

        if($pagination === false) {
            $models = $this->queryRelationManager->all($this->db);
        } else {
            $limit = $pagination->getLimit();
            $offset = $pagination->getOffset();

            $pagination->totalCount = $this->getTotalCount();

            $pkField = $this->queryRelationManager->getMainTablePkField();

            $ids = $this->queryRelationManager
                ->prepare()
                ->getQuery()
                ->select($pkField)
                ->distinct()
                ->limit($limit)
                ->offset($offset)
                ->column();

            $models = $this->queryRelationManager->filter(function(Query $q) use ($pkField, $ids) {
                $q->andWhere([$pkField => $ids]);
            })->all();
        }

        return $models;
    }

    /**
     * Prepares the keys associated with the currently available data models.
     * @param array $models the available data models
     * @return array the keys
     */
    protected function prepareKeys($models)
    {
        if($this->key !== null) {
            $keys = [];

            foreach($models as $model) {
                if(is_string($this->key)) {
                    $keys[] = $model[$this->key];
                } else {
                    $keys[] = call_user_func($this->key, $model);
                }
            }

            return $keys;
        } else {
            return array_keys($models);
        }
    }

    /**
     * Returns a value indicating the total number of data models in this data provider.
     * @return int total number of data models in this data provider.
     */
    protected function prepareTotalCount()
    {
        // TODO слабое место: сильно снижает производительность!!! Возможно необходимо сделать рассчет опциональным
        return $this->queryRelationManager
            ->prepare()
            ->getQuery()
            ->select($this->queryRelationManager->getMainTablePkField())
            ->distinct()
            ->count();
    }
}