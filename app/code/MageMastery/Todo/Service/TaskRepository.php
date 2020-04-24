<?php

declare(strict_types=1);

namespace MageMastery\Todo\Service;

use MageMastery\Todo\Api\TaskRepositoryInterface;
use MageMastery\Todo\Model\ResourceModel\Task;
use MageMastery\Todo\Model\TaskFactory;

class TaskRepository implements TaskRepositoryInterface
{
    /**
     * @var Task
     */
    private $resource;

    /**
     * @var TaskFactory
     */
    private $taskFactory;

    public function __construct(Task $resource, TaskFactory $taskFactory)
    {
        $this->resource    = $resource;
        $this->taskFactory = $taskFactory;
    }

    public function getList()
    {

    }

    public function get(int $taskId)
    {
        $object = $this->taskFactory->create();
        $this->resource->load($object, $taskId);

        return $object;
    }
}
