<?php

declare(strict_types=1);

namespace MageMastery\Todo\Model;

use MageMastery\Todo\Api\Data\TaskInterface;
use MageMastery\Todo\Model\ResourceModel\Task as TaskResource;
use Magento\Framework\Model\AbstractModel;

class Task extends AbstractModel implements TaskInterface
{
    public const TASK_ID = 'task_id';
    public const STATUS = 'status';
    public const LABEL = 'label';

    protected function _construct()
    {
        $this->_init(TaskResource::class);
    }

    /**
     * @return int
     */
    public function getTaskId(): int
    {
        return (int)$this->getData(self::TASK_ID);
    }

    /**
     * @return string
     */
    public function getStatus(): string
    {
        return $this->getData(self::STATUS);
    }

    /**
     * @return string
     */
    public function getLabel(): string
    {
        return $this->getData(self::LABEL);

    }
}
