<?php
namespace ZuoYeah\Entity;

use Gram\Domain\Entity\EntityInterface;

/**
 * Class School
 * @package ZuoYeah\Entity
 */
class PageResult implements EntityInterface
{
    /**
     * @var int
     */
    public $totalCount;

    /**
     * @var int
     */
    public $pageIndex;

    /**
     * @var int
     */
    public $pageSize;

    /**
     * @var int
     */
    public $totalPage;

    /**
     * @var array
     */
    public $items = [];

    /**
     * @param     $items
     * @param int $totalCount
     * @param int $pageIndex
     * @param int $pageSize
     */
    function __construct($items, $totalCount, $pageIndex, $pageSize)
    {
        $this->items = $items;
        $this->totalCount = (int)$totalCount;
        $this->pageIndex = (int)$pageIndex;
        $this->pageSize = (int)$pageSize;
        $this->totalPage = intval(ceil(floatval($totalCount) / $pageSize));
    }

    /**
     * 移除Items中所有的字符串索引
     */
    function removeKeysFromItems()
    {
        if (is_array($this->items)) {
            $this->items = array_values($this->items);
        }
    }
}