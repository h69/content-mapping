<?php
namespace H69\ContentMapping\Tests;

use H69\ContentMapping\Adapter;
use H69\ContentMapping\Indexer;
use H69\ContentMapping\Mapper\Result;

/**
 * Class IndexerTest
 * tests for the Indexer
 *
 * @package H69\ContentMapping\Tests
 */
class IndexerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * System under test.
     *
     * @var Indexer
     */
    private $indexer;

    /**
     * Can be used as the parameter for $this->synchronizer->synchronize().
     *
     * @var string
     */
    private $type = 'arbitrary type';

    /**
     * @var Adapter|\PHPUnit_Framework_MockObject_MockObject
     */
    private $source;

    /**
     * @var Adapter|\PHPUnit_Framework_MockObject_MockObject
     */
    private $destination;

    /**
     * @see \PHPUnit_Framework_TestCase::setUp()
     */
    protected function setUp()
    {
        $this->source = $this->getMock(Adapter::class);
        $this->destination = $this->getMock(TestAdapterInterfaces::class);
        $this->indexer = new Indexer($this->source, $this->destination);
    }

    /**
     * @test
     */
    public function indexRewindsOnlySourceQueue()
    {
        $sourceQueue = $this->getMock('\Iterator');
        $sourceQueue->expects($this->once())
            ->method('rewind');
        $this->setUpSourceToReturn($sourceQueue);

        $destinationQueue = $this->getMock('\Iterator');
        $destinationQueue->expects($this->never())
            ->method('rewind');
        $this->setUpDestinationToReturn($destinationQueue);

        $this->indexer->index($this->type, function () {
            return Result::unchanged();
        });
    }

    /**
     * @test
     */
    public function indexHandlesEmptySourceObjectsSet()
    {
        $emptySet = new \ArrayIterator();
        $this->setUpSourceToReturn($emptySet);

        $this->setExpectedException(null);

        $this->indexer->index($this->type, function () {
            return Result::unchanged();
        });
    }

    /**
     * @test
     */
    public function indexHandlesCreatingNewObjects()
    {
        $idOfNewSourceObject = 1;
        $newSourceObject = new ObjectDummy();
        $this->setUpSourceToReturn(new \ArrayIterator(array($newSourceObject)));

        $this->source->expects($this->any())
            ->method('idOf')
            ->will($this->returnValue($idOfNewSourceObject));

        $this->source->expects($this->once())
            ->method('statusOf')
            ->will($this->returnValue(Adapter::STATUS_NEW));

        $newlyCreatedObject = new \stdClass();
        $this->destination->expects($this->once())
            ->method('createObject')
            ->with($idOfNewSourceObject, $this->type)
            ->will($this->returnValue($newlyCreatedObject));

        $this->destination->expects($this->once())
            ->method('updated')
            ->with($newlyCreatedObject);

        // afterObjectProcessed() called for every object
        $this->destination->expects($this->once())
            ->method('afterObjectProcessed');

        $this->destination->expects($this->once())
            ->method('commit');

        $this->indexer->index($this->type, function ($src, $dest) {
            return Result::changed($dest);
        });
    }

    /**
     * @test
     */
    public function indexHandlesDeletingOutdatedObjects()
    {
        $idOfOutdatedSourceObject = 1;
        $outdatedSourceObject = new ObjectDummy();
        $this->setUpSourceToReturn(new \ArrayIterator(array($outdatedSourceObject)));

        $this->source->expects($this->any())
            ->method('idOf')
            ->will($this->returnValue($idOfOutdatedSourceObject));

        $this->source->expects($this->once())
            ->method('statusOf')
            ->will($this->returnValue(Adapter::STATUS_DELETE));

        $newlyCreatedObject = new \stdClass();
        $this->destination->expects($this->once())
            ->method('createObject')
            ->with($idOfOutdatedSourceObject, $this->type)
            ->will($this->returnValue($newlyCreatedObject));

        $this->destination->expects($this->once())
            ->method('delete')
            ->with($newlyCreatedObject);

        // afterObjectProcessed() called for every object
        $this->destination->expects($this->once())
            ->method('afterObjectProcessed');

        $this->destination->expects($this->once())
            ->method('commit');

        $this->indexer->index($this->type, function ($src, $dest) {
            return Result::changed($dest);
        });
    }

    /**
     * @test
     */
    public function indexHandlesUpdatedObjects()
    {
        $idOfNewSourceObject = 1;
        $newSourceObject = new ObjectDummy();
        $this->setUpSourceToReturn(new \ArrayIterator(array($newSourceObject)));

        $this->source->expects($this->any())
            ->method('idOf')
            ->will($this->returnValue($idOfNewSourceObject));

        $this->source->expects($this->once())
            ->method('statusOf')
            ->will($this->returnValue(Adapter::STATUS_NEW));

        $newlyCreatedObject = new \stdClass();
        $this->destination->expects($this->once())
            ->method('createObject')
            ->with($idOfNewSourceObject, $this->type)
            ->will($this->returnValue($newlyCreatedObject));

        $this->destination->expects($this->once())
            ->method('updated')
            ->with($newlyCreatedObject);

        // afterObjectProcessed() called for every object
        $this->destination->expects($this->once())
            ->method('afterObjectProcessed');

        $this->destination->expects($this->once())
            ->method('commit');

        $this->indexer->index($this->type, function ($src, $dest) {
            return Result::changed($dest);
        });
    }

    /**
     * @param \Iterator $sourceObjects
     */
    private function setUpSourceToReturn(\Iterator $sourceObjects)
    {
        $this->source->expects($this->any())
            ->method('getObjectsOrderedById')
            ->will($this->returnValue($sourceObjects));
    }

    /**
     * @param \Iterator $destinationObjects
     */
    private function setUpDestinationToReturn(\Iterator $destinationObjects)
    {
        $this->destination->expects($this->any())
            ->method('getObjectsOrderedById')
            ->will($this->returnValue($destinationObjects));
    }
}
