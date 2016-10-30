<?php
namespace Gram\Entity;

use Doctrine\DBAL\Query\QueryBuilder;
use Doctrine\DBAL\Connection;
use Gram\Domain\Repository\Dbal\ConnectionFactory;
use Respect\Validation\Validator as V;
use ZuoYeah\Entity\PageResult;
use ZuoYeah\Entity\Search\SearchBase;

trait EntityTrait
{
    use ObjectTrait;

    use EntityColumnTrait;


    static protected function primaryKey()
    {
        return 'id';
    }

    static protected function tableName()
    {
        $tableName = (array_pop(explode('\\', __CLASS__)));
        $tableName = preg_replace('/[A-Z]/', '_$0', $tableName);
        $tableName = substr($tableName, 1);
        return strtolower($tableName);
    }


    /**
     * @return \Doctrine\DBAL\Connection
     */
    static protected function getConnection()
    {
        return ConnectionFactory::getConnection();
    }


    /**
     * @return array
     */
    protected function metadata()
    {
        return [
            'id' => 'integer',
            'updateTime' => '\DateTime',
            'createTime' => '\DateTime'
        ];
    }


    /**
     * @param $value
     * @return int
     */
    static protected function getPdoType($value)
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
     * 创建新的QueryBuilder
     *
     * @return \Doctrine\DBAL\Query\QueryBuilder
     */
    static protected function query()
    {
        return static::getConnection()
            ->createQueryBuilder()
            ->select('*')
            ->from(static::tableName());
    }

    /**
     * 根据给定的QueryBuilder获取对象
     *
     * @param QueryBuilder $query
     *
     * @return mixed|null
     */
    static protected function fetch(QueryBuilder $query)
    {
        $query->setMaxResults(1);
        $items = static::fetchAll($query);

        return !empty($items) ? array_shift($items) : null;
    }

    /**
     * 根据给定的QueryBuilder获取对象数组
     *
     * @param QueryBuilder $query
     *
     * @return array
     * @throws \Doctrine\DBAL\DBALException
     */
    static protected function fetchAll(QueryBuilder $query)
    {
        $stmt = static::getConnection()->executeQuery(
            $query->getSQL(),
            $query->getParameters(),
            $query->getParameterTypes()
        );
        $pk = static::primaryKey();
        $rows = $stmt->fetchAll();
        $items = [];
        foreach ($rows as $row) {
            $entity = static::assemble($row);
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
     * @param $query QueryBuilder
     * @param $search SearchBase
     * @param array $extFields
     * @param string $sort
     * @param string $order
     * @return PageResult
     */
    static protected function fetchPage($query, $search, $sort = '', $order = '', $extFields = [])
    {
        $count = self::fetchCount($query);
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

            $items = self::fetchAll($query, $extFields);
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

    /**
     * @param QueryBuilder $query
     *
     * @return int
     * @throws \Doctrine\DBAL\DBALException
     */
    static protected function fetchCount(QueryBuilder $query)
    {
        $select = $query->getQueryPart('select');
        $count = $query->select('COUNT(*)')
            ->execute()
            ->fetchColumn(0);
        $query->select($select);

        return (int)$count;
    }

    /**
     * 根据给定的主键获取对象
     *
     * @param $id
     *
     * @return mixed
     */
    static function find($id)
    {
        $query = static::query()
            ->where(sprintf('%s = ?', static::primaryKey()))
            ->setParameter(0, $id)
            ->setMaxResults(1);

        return static::fetch($query);
    }

    /**
     * 根据给定的主键集合获取对象
     *
     * @param array $ids
     *
     * @return mixed
     */
    static function findAll(array $ids)
    {
        if (empty($ids)) {
            return [];
        }

        $query = static::query()
            ->where(sprintf('%s IN (?)', static::primaryKey()))
            ->setParameter(0, $ids, Connection::PARAM_INT_ARRAY);

        return static::fetchAll($query);
    }

    /**
     * 创建对象
     *
     * @param EntityInterface $entity
     * @return bool
     */
    function insert()
    {
        if ($this instanceof LifeCycleInterface) {
            $this->onCreate();
        }
        if ($this instanceof ValidatorInterface) {
            $this->validate();
        }

        $conn = static::getConnection();
        $query = $conn->createQueryBuilder()->insert($this->tableName());
        $row = static::disassemble($this);

        $index = 0;
        foreach ($row as $key => $value) {
            if ($key === $this->primaryKey() && !$value) {
                continue;
            }
            $query->setValue($key, '?');
            $query->setParameter($index++, $value, static::getPdoType($value));
        }
        if ($query->execute()) {
            $this->{$this->primaryKey()} = (int)$conn->lastInsertId();

            return true;
        }

        return false;
    }

    /**
     * @param EntityInterface $entity
     * @return bool
     */
    function update()
    {
        if ($this instanceof LifeCycleInterface) {
            $this->onUpdate();
        }
        if ($this instanceof ValidatorInterface) {
            $this->validate();
        }

        $query = static::getConnection()
            ->createQueryBuilder()
            ->update($this->tableName());

        $row = static::disassemble($this);

        $index = 0;
        foreach ($row as $key => $value) {
            if ($key === $this->primaryKey()) {
                continue;
            }
            $query->set($key, '?');
            $query->setParameter($index++, $value, static::getPdoType($value));
        }

        $query->where(sprintf('%s = ?', static::primaryKey()))
            ->setParameter($index, $this->{$this->primaryKey()})
            ->execute();

        return true;
    }

    /**
     * 删除对象
     *
     * @param EntityInterface $entity
     * @return bool
     */
    function delete()
    {
        if ($this instanceof LifeCycleInterface) {
            $this->onDelete();
        }

        static::getConnection()
            ->createQueryBuilder()
            ->delete($this->tableName())
            ->where(sprintf('%s = ?', static::primaryKey()))
            ->setParameter(0, $this->{$this->primaryKey()})
            ->execute();

        return true;
    }

}