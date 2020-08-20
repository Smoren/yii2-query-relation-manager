<?php


namespace Smoren\Yii2\QueryRelationManager;


use yii\db\Connection;
use yii\db\Query;

/**
 * Class for making queries for getting data from database with relations and filters
 * @package Smoren\Yii2\QueryRelationManager
 * @author Smoren <ofigate@gmail.com>
 */
class QueryRelationManager
{
    /**
     * @var Query хранит объект билдера запроса
     */
    protected $query;

    /**
     * @var string псевдоним таблицы, данные из которой хотим получить
     */
    protected $mainTableAlias;

    /**
     * @var string имя таблицы, данные из которой хотим получить
     */
    protected $mainTableName;

    /**
     * @var callable[] список анонимных функций, которые будут модифицировать запрос
     */
    protected $filters = [];

    /**
     * @var callable[] карта модификаторов результата (псевдоним таблицы => функция)
     */
    protected $modifierMap = [];

    /**
     * @var string[] карта имен подключаемых таблиц по их псевдонимам
     */
    protected $mapJoinAsToTableName = [];

    /**
     * @var string[] карта первичных ключей подключаемых таблиц по их псевдонимам
     */
    protected $mapJoinAsToPrimaryFieldName = [];

    /**
     * @var string[] карта имен полей (на которые ссылается join-связь) по пседонимам подключаемых таблиц
     */
    protected $mapJoinAsToFieldJoinTo = [];

    /**
     * @var string[] карта имен полей (по которым ссылается join-связь) по пседонимам подключаемых таблиц
     */
    protected $mapJoinAsToFieldJoinBy = [];

    /**
     * @var array карта набора условий подключения таблиц в запросе по псевдонимам подключаемых таблиц
     */
    protected $relationConditions = [];

    /**
     * @var string[] карта псевдонимов таблиц по псевдонимам подключаемых к ним таблиц по принципу "один к одному"
     */
    protected $relationMapSingle = [];

    /**
     * @var string[] карта псевдонимов таблиц по псевдонимам подключаемых к ним таблиц по принципу "один ко многим"
     */
    protected $relationMapMultiple = [];

    /**
     * @var string[][] карта массивов имен полей-контейнеров по псевдонимам таблиц, в данные которых эти контейнеры должны быть встроены
     * (псевдоним таблицы => [имя поля, имя поля, ...])
     */
    protected $relationSingleFieldMap = [];

    /**
     * @var string[][] карта массивов имен полей-контейнеров по псевдонимам таблиц, в данные которых эти контейнеры должны быть встроены
     * (псевдоним таблицы => [имя поля, имя поля, ...])
     */
    protected $relationMultipleFieldMap = [];

    /**
     * @var string[][] матрица имен полей в запросе (псевдоним таблицы => имя поля => имя поля с префиксом псевдонима таблицы)
     */
    protected $fieldMatrix = [];

    /**
     * @var string[][] обратная матрица имен полей в запросе (псевдоним таблицы => имя поля с префиксом => имя поля)
     */
    protected $fieldMatrixInverse = [];

    /**
     * @var string[] карта имен полей таблиц по именам полей с префиксом соответствующих им таблиц
     */
    protected $fieldMap = [];

    /**
     * @var string[] карта имени поля, содержащего экземпляр(ы) данных из подключаемой таблицы в родительском массиве
     * по псевдониму этой таблицы
     */
    protected $mapJoinAsToContainerFieldAlias = [];

    /**
     * Начинает формирование данных запроса
     * @param string $className имя класса ActiveRecord, сущности которого нужно получить
     * @param string $tableAlias псевдоним таблицы в БД для записи отношений
     * @param string $fieldJoinTo имя поля, на которое будут ссылаться подключаемые сущности
     * @param string $primaryFieldName имя поля первичного ключа таблицы
     * @return static новый объект relation-мененджера
     * @throws QueryRelationManagerException
     */
    public static function select(string $className, string $tableAlias, string $fieldJoinTo = 'id', string $primaryFieldName = 'id'): self
    {
        return new static($className, $tableAlias, $fieldJoinTo, $primaryFieldName);
    }

