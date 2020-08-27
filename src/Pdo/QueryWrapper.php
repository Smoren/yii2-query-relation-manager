<?php


namespace Smoren\Yii2\QueryRelationManager\Pdo;

use PDO;
use Smoren\Yii2\QueryRelationManager\Base\QueryRelationManagerException;
use Smoren\Yii2\QueryRelationManager\Base\QueryWrapperInterface;


class QueryWrapper implements QueryWrapperInterface
{
    /**
     * @var PDO
     */
    protected static $pdo;

    /**
     * @var string
     */
    protected $query;

    /**
     * @var array
     */
    protected $mapParams;

    /**
     * @param string $dsn
     * @param string $username
     * @param string $password
     * @return PDO
     */
    public static function setDbConfig(string $dsn, string $username, string $password): PDO
    {
        static::$pdo = new PDO(
            $dsn,
            $username,
            $password,
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]
        );

        return static::$pdo;
    }

    /**
     * QueryWrapper constructor.
     */
    public function __construct()
    {
        $this->query = '';
        $this->mapParams = [];
    }

    /**
     * @inheritDoc
     */
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

    /**
     * @inheritDoc
     */
    public function from(array $mapFrom): QueryWrapperInterface
    {
        $this->query .= ' FROM ';

        foreach($mapFrom as $alias => $tableName) {
            $this->query .= ' '.addslashes($tableName).' '.addslashes($alias).' ';
            break;
        }

        return $this;
    }

    /**
     * @inheritDoc
     */
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

    /**
     * @param PDO|null $db
     * @return array
     * @throws QueryRelationManagerException
     * @inheritDoc
     */
    public function all($db = null): array
    {
        /** @var PDO $db */
        $db = $db ?? static::$pdo;

        if(!$db) {
            throw new QueryRelationManagerException('no pdo connection opened');
        }

        $q = $db->prepare($this->query);

        foreach($this->mapParams as $key => $val) {
            $q->bindValue($key, $val);
        }

        $q->execute();

        return $q->fetchAll();
    }

    /**
     * @inheritDoc
     */
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

    /**
     * @param string $sql
     * @return $this
     */
    public function setRawSql(string $sql): self
    {
        $this->query = $sql;

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function getQuery()
    {
        return $this->query;
    }
}