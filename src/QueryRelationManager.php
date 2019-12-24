<?php


namespace Smoren\Yii2\QueryRelationManager;


use yii\db\Query;

class QueryRelationManager
{
    /**
     * @var Query
     */
    protected $query;

    protected $mainTableAlias;
    protected $mainTableName;

    /**
     * @var callable[]
     */
    protected $filters = [];

    /**
     * @var callable[]
     */
    protected $modifierMap = [];

    protected $mapJoinAsToTableName = [];

    protected $mapJoinAsToPrimaryFieldName = [];
    protected $mapJoinAsToFieldJoinTo = [];
    protected $mapJoinAsToFieldJoinBy = [];
    protected $relationConditions = [];

    protected $relationMapSingle = [];
    protected $relationMapMultiple = [];

    protected $fieldMatrix = [];
    protected $fieldMatrixInverse = [];
    protected $fieldMap = [];
    protected $mapJoinAsToContainerFieldAlias = [];

    /**
     * Начинает формирование запроса
     * @param string $className имя класса ActiveRecord, сущности которого нужно получить
     * @param string $tableAlias короткий псевдоним таблицы в БД для записи отношений
     * @param string $fieldJoinTo
     * @param string $primaryFieldName
     * @return static
     * @throws QueryRelationManagerException
     */
    public static function select(string $className, string $tableAlias, string $fieldJoinTo = 'id', string $primaryFieldName = 'id'): self
    {
        return new static($className, $tableAlias, $fieldJoinTo, $primaryFieldName);
    }

    /**
     * Добавляет к запросу связь один к одному с другой сущностью ActiveRecord
     * @param string $containerFieldAlias название поля, куда будет записана сущность в результате
     * @param string $className имя класса ActiveRecord, сущности которого нужно подключить
     * @param string $joinAs
     * @param string $joinTo
     * @param string $fieldJoinBy
     * @param string $fieldJoinTo
     * @param string $joinType тип присоединения таблицы (inner, left, right, outer)
     * @param string|null $extraJoinCondition
     * @param array $extraJoinParams
     * @param string $primaryFieldName
     * @return $this
     * @throws QueryRelationManagerException
     */
    public function withSingle(
        string $containerFieldAlias, string $className, string $joinAs, string $joinTo,
        string $fieldJoinBy, string $fieldJoinTo, string $joinType = 'left',
        ?string $extraJoinCondition = null, array $extraJoinParams = [], string $primaryFieldName = 'id'
    ): self
    {
        $this->addAliases($className, $joinAs, $fieldJoinTo, $primaryFieldName, $fieldJoinBy, $containerFieldAlias);
        $this->addRelationConditions(
            $joinAs, $joinTo, $fieldJoinBy, $fieldJoinTo, $joinType, $extraJoinCondition, $extraJoinParams
        );

        $this->relationMapSingle[$joinAs] = $joinTo;

        return $this;
    }

    /**
     * Добавляет к запросу связь один ко многим с другими сущностями ActiveRecord
     * @param string $containerFieldAlias название поля, куда будут записаны сущности в результате
     * @param string $className имя класса ActiveRecord, сущности которого нужно подключить
     * @param string $joinAs короткий псевдоним таблицы в БД для записи отношений
     * @param string $joinTo короткий псевдоним таблицы в БД, к которой подключается данный тип сущностей
     * @param string $fieldJoinBy
     * @param string $fieldJoinTo
     * @param string $joinType тип присоединения таблицы (inner, left, right, outer)
     * @param string|null $extraJoinCondition
     * @param array $extraJoinParams параметры для условия присоединения таблицы
     * @param string $primaryFieldName
     * @return $this
     * @throws QueryRelationManagerException
     */
    public function withMultiple(
        string $containerFieldAlias, string $className, string $joinAs, string $joinTo,
        string $fieldJoinBy, string $fieldJoinTo, string $joinType = 'left',
        ?string $extraJoinCondition = null, array $extraJoinParams = [], string $primaryFieldName = 'id'
    ): self
    {
        $this->addAliases($className, $joinAs, $fieldJoinTo, $primaryFieldName, $fieldJoinBy, $containerFieldAlias);
        $this->addRelationConditions(
            $joinAs, $joinTo, $fieldJoinBy, $fieldJoinTo, $joinType, $extraJoinCondition, $extraJoinParams
        );

        $this->relationMapMultiple[$joinAs] = $joinTo;

        return $this;
    }

    /**
     * @param callable $filter
     * @return $this
     */
    public function filter(callable $filter): self
    {
        $this->filters[] = $filter;

        return $this;
    }

    public function modify(string $tableAlias, callable $modifier): self
    {
        $this->modifierMap[$tableAlias] = $modifier;

        return $this;
    }