    /**
     * Добавляет к запросу связь "один к одному" с другой сущностью ActiveRecord
     * @param string $containerFieldAlias название поля, куда будет записана сущность в результате
     * @param string $className имя класса ActiveRecord, сущности которого нужно подключить
     * @param string $joinAs псевдоним для таблицы, связанной с классом
     * @param string $joinTo псевдоним таблицы, к которой будут подключаться сущности класса
     * @param string $fieldJoinBy поле, по которому ссылается join-связь
     * @param string $fieldJoinTo поле, на которое ссылается join-связь
     * @param string $joinType тип присоединения таблицы (inner, left, right, outer)
     * @param string|null $extraJoinCondition дополнительные условия join-связи
     * @param array $extraJoinParams параметры дополнительных условий join-связи
     * @param string $primaryFieldName поле первичного ключа в таблице, по умолчанию — id
     * @return $this
     * @throws QueryRelationManagerException
     */
    public function withSingle(
        string $containerFieldAlias, string $className, string $joinAs, string $joinTo,
        string $fieldJoinBy, string $fieldJoinTo, string $joinType = 'left',
        ?string $extraJoinCondition = null, array $extraJoinParams = [], string $primaryFieldName = 'id'
    ): self
    {
        if(!isset($this->relationSingleFieldMap[$joinTo])) {
            $this->relationSingleFieldMap[$joinTo] = [];
        }
        $this->relationSingleFieldMap[$joinTo][] = $containerFieldAlias;

        $this->addAliases($className, $joinAs, $fieldJoinTo, $primaryFieldName, $fieldJoinBy, $containerFieldAlias);
        $this->addRelationConditions(
            $joinAs, $joinTo, $fieldJoinBy, $fieldJoinTo, $joinType, $extraJoinCondition, $extraJoinParams
        );

        $this->relationMapSingle[$joinAs] = $joinTo;

        return $this;
    }

    /**
     * Добавляет к запросу связь "один ко многим" с другими сущностями ActiveRecord
     * @param string $containerFieldAlias название поля, куда будет записана сущность в результате
     * @param string $className имя класса ActiveRecord, сущности которого нужно подключить
     * @param string $joinAs псевдоним для таблицы, связанной с классом
     * @param string $joinTo псевдоним таблицы, к которой будут подключаться сущности класса
     * @param string $fieldJoinBy поле, по которому ссылается join-связь
     * @param string $fieldJoinTo поле, на которое ссылается join-связь
     * @param string $joinType тип присоединения таблицы (inner, left, right, outer)
     * @param string|null $extraJoinCondition дополнительные условия join-связи
     * @param array $extraJoinParams параметры дополнительных условий join-связи
     * @param string $primaryFieldName поле первичного ключа в таблице, по умолчанию — id
     * @return $this
     * @throws QueryRelationManagerException
     */
    public function withMultiple(
        string $containerFieldAlias, string $className, string $joinAs, string $joinTo,
        string $fieldJoinBy, string $fieldJoinTo, string $joinType = 'left',
        ?string $extraJoinCondition = null, array $extraJoinParams = [], string $primaryFieldName = 'id'
    ): self
    {
        if(!isset($this->relationMultipleFieldMap[$joinTo])) {
            $this->relationMultipleFieldMap[$joinTo] = [];
        }
        $this->relationMultipleFieldMap[$joinTo][] = $containerFieldAlias;

        $this->addAliases($className, $joinAs, $fieldJoinTo, $primaryFieldName, $fieldJoinBy, $containerFieldAlias);
        $this->addRelationConditions(
            $joinAs, $joinTo, $fieldJoinBy, $fieldJoinTo, $joinType, $extraJoinCondition, $extraJoinParams
        );

        $this->relationMapMultiple[$joinAs] = $joinTo;

        return $this;
    }

    /**
     * Добавляет функцию-модификатор запроса
     * @param callable $filter
     * @return $this
     */
    public function filter(callable $filter): self
    {
        $this->filters[] = $filter;

        return $this;
    }

    /**
     * Устанавливает для таблицы функцию-модификатор сущности результата
     * @param string $tableAlias псевдоним таблицы
     * @param callable $modifier функция-модификатор результата
     * @return $this
     */
    public function modify(string $tableAlias, callable $modifier): self
    {
        $this->modifierMap[$tableAlias] = $modifier;

        return $this;
    }

