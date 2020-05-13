<?php

declare(strict_types=1);

namespace MageMastery\Todo\Controller\Index;

use MageMastery\Todo\Api\TaskManagementInterface;
use MageMastery\Todo\Model\ResourceModel\Task as TaskResource;
use MageMastery\Todo\Model\Task;
use MageMastery\Todo\Model\TaskFactory;
use MageMastery\Todo\Service\TaskRepository;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\ResultFactory;

class Index extends Action
{
    private $taskResource;

    private $taskFactory;

    private $taskRepository;

    /**
     * @var SearchCriteriaBuilder
     */
    private $searchCriteriaBuilder;

    /**
     * @var TaskManagementInterface
     */
    private $taskManagement;

    public function __construct(
        Context $context,
        TaskFactory $taskFactory,
        TaskResource $task,
        TaskRepository $repository,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        TaskManagementInterface $taskManagement
    ) {
        $this->taskFactory           = $taskFactory;
        $this->taskResource          = $task;
        $this->taskRepository        = $repository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->taskManagement        = $taskManagement;
        parent::__construct($context);

    }

    public function execute()
    {
        return $this->resultFactory->create(ResultFactory::TYPE_PAGE);
    }
}