    /**
     * @return array
     * @throws QueryRelationManagerException
     */
    public function all(): array
    {
        $this->prepare();

        $rows = $this->query->all();

        $maps = [];

        foreach($this->fieldMatrixInverse as $alias => $fieldNameMap) {
            $maps[$alias] = $this->getMapFromPrefixedResult(
                $rows, $fieldNameMap, $this->mapJoinAsToPrimaryFieldName[$alias]
            );
        }

        $mapJoinAsToContainerFieldAliasReverse = array_reverse($this->mapJoinAsToContainerFieldAlias);
        $mapJoinAsToContainerFieldAliasReverse[$this->mainTableAlias] = null;

        $count = count($mapJoinAsToContainerFieldAliasReverse);

        if(!$count < 2) {
            for($i=0; $i<$count-1; $i++) {
                $joinAs = key($mapJoinAsToContainerFieldAliasReverse);
                $containerFieldName = current($mapJoinAsToContainerFieldAliasReverse);

                if(isset($this->relationMapSingle[$joinAs])) {
                    // сценарий single
                    $isMultiple = false;
                    $joinTo = $this->relationMapSingle[$joinAs];
                } elseif(isset($this->relationMapMultiple[$joinAs])) {
                    // сценарий multiple
                    $isMultiple = true;
                    $joinTo = $this->relationMapMultiple[$joinAs];
                } else {
                    throw new QueryRelationManagerException("something went wrong and we don't care...");
                }

                $joinAsFieldName = $this->mapJoinAsToFieldJoinBy[$joinAs];

                $itemsFrom = &$maps[$joinAs];
                $itemsTo = &$maps[$joinTo];

                foreach($itemsFrom as $id => $itemFrom) {
                    if(!isset($itemFrom[$joinAsFieldName])) {
                        throw new QueryRelationManagerException("no field {$joinAsFieldName} found in items of {$joinAs}");
                    }

                    if(!isset($itemsTo[$itemFrom[$joinAsFieldName]])) {
                        throw new QueryRelationManagerException(
                            "no item with {$joinAsFieldName} = {$itemFrom[$joinAsFieldName]} ".
                            "found in items of {$joinTo}"
                        );
                    }

                    if(!$isMultiple) {
                        $joinToFieldName = $this->mapJoinAsToFieldJoinTo[$joinAs];
                        foreach($itemsTo as &$itemTo) {
                            if($itemTo[$joinToFieldName] == $itemFrom[$joinAsFieldName]) {
                                if(isset($itemTo[$containerFieldName])) {
                                    throw new QueryRelationManagerException(
                                        "trying to rewrite single relation to field {$containerFieldName} of {$joinTo}"
                                    );
                                }

                                if(isset($this->modifierMap[$joinAs])) {
                                    ($this->modifierMap[$joinAs])($itemFrom, $itemTo);
                                }

                                $itemTo[$containerFieldName] = $itemFrom;
                            }
                        }
                        unset($itemTo);
                    } else {
                        $itemTo = &$itemsTo[$itemFrom[$joinAsFieldName]];

                        if(!isset($itemTo[$containerFieldName])) {
                            $itemTo[$containerFieldName] = [];
                        }

                        if(isset($this->modifierMap[$joinAs])) {
                            ($this->modifierMap[$joinAs])($itemFrom, $itemTo);
                        }

                        $itemTo[$containerFieldName][] = $itemFrom;
                    }
                }

                next($mapJoinAsToContainerFieldAliasReverse);
            }
        }

        if(isset($this->modifierMap[$this->mainTableAlias])) {
            foreach($maps[$this->mainTableAlias] as &$item) {
                ($this->modifierMap[$this->mainTableAlias])($item);
            }
        }

        return array_values($maps[$this->mainTableAlias]);
    }

    /**
     * @return string
     */
    public function getRawSql(): string
    {
        $this->prepare();

        return $this->query->createCommand()->getRawSql();
    }

    /**
     * QueryRelationManager constructor.
     * @param string $className
     * @param string $alias
     * @param string $fieldJoinTo
     * @param string $primaryFieldName
     * @throws QueryRelationManagerException
     */
    protected function __construct(string $className, string $alias, string $fieldJoinTo, string $primaryFieldName = 'id')
    {
        $this->mainTableAlias = $alias;
        $this->mainTableName = $this->getTableName($className);
        $this->addAliases($className, $alias, $fieldJoinTo, $primaryFieldName);
    }

