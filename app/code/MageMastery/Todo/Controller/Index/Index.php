<?php

declare(strict_types=1);

namespace MageMastery\Todo\Controller\Index;

use MageMastery\Todo\Model\ResourceModel\Task as TaskResource;
use MageMastery\Todo\Model\Task;
use MageMastery\Todo\Model\TaskFactory;
use MageMastery\Todo\Service\TaskRepository;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\ResultFactory;

class Index extends Action
{
    private $taskResource;

    private $taskFactory;

    private $taskRepository;

    public function __construct(
        Context $context,
        TaskFactory $taskFactory,
        TaskResource $task,
        TaskRepository $repository
    ) {
        $this->taskFactory    = $taskFactory;
        $this->taskResource   = $task;
        $this->taskRepository = $repository;
        parent::__construct($context);
    }

    public function execute()
    {
        var_dump($this->taskRepository->get(1)->getData());
        /** @var Task $task */
        //        $task = $this->taskFactory->create();
        //
        //        $task->setData([
        //            'label' => 'New Task 3',
        //            'status' => 'open',
        //            'customer_id' => 1
        //        ]);
        //
        //        $this->taskResource->save($task);
        return $this->resultFactory->create(ResultFactory::TYPE_PAGE);
    }
}
