<?php
namespace Gram\Domain\Repository\Dbal;

use Doctrine\DBAL\Query\QueryBuilder;
use Doctrine\DBAL\Connection;
use Gram\Domain\Entity\EntityInterface;
use Gram\Domain\Entity\LifeCycleInterface;
use Gram\Domain\Entity\ValidatorInterface;
use Gram\Domain\Repository\RepositoryBase;
use Gram\Utility\Helper\ArrayHelper;
use ZuoYeah\Entity\PageResult;
use ZuoYeah\Entity\Search\SearchBase;

abstract class DbalRepository extends RepositoryBase
{
    /**
     * @var \Doctrine\DBAL\Connection
     */
    private $conn;

    /**
     * @param \Doctrine\DBAL\Connection|null $conn
     */
    function __construct($conn = null)
    {
        if (is_null($conn)) {
            $this->conn = ConnectionFactory::getConnection();
        } else {
            $this->conn = $conn;
        }
    }

    /**
     * 数据库表名
     *
     * @return string
     */
    abstract protected function tableName();

    /**
     * 对象主键
     *
     * @return string
     */
    protected function primaryKey()
    {
        return 'id';
    }

    /**
     * 获取数据库连接对象
     *
     * @return Connection|null
     */
    protected function conn()
    {
        return $this->conn;
    }

    protected function getPdoType($value)
    {
        switch (gettype($value)) {
            case 'boolean':
                return \PDO::PARAM_BOOL;
            case 'integer':
                return \PDO::PARAM_INT;
            case 'array':
            case 'double':
            case 'float':
            case 'string':
            default:
                return \PDO::PARAM_STR;
        }
    }

    /**
     * 创建对象
     *
     * @param EntityInterface $entity
     *
     * @return bool
     */
    protected function createInner(EntityInterface $entity)
    {
        $conn = $this->conn();
        $query = $conn->createQueryBuilder()->insert($this->tableName());
        $row = $this->disassemble($entity);
        $index = 0;
        foreach ($row as $key => $value) {
            if ($key === $this->primaryKey() && !$value) {
                continue;
            }
            $query->setValue($key, '?');
            $query->setParameter($index++, $value, $this->getPdoType($value));
        }
        if ($query->execute()) {
            $entity->{$this->primaryKey()} = (int)$conn->lastInsertId();

            return true;
        }

        return false;
    }

    /**
     * 更新对象
     *
     * @param EntityInterface $entity
     *
     * @return bool
     */
    protected function updateInner(EntityInterface $entity, $updateKeys = [])
    {
        $query = $this->conn()->createQueryBuilder()->update($this->tableName());
        $row = $this->disassemble($entity);

        $index = 0;
        foreach ($row as $key => $value) {
            if ($key === $this->primaryKey()) {
                continue;
            }

            if (!empty($updateKeys) && !in_array($key, $updateKeys)) {
                continue;
            }

            $query->set($key, '?');
            $query->setParameter($index++, $value, $this->getPdoType($value));
        }

        $query->where("{$this->primaryKey()} = ?")
            ->setParameter($index, $entity->{$this->primaryKey()})
            ->execute();

        return true;
    }

    /**
     * 删除对象
     *
     * @param EntityInterface $entity
     *
     * @return bool
     */
    protected function deleteInner(EntityInterface $entity)
    {
        $this->conn()->createQueryBuilder()
            ->delete($this->tableName())
            ->where("{$this->primaryKey()} = ?")
            ->setParameter(0, $entity->{$this->primaryKey()})
            ->execute();

        return true;
    }

    /**
     * @param QueryBuilder $query
     *
     * @return int
     * @throws \Doctrine\DBAL\DBALException
     */
    protected function fetchCount(QueryBuilder $query)
    {
        $query = clone $query;

        $group = $query->getQueryPart('groupBy');
        if (!empty($group)) {
            $sql = $query->getSQL();
            $query->resetQueryParts();
            $query->from('(' . $sql . ')', 'tableToCount');
        }

        $query->select('count(*) total');
        $count = $query
            ->execute()
            ->fetchColumn(0);

        return (int)$count;
    }

    /**
     * @param QueryBuilder $query
     *
     * @return int
     * @throws \Doctrine\DBAL\DBALException
     */
    protected function fetchInt(QueryBuilder $query)
    {
        $scalar = $query->execute()
            ->fetchColumn(0);
        return (int)$scalar;
    }

