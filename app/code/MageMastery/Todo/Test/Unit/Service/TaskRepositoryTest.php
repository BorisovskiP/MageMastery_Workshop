<?php

declare(strict_types=1);

namespace MageMastery\Todo\Test\Unit\Service;

use MageMastery\Todo\Api\Data\TaskSearchResultInterface;
use MageMastery\Todo\Api\Data\TaskSearchResultInterfaceFactory;
use MageMastery\Todo\Model\ResourceModel\Task;
use MageMastery\Todo\Model\ResourceModel\Task\Collection;
use MageMastery\Todo\Model\Task as TaskModel;
use MageMastery\Todo\Model\TaskFactory;
use MageMastery\Todo\Service\TaskRepository;
use Magento\Framework\Api\SearchCriteria\CollectionProcessorInterface;
use Magento\Framework\Api\SearchCriteriaInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class TaskRepositoryTest extends TestCase
{
    /**
     * @var MockObject
     */
    private $resource;

    /**
     * @var MockObject
     */
    private $taskFactory;

    /**
     * @var MockObject
     */
    private $collectionProcessor;

    /**
     * @var MockObject
     */
    private $searchResultsFactory;

    /**
     * @var MockObject
     */
    private $searchResult;

    /**
     * @var MockObject
     */
    private $searchCriteria;

    /**
     * @var MockObject
     */
    private $task;

    protected function setUp()
    {
        $this->resource = $this->getMockBuilder(Task::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->taskFactory = $this->getMockBuilder(TaskFactory::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->collectionProcessor = $this->getMockForAbstractClass(
            CollectionProcessorInterface::class,
            [],
            '',
            false,
            false,
            true,
            ['process']
        );

        $this->searchResultsFactory = $this->getMockBuilder(TaskSearchResultInterfaceFactory::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->searchResult = $this->getMockBuilder(Collection::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->searchCriteria = $this->getMockForAbstractClass(
            SearchCriteriaInterface::class,
            [],
            '',
            false,
            false,
            true,
            []
        );

        $this->task = $this->getMockBuilder(TaskModel::class)
            ->disableOriginalConstructor()
            ->getMock();
    }

    public function testGetList()
    {
        $repository = new TaskRepository(
            $this->resource,
            $this->taskFactory,
            $this->collectionProcessor,
            $this->searchResultsFactory
        );

        $this->searchResultsFactory->expects($this->any())
            ->method('create')
            ->willReturn($this->searchResult);

        $this->searchResult->expects($this->any())
            ->method('setSearchCriteria');

        $searchResult = $repository->getList($this->searchCriteria);
        $this->assertTrue($searchResult instanceof TaskSearchResultInterface);
    }

    public function testGet()
    {
        $repository = new TaskRepository(
            $this->resource,
            $this->taskFactory,
            $this->collectionProcessor,
            $this->searchResultsFactory
        );

        $this->taskFactory->expects($this->any())
            ->method('create')
            ->willReturn($this->task);

        $this->task->expects($this->any())
            ->method('getTaskId')
            ->willReturn(1);

        $task = $repository->get(1);
        $this->assertNotEmpty($task);
        $this->assertEquals(1, $task->getTaskId());
    }
}
