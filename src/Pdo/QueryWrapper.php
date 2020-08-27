<?php


namespace Smoren\Yii2\QueryRelationManager\Pdo;

use PDO;
use Smoren\Yii2\QueryRelationManager\Base\QueryWrapperInterface;


class QueryWrapper implements QueryWrapperInterface
{
    /**
     * @var string
     */
    protected $query;

    /**
     * @var array
     */
    protected $mapParams;

    /**
     * @var PDO
     */
    protected $pdo;

    public function __construct()
    {
        $this->query = '';
        $this->mapParams = [];

        $this->pdo = new PDO(
            'mysql:host=localhost;dbname=yii2_query_relation_manager_demo',
            'user',
            '123456789',
            [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]
        );
    }

    public function select(array $arSelect): QueryWrapperInterface
    {
        $this->query .= 'SELECT ';

        $buf = [];
        foreach($arSelect as $alias => $field) {
            $buf[] = addslashes($field).' AS '.addslashes($alias);
        }

        $this->query .= implode(', ', $buf).' ';

        return $this;
    }

    public function from(array $mapFrom): QueryWrapperInterface
    {
        $this->query .= ' FROM ';

        foreach($mapFrom as $alias => $tableName) {
            $this->query .= ' '.addslashes($tableName).' '.addslashes($alias).' ';
            break;
        }

        return $this;
    }

    public function join(string $type, array $mapTable, string $condition, array $extraJoinParams = []): QueryWrapperInterface
    {
        $this->query .= " ".addslashes($type)." JOIN ";

        foreach($mapTable as $alias => $tableName) {
            $this->query .= addslashes($tableName).' '.addslashes($alias).' ';
            break;
        }

        $this->query .= " ON {$condition} ";

        foreach($extraJoinParams as $key => $val) {
            $this->mapParams[$key] = $val;
        }

        return $this;
    }

    public function all($db = null): array
    {
        /** @var PDO $db */
        $db = $db ?? $this->pdo;

        $q = $db->prepare($this->query);

        foreach($this->mapParams as $key => $val) {
            $q->bindValue($key, $val);
        }

        $q->execute();

        return $q->fetchAll();
    }

    public function getRawSql(): string
    {
        $from = array_keys($this->mapParams);
        $to = array_values($this->mapParams);
        foreach($to as &$param) {
            $param = "'{$param}'";
        }
        unset($param);

        return str_replace($from, $to, $this->query);
    }

    public function setRawSql(string $sql): self
    {
        $this->query = $sql;

        return $this;
    }

    public function getQuery()
    {
        return $this->query;
    }
}