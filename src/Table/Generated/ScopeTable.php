<?php

namespace Fusio\Impl\Table\Generated;

class ScopeTable extends \PSX\Sql\TableAbstract
{
    public const NAME = 'fusio_scope';
    public const COLUMN_ID = 'id';
    public const COLUMN_CATEGORY_ID = 'category_id';
    public const COLUMN_STATUS = 'status';
    public const COLUMN_NAME = 'name';
    public const COLUMN_DESCRIPTION = 'description';
    public function getName() : string
    {
        return self::NAME;
    }
    public function getColumns() : array
    {
        return array(self::COLUMN_ID => 0x3020000a, self::COLUMN_CATEGORY_ID => 0x20000a, self::COLUMN_STATUS => 0x20000a, self::COLUMN_NAME => 0xa00020, self::COLUMN_DESCRIPTION => 0xa000ff);
    }
    /**
     * @return \Fusio\Impl\Table\Generated\ScopeRow[]
     * @throws \PSX\Sql\Exception\QueryException
     */
    public function findAll(?\PSX\Sql\Condition $condition = null, ?int $startIndex = null, ?int $count = null, ?string $sortBy = null, ?int $sortOrder = null, ?\PSX\Sql\Fields $fields = null) : iterable
    {
        return $this->doFindAll($condition, $startIndex, $count, $sortBy, $sortOrder, $fields);
    }
    /**
     * @return \Fusio\Impl\Table\Generated\ScopeRow[]
     * @throws \PSX\Sql\Exception\QueryException
     */
    public function findBy(\PSX\Sql\Condition $condition, ?int $startIndex = null, ?int $count = null, ?string $sortBy = null, ?int $sortOrder = null, ?\PSX\Sql\Fields $fields = null) : iterable
    {
        return $this->doFindBy($condition, $startIndex, $count, $sortBy, $sortOrder, $fields);
    }
    /**
     * @throws \PSX\Sql\Exception\QueryException
     */
    public function findOneBy(\PSX\Sql\Condition $condition, ?\PSX\Sql\Fields $fields = null) : ?\Fusio\Impl\Table\Generated\ScopeRow
    {
        return $this->doFindOneBy($condition, $fields);
    }
    /**
     * @throws \PSX\Sql\Exception\QueryException
     */
    public function find(int $id) : ?\Fusio\Impl\Table\Generated\ScopeRow
    {
        $condition = new \PSX\Sql\Condition();
        $condition->equals('id', $id);
        return $this->doFindOneBy($condition);
    }
    /**
     * @return \Fusio\Impl\Table\Generated\ScopeRow[]
     * @throws \PSX\Sql\Exception\QueryException
     */
    public function findById(int $value, ?int $startIndex = null, ?int $count = null, ?string $sortBy = null, ?int $sortOrder = null) : iterable
    {
        $condition = new \PSX\Sql\Condition();
        $condition->equals('id', $value);
        return $this->doFindBy($condition, $startIndex, $count, $sortBy, $sortOrder);
    }
    /**
     * @throws \PSX\Sql\Exception\QueryException
     */
    public function findOneById(int $value) : ?\Fusio\Impl\Table\Generated\ScopeRow
    {
        $condition = new \PSX\Sql\Condition();
        $condition->equals('id', $value);
        return $this->doFindOneBy($condition);
    }
    /**
     * @return \Fusio\Impl\Table\Generated\ScopeRow[]
     * @throws \PSX\Sql\Exception\QueryException
     */
    public function findByCategoryId(int $value, ?int $startIndex = null, ?int $count = null, ?string $sortBy = null, ?int $sortOrder = null) : iterable
    {
        $condition = new \PSX\Sql\Condition();
        $condition->equals('category_id', $value);
        return $this->doFindBy($condition, $startIndex, $count, $sortBy, $sortOrder);
    }
    /**
     * @throws \PSX\Sql\Exception\QueryException
     */
    public function findOneByCategoryId(int $value) : ?\Fusio\Impl\Table\Generated\ScopeRow
    {
        $condition = new \PSX\Sql\Condition();
        $condition->equals('category_id', $value);
        return $this->doFindOneBy($condition);
    }
    /**
     * @return \Fusio\Impl\Table\Generated\ScopeRow[]
     * @throws \PSX\Sql\Exception\QueryException
     */
    public function findByStatus(int $value, ?int $startIndex = null, ?int $count = null, ?string $sortBy = null, ?int $sortOrder = null) : iterable
    {
        $condition = new \PSX\Sql\Condition();
        $condition->equals('status', $value);
        return $this->doFindBy($condition, $startIndex, $count, $sortBy, $sortOrder);
    }
    /**
     * @throws \PSX\Sql\Exception\QueryException
     */
    public function findOneByStatus(int $value) : ?\Fusio\Impl\Table\Generated\ScopeRow
    {
        $condition = new \PSX\Sql\Condition();
        $condition->equals('status', $value);
        return $this->doFindOneBy($condition);
    }
    /**
     * @return \Fusio\Impl\Table\Generated\ScopeRow[]
     * @throws \PSX\Sql\Exception\QueryException
     */
    public function findByName(string $value, ?int $startIndex = null, ?int $count = null, ?string $sortBy = null, ?int $sortOrder = null) : iterable
    {
        $condition = new \PSX\Sql\Condition();
        $condition->like('name', $value);
        return $this->doFindBy($condition, $startIndex, $count, $sortBy, $sortOrder);
    }
    /**
     * @throws \PSX\Sql\Exception\QueryException
     */
    public function findOneByName(string $value) : ?\Fusio\Impl\Table\Generated\ScopeRow
    {
        $condition = new \PSX\Sql\Condition();
        $condition->like('name', $value);
        return $this->doFindOneBy($condition);
    }
    /**
     * @return \Fusio\Impl\Table\Generated\ScopeRow[]
     * @throws \PSX\Sql\Exception\QueryException
     */
    public function findByDescription(string $value, ?int $startIndex = null, ?int $count = null, ?string $sortBy = null, ?int $sortOrder = null) : iterable
    {
        $condition = new \PSX\Sql\Condition();
        $condition->like('description', $value);
        return $this->doFindBy($condition, $startIndex, $count, $sortBy, $sortOrder);
    }
    /**
     * @throws \PSX\Sql\Exception\QueryException
     */
    public function findOneByDescription(string $value) : ?\Fusio\Impl\Table\Generated\ScopeRow
    {
        $condition = new \PSX\Sql\Condition();
        $condition->like('description', $value);
        return $this->doFindOneBy($condition);
    }
    /**
     * @throws \PSX\Sql\Exception\ManipulationException
     */
    public function create(\Fusio\Impl\Table\Generated\ScopeRow $record) : int
    {
        return $this->doCreate($record);
    }
    /**
     * @throws \PSX\Sql\Exception\ManipulationException
     */
    public function update(\Fusio\Impl\Table\Generated\ScopeRow $record) : int
    {
        return $this->doUpdate($record);
    }
    /**
     * @throws \PSX\Sql\Exception\ManipulationException
     */
    public function delete(\Fusio\Impl\Table\Generated\ScopeRow $record) : int
    {
        return $this->doDelete($record);
    }
    protected function newRecord(array $row) : \Fusio\Impl\Table\Generated\ScopeRow
    {
        return new \Fusio\Impl\Table\Generated\ScopeRow($row);
    }
}