    /**
     * @return $this
     */
    protected function prepare(): self
    {
        $this->query = new Query();

        $arSelect = [];
        foreach($this->fieldMatrix as $joinAs => $fieldsMap) {
            foreach($fieldsMap as $fieldName => $fieldNamePrefixed) {
                $arSelect[] = "{$joinAs}.{$fieldName} as {$fieldNamePrefixed}";
            }
        }

        $this->query
            ->select($arSelect)
            ->from([$this->mainTableAlias => $this->mainTableName]);

        foreach($this->relationConditions as $joinAs => [$joinTo, $fieldJoinBy, $fieldJoinTo, $joinType, $extraJoinCondition, $extraJoinParams]) {
            $tableName = $this->mapJoinAsToTableName[$joinAs];

            $condition = "{$joinAs}.{$fieldJoinBy} = {$joinTo}.{$fieldJoinTo}";

            if($extraJoinCondition !== null) {
                $condition .= " {$extraJoinCondition}";
            }

            $this->query->join("{$joinType} join", [$joinAs => $tableName], $condition, $extraJoinParams);
        }

        foreach($this->filters as $modifier) {
            $modifier($this->query);
        }

        return $this;
    }

    /**
     * @param string $className
     * @param string $joinAs
     * @param string $fieldJoinTo
     * @param string $primaryFieldName
     * @param string|null $fieldJoinBy
     * @param string|null $containerFieldAlias
     * @return $this
     * @throws QueryRelationManagerException
     */
    protected function addAliases(
        string $className, string $joinAs, string $fieldJoinTo, string $primaryFieldName,
        ?string $fieldJoinBy = null, ?string $containerFieldAlias = null
    ): self
    {
        $tableName = $this->getTableName($className);

        if(isset($this->mapJoinAsToTableName[$joinAs])) {
            throw new QueryRelationManagerException("alias {$joinAs} is already used");
        }

        $this->mapJoinAsToTableName[$joinAs] = $tableName;

        $this->mapJoinAsToPrimaryFieldName[$joinAs] = $primaryFieldName;
        $this->mapJoinAsToFieldJoinTo[$joinAs] = $fieldJoinTo;
        if($fieldJoinBy !== null) {
            $this->mapJoinAsToFieldJoinBy[$joinAs] = $fieldJoinBy;
        }

        if($containerFieldAlias !== null) {
            $this->mapJoinAsToContainerFieldAlias[$joinAs] = $containerFieldAlias;
        }

        $this->addFields($className, $joinAs);

        return $this;
    }

    /**
     * @param string $joinAs
     * @param string $joinTo
     * @param string $fieldJoinBy
     * @param string $fieldJoinTo
     * @param string $joinType
     * @param string $extraJoinCondition
     * @param array $extraJoinParams
     * @return $this
     */
    protected function addRelationConditions(
        string $joinAs, string $joinTo, string $fieldJoinBy, string $fieldJoinTo,
        string $joinType, ?string $extraJoinCondition = null, array $extraJoinParams = []
    ): self
    {
        $this->relationConditions[$joinAs] = [
            $joinTo, $fieldJoinBy, $fieldJoinTo, $joinType, $extraJoinCondition, $extraJoinParams
        ];

        return $this;
    }

    /**
     * @param string $className
     * @param string $joinAs
     * @return $this
     * @throws QueryRelationManagerException
     */
    protected function addFields(string $className, string $joinAs): self
    {
        if(!class_exists($className)) {
            throw new QueryRelationManagerException("class {$className} is not defined");
        }

        $obj = (new $className());

        if(!method_exists($obj, 'getAttributes')) {
            throw new QueryRelationManagerException("method {$className}::getAttributes() is not defined");
        }

        $fields = array_keys($obj->getAttributes());

        $this->fieldMatrix[$joinAs] = [];

        foreach($fields as $fieldName) {
            $fieldNameAliased = "{$joinAs}_{$fieldName}";
            if(isset($this->fieldMap[$fieldNameAliased])) {
                throw new QueryRelationManagerException("aliased field name {$fieldNameAliased} already used");
            }

            $this->fieldMatrix[$joinAs][$fieldName] = $fieldNameAliased;
            $this->fieldMatrixInverse[$joinAs][$fieldNameAliased] = $fieldName;
            $this->fieldMap[$fieldNameAliased] = $fieldName;
        }

        return $this;
    }

    /**
     * @param string $className
     * @return string
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
     * @param array $result
     * @param array $fieldNameMap
     * @param string $relatedFieldName
     * @return array
     * @throws QueryRelationManagerException
     */
    protected function getMapFromPrefixedResult(array $result, array $fieldNameMap, string $relatedFieldName = 'id'): array
    {
        $map = [];

        foreach($result as $row) {
            $item = [];

            foreach($fieldNameMap as $fieldNamePrefixed => $fieldName) {
                if(!array_key_exists($fieldNamePrefixed, $row)) {
                    throw new QueryRelationManagerException("no field {$fieldNamePrefixed} in result row");
                }

                $item[$fieldName] = $row[$fieldNamePrefixed];
            }

            if(!isset($item[$relatedFieldName])) {
                throw new QueryRelationManagerException("no field {$relatedFieldName} in result row for mapping");
            }

            $map[$item[$relatedFieldName]] = $item;
        }

        return $map;
    }
}