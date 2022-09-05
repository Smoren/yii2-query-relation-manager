<?php

namespace Smoren\QueryRelationManager\Yii2;

use Smoren\QueryRelationManager\Base\QueryRelationManagerException;
use yii\data\BaseDataProvider;
use yii\db\Connection;
use yii\db\Query;

/**
 * DataProvider class for building pager navigation for QueryRelationManager queries
 * @author Smoren <ofigate@gmail.com>
 */
class QueryRelationDataProvider extends BaseDataProvider
{
    /**
     * @var QueryRelationManager QueryRelationManager instance
     */
    public QueryRelationManager $queryRelationManager;

    /**
     * @var Connection|null the DB connection object or the application component ID of the DB connection.
     * If not set, the default DB connection will be used.
     */
    public ?Connection $db;

    /**
     * @var string|callable table column name of key or callback-function which returns it
     */
    public $key;

    /**
     * @var bool Flag to prevent total count query
     */
    public bool $withoutTotalCount = false;

    /**
     * @inheritDoc
     */
    public function init(): void
    {
        parent::init();
        $this->queryRelationManager = clone $this->queryRelationManager;
    }

    /**
     * @inheritDoc
     * @return array<mixed> the available data models
     * @throws QueryRelationManagerException
     */
    protected function prepareModels(): array
    {
        $pagination = $this->getPagination();

        if($pagination === false) {
            $models = $this->queryRelationManager->all($this->db);
        } else {
            $limit = $pagination->getLimit();
            $offset = $pagination->getOffset();

            $pagination->totalCount = $this->getTotalCount();

            $mainTable = $this->queryRelationManager->getTableCollection()->getMainTable();
            $pkFields = $mainTable->getPrimaryKeyForSelect();

            if(count($pkFields) === 1) {
                $ids = $this->queryRelationManager
                    ->prepare()
                    ->getQuery()
                    ->select($pkFields)
                    ->distinct()
                    ->limit($limit)
                    ->offset($offset)
                    ->column();

                $models = $this->queryRelationManager->filter(function(Query $q) use ($pkFields, $ids) {
                    $q->andWhere([$pkFields[0] => $ids]);
                })->all();
            } else {
                $pkValues = $this->queryRelationManager
                    ->prepare()
                    ->getQuery()
                    ->select($pkFields)
                    ->distinct()
                    ->limit($limit)
                    ->offset($offset)
                    ->all();

                $pkValuesPrefixed = [];
                foreach($pkValues as $row) {
                    $rowPrefixed = [];
                    foreach($row as $field => $value) {
                        $rowPrefixed["{$mainTable->alias}.{$field}"] = $value;
                    }
                    $pkValuesPrefixed[] = $rowPrefixed;
                }

                $models = $this->queryRelationManager->filter(
                    function(Query $q) use ($pkFields, $pkValuesPrefixed) {
                        $q->andWhere(['in', $pkFields, $pkValuesPrefixed]);
                    }
                )->all();
            }
        }

        return $models;
    }

    /**
     * @inheritDoc
     * @param array<array<string, mixed>> $models the available data models
     * @return array<scalar> the keys
     */
    protected function prepareKeys($models): array
    {
        if($this->key !== null) {
            /** @var array<scalar> $keys */
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
     * @inheritDoc
     * @throws QueryRelationManagerException
     */
    protected function prepareTotalCount(): int
    {
        if($this->withoutTotalCount) {
            return 0;
        }

        return (int)$this->queryRelationManager
            ->prepare()
            ->getQuery()
            ->select($this->queryRelationManager->getTableCollection()->getMainTable()->getPrimaryKeyForSelect())
            ->distinct()
            ->count();
    }
}