    /**
     * Выполняет запрос к базе, собирает и возвращает результат
     * @param Connection|null $db подключение к БД
     * @return array массив сущностей главной таблицы с отношениями подключенных таблиц
     * @throws QueryRelationManagerException
     */
    public function all(?Connection $db = null): array
    {
        $this->prepare();

        $rows = $this->query->all($db);

        $maps = [];

        foreach($this->fieldMatrixInverse as $alias => $fieldNameMap) {
            $maps[$alias] = $this->getMapFromPrefixedResult(
                $rows, $fieldNameMap, $this->mapJoinAsToPrimaryFieldName[$alias]
            );
        }

        $mapJoinAsToContainerFieldAliasReverse = array_reverse($this->mapJoinAsToContainerFieldAlias);
        $mapJoinAsToContainerFieldAliasReverse[$this->mainTableAlias] = null;

        $count = count($mapJoinAsToContainerFieldAliasReverse);

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

            if(isset($this->relationMultipleFieldMap[$joinTo]) || isset($this->relationSingleFieldMap[$joinTo])) {
                foreach($itemsTo as &$joinToRow) {
                    if(isset($this->relationMultipleFieldMap[$joinTo])) {
                        foreach($this->relationMultipleFieldMap[$joinTo] as $fieldNameMultiple) {
                            if(!isset($joinToRow[$fieldNameMultiple])) {
                                $joinToRow[$fieldNameMultiple] = [];
                            }
                        }
                    }

                    if(isset($this->relationSingleFieldMap[$joinTo])) {
                        foreach($this->relationSingleFieldMap[$joinTo] as $fieldNameSingle) {
                            if(!isset($joinToRow[$fieldNameSingle])) {
                                $joinToRow[$fieldNameSingle] = null;
                            }
                        }
                    }
                }
                unset($joinToRow);
            }

            foreach($itemsFrom as $id => $itemFrom) {
                if(!isset($itemFrom[$joinAsFieldName])) {
                    throw new QueryRelationManagerException("no field {$joinAsFieldName} found in items of {$joinAs}");
                }

                //if(!isset($itemsTo[$itemFrom[$joinAsFieldName]])) {
                //    throw new QueryRelationManagerException(
                //        "no item with {$joinAsFieldName} = {$itemFrom[$joinAsFieldName]} ".
                //        "found in items of {$joinTo}"
                //    );
                //}

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

        if(isset($this->modifierMap[$this->mainTableAlias])) {
            foreach($maps[$this->mainTableAlias] as &$item) {
                ($this->modifierMap[$this->mainTableAlias])($item);
            }
        }

        return array_values($maps[$this->mainTableAlias]);
    }

    /**
     * Возвращает текст SQL-запроса
     * @return string текст SQL-запроса
     */
    public function getRawSql(): string
    {
        $this->prepare();

        return $this->query->createCommand()->getRawSql();
    }

    /**
     * QueryRelationManager constructor.
     * @param string $className имя класса сущности ActiveRecord
     * @param string $alias псевдоним таблицы сущности
     * @param string $fieldJoinTo имя поля, на которое будут ссылаться подключаемые сущности
     * @param string $primaryFieldName имя поля первичного ключа таблицы
     * @throws QueryRelationManagerException
     */
    protected function __construct(string $className, string $alias, string $fieldJoinTo, string $primaryFieldName = 'id')
    {
        $this->mainTableAlias = $alias;
        $this->mainTableName = $this->getTableName($className);
        $this->addAliases($className, $alias, $fieldJoinTo, $primaryFieldName);
    }

    /**
     * Создает и выстраивает SQL-запрос
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
     * Добавляет в карты данные о задействованной в запросе сущности
     * @param string $className имя класса сущности ActiveRecord
     * @param string $joinAs псевдоним таблицы
     * @param string $fieldJoinTo поле, на которое ссылается join-связь
     * @param string $primaryFieldName поле первичного ключа таблицы
     * @param string|null $fieldJoinBy поле, по которому ссылается join-связь
     * @param string|null $containerFieldAlias имя поля-контейнера для сущностей в родительском экземпляре
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
     * Добавляет в карту набор условий подключения таблиц в запросе
     * @param string $joinAs псевдоним подключаемой таблицы
     * @param string $joinTo псевдоним таблицы, к которой будет осуществлено подключение
     * @param string $fieldJoinBy поле, по которому ссылается join-связь
     * @param string $fieldJoinTo поле, на которое ссылается join-связь
     * @param string $joinType тип присоединения таблицы (inner, left, right, outer)
     * @param string $extraJoinCondition дополнительные условия join-связи
     * @param array $extraJoinParams параметры дополнительных условий join-связи
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
     * Добавляет данные о полях таблицы в карту и матрицы
     * @param string $className имя класса сущности ActiveRecord
     * @param string $joinAs псевдоним таблицы
     * @return $this
     * @throws QueryRelationManagerException
     */
    protected function addFields(string $className, string $joinAs): self
    {
        if(!class_exists($className)) {
            throw new QueryRelationManagerException("class {$className} is not defined");
        }


        if(!method_exists($className, 'getTableSchema')) {
            throw new QueryRelationManagerException("method {$className}::getTableSchema() is not defined");
        }

        $fields = array_keys($className::getTableSchema()->columns);

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
     * Выстраивает из результата запроса карту сущностей по значениям ссылающихся полей с избавлением от префиксов полей
     * @param array $result результат выполнения запроса к БД
     * @param array $fieldNameMap карта полей сущности (имя поля с префиксом => имя поля)
     * @param string $relatedFieldName имя поля, по которому предусмотрена связь
     * @return array карта сущностей по значениям ссылающихся полей
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

            if(!array_key_exists($relatedFieldName, $item)) {
                throw new QueryRelationManagerException("no field {$relatedFieldName} in result row for mapping");
            }

            if($item[$relatedFieldName] === null) {
                continue;
            }

            $map[$item[$relatedFieldName]] = $item;
        }

        return $map;
    }
}