    /**
     * 根据给定的QueryBuilder获取对象数组
     *
     * @param QueryBuilder $query
     *
     * @return array
     * @throws \Doctrine\DBAL\DBALException
     */
    protected function fetchAll(QueryBuilder $query, $extFields = [])
    {
        $stmt = $this->conn()->executeQuery(
            $query->getSQL(),
            $query->getParameters(),
            $query->getParameterTypes()
        );
        $pk = $this->primaryKey();
        $rows = $stmt->fetchAll();
        $items = [];
        foreach ($rows as $row) {
            $entity = $this->assemble($row);
            foreach ($extFields as $field) {
                $entity->$field = $row[$field];
            }

            if (isset($row[$pk])) {
                $id = $row[$pk];
                $items[$id] = $entity;
            } else {
                $items[] = $entity;
            }
        }

        return $items;
    }

    /**
     * 根据给定的QueryBuilder获取对象数组
     *
     * @param EntityInterface $entity
     * @param array $notUpdateFields
     * @return array
     * @throws \Doctrine\DBAL\DBALException
     *
     */
    public function save(EntityInterface $entity, $notUpdateFields = ['createTime'])
    {
        if ($entity instanceof LifeCycleInterface) {
            $entity->onUpdate();
        }
        if ($entity instanceof ValidatorInterface) {
            $entity->validate();
        }

        $row = $this->disassemble($entity);
        $fields = [];
        $params = [];

        foreach ($row as $key => $value) {
            if ($key === $this->primaryKey()) {
                continue;
            }
            $fields[] = $key;
            $params[':' . $key] = $row[$key];
        }

        $sql = "INSERT INTO {$this->tablename()} (" . implode(',', $fields)
            . ") VALUES (" . implode(',', array_keys($params))
            . ") on duplicate key update "
            . implode(',', array_map(function ($key)use($notUpdateFields) {
                if (in_array($key,$notUpdateFields)) {
                    return $key . ' = ' . $key;
                } else {
                    return $key . ' = :' . $key;
                }
            }, $fields));

        $conn = $this->conn();
        $stmt = $conn->executeQuery($sql, $params);
        $stmt->execute();
        $entity->{$this->primaryKey()} = (int)$conn->lastInsertId();
        return true;
    }


    /**
     * 根据给定的QueryBuilder获取对象
     *
     * @param QueryBuilder $query
     *
     * @return mixed|null
     */
    protected function fetch(QueryBuilder $query)
    {
        $query->setMaxResults(1);
        $items = $this->fetchAll($query);

        return !empty($items) ? array_shift($items) : null;
    }

    /**
     * 创建新的QueryBuilder
     *
     * @return \Doctrine\DBAL\Query\QueryBuilder
     */
    protected function query()
    {
        return $this->conn()
            ->createQueryBuilder()
            ->select('*')
            ->from($this->tableName());
    }


    function queryInItems($items, $keys)
    {
        $query = $this->query();

        if(empty($items)){
            return $query->andWhere('1=2');
        }

        $in = '(' . implode(',', array_map(function () use ($keys) {
                return '(' . implode(',', array_map(function () {
                    return '?';
                }, $keys)) . ')';
            }, $items)) . ')';

        $index = 0;
        foreach ($items as $item) {
            foreach ($keys as $key) {
                $query->setParameter($index++, ArrayHelper::getValueFromItem($item, $key));
            }
        }

        $query->andWhere("(" . implode(',', $keys) . ") in $in");
        return $query;
    }

    /**
     * 根据给定的主键获取对象
     *
     * @param $id
     *
     * @return mixed
     */
    function get($id)
    {
        $query = $this->query()
            ->where("{$this->primaryKey()} = ?")
            ->setParameter(0, $id)
            ->setMaxResults(1);

        return $this->fetch($query);
    }

    /**
     * 根据给定的主键集合获取对象
     *
     * @param array $ids
     *
     * @return mixed
     */
    function getAll(array $ids)
    {
        if (empty($ids)) {
            return [];
        }

        $query = $this->query()
            ->where("{$this->primaryKey()} IN (?)")
            ->setParameter(0, $ids, Connection::PARAM_INT_ARRAY);

        return $this->fetchAll($query);
    }

    /**
     * @param $query QueryBuilder
     * @param $search SearchBase
     * @param array $extFields
     * @param string $sort
     * @param string $order
     * @return PageResult
     */
    function fetchPage($query, $search, $sort = '', $order = '', $extFields = [])
    {
        $count = $this->fetchCount($query);
        if ($count > 0) {
            if ($sort) {
                $query->orderBy($sort, $order);
            }

            $query->setMaxResults($search->pageSize);

            if ($search->itemIndex) {
                $query->setFirstResult($search->itemIndex);
            } else {
                $query->setFirstResult($search->pageIndex * $search->pageSize);
            }

            $items = $this->fetchAll($query, $extFields);
        } else {
            $items = [];
        }

        return new PageResult(
            $items,
            $count,
            $search->pageIndex,
            $search->pageSize
        );
    }
}