<?php
/*
 * (c) webfactory GmbH <info@webfactory.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace H69\ContentMapping\Tests;

use H69\ContentMapping\Adapter;
use H69\ContentMapping\Mapper\Result;
use H69\ContentMapping\Synchronizer;

/**
 * Tests for the Synchronize.
 */
class SynchronizerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * System under test.
     *
     * @var Synchronizer
     */
    private $synchronizer;

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
        $this->synchronizer = new Synchronizer($this->source, $this->destination);
    }

    /**
     * @test
     */
    public function synchronizeRewindsSourceQueueAndDestinationQueue()
    {
        $sourceQueue = $this->getMock('\Iterator');
        $sourceQueue->expects($this->once())
            ->method('rewind');
        $this->setUpSourceToReturn($sourceQueue);

        $destinationQueue = $this->getMock('\Iterator');
        $destinationQueue->expects($this->once())
            ->method('rewind');
        $this->setUpDestinationToReturn($destinationQueue);

        $this->synchronizer->synchronize($this->type, function($srcObject, $destObject){

        });
    }

    /**
     * @test
     */
    public function synchronizeHandlesEmptySourceObjectsSetAndEmptyDestinationObjectsSet()
    {
        $emptySet = new \ArrayIterator();
        $this->setUpSourceToReturn($emptySet);
        $this->setUpDestinationToReturn($emptySet);

        $this->setExpectedException(null);

        $this->synchronizer->synchronize($this->type, function($srcObject, $destObject){

        });
    }

    /**
     * @test
     */
    public function synchronizeHandlesLongerSourceQueuesByCreatingNewObjects()
    {
        $idOfNewSourceObject = 1;
        $newSourceObject = new ObjectDummy();
        $this->setUpSourceToReturn(new \ArrayIterator(array($newSourceObject)));

        $emptySet = new \ArrayIterator();
        $this->setUpDestinationToReturn($emptySet);

        $this->source->expects($this->any())
            ->method('idOf')
            ->will($this->returnValue($idOfNewSourceObject));
        $newlyCreatedObject = new \stdClass();
        $this->destination->expects($this->once())
            ->method('createObject')
            ->with($idOfNewSourceObject, $this->type)
            ->will($this->returnValue($newlyCreatedObject));

        $this->synchronizer->synchronize($this->type, function($srcObject, $destObject){
            return Result::unchanged();
        });
    }

    /**
     * @test
     */
    public function synchronizeCallsHooksAfterCreatingNewObject()
    {
        $idOfNewSourceObject = 1;
        $newSourceObject = new ObjectDummy();
        $this->setUpSourceToReturn(new \ArrayIterator(array($newSourceObject)));

        $emptySet = new \ArrayIterator();
        $this->setUpDestinationToReturn($emptySet);

        $this->source->expects($this->any())
            ->method('idOf')
            ->will($this->returnValue($idOfNewSourceObject));
        $newlyCreatedObject = new \stdClass();
        $this->destination->expects($this->once())
            ->method('createObject')
            ->with($idOfNewSourceObject, $this->type)
            ->will($this->returnValue($newlyCreatedObject));
        $this->destination->expects($this->once())
            ->method('updated');

        // afterObjectProcessed() called for every object
        $this->destination->expects($this->once())
            ->method('afterObjectProcessed');

        // commit() called at the end
        $this->destination->expects($this->once())
            ->method('commit');

        $this->synchronizer->synchronize($this->type, function($srcObject, $destObject){
            return Result::unchanged();
        });
    }

    /**
     * @test
     */
    public function synchronizeHandlesLongerDestinationQueuesByDeletingOutdatedObjects()
    {
        $emptySet = new \ArrayIterator();
        $this->setUpSourceToReturn($emptySet);

        $outdatedDestinationObject = new ObjectDummy();
        $this->setUpDestinationToReturn(new \ArrayIterator(array($outdatedDestinationObject)));

        $this->destination->expects($this->once())
            ->method('delete')
            ->with($outdatedDestinationObject);

        $this->synchronizer->synchronize($this->type, function($srcObject, $destObject){

        });
    }

    /**
     * @test
     */
    public function synchronizeCallsHooksAfterDelete()
    {
        $emptySet = new \ArrayIterator();
        $this->setUpSourceToReturn($emptySet);

        $outdatedDestinationObject = new ObjectDummy();
        $this->setUpDestinationToReturn(new \ArrayIterator(array($outdatedDestinationObject)));

        // delete() indicated object has to be removed
        $this->destination->expects($this->once())
            ->method('delete');

        // afterObjectProcessed() called for every object
        $this->destination->expects($this->once())
            ->method('afterObjectProcessed');

        // commit() always called at the end
        $this->destination->expects($this->once())
            ->method('commit');

        $this->synchronizer->synchronize($this->type, function($srcObject, $destObject){

        });
    }

    /**
     * @test
     */
    public function synchronizeHandlesSameSourceIdAsDestinationIdInQueueComparisonByUpdatingObject()
    {
        $sameIdForSourceAndDestinationObject = 1;
        $newerVersionOfSourceObject = new ObjectDummy();
        $this->setUpSourceToReturn(new \ArrayIterator(array($newerVersionOfSourceObject)));

        $olderVersionOfDestinationObject = new ObjectDummy();
        $this->setUpDestinationToReturn(new \ArrayIterator(array($olderVersionOfDestinationObject)));

        $this->source->expects($this->any())
            ->method('idOf')
            ->will($this->returnValue($sameIdForSourceAndDestinationObject));
        $this->destination->expects($this->any())
            ->method('idOf')
            ->will($this->returnValue($sameIdForSourceAndDestinationObject));

        $this->synchronizer->synchronize($this->type, function($srcObject, $destObject) use ($olderVersionOfDestinationObject) {
            return Result::changed($olderVersionOfDestinationObject);
        });
    }

    /**
     * @test
     */
    public function synchronizeCallsUpdatedForObjectsThatGotUpdated()
    {
        $sameIdForSourceAndDestinationObject = 1;
        $newerVersionOfSourceObject = new ObjectDummy();
        $this->setUpSourceToReturn(new \ArrayIterator(array($newerVersionOfSourceObject)));

        $olderVersionOfDestinationObject = new ObjectDummy();
        $this->setUpDestinationToReturn(new \ArrayIterator(array($olderVersionOfDestinationObject)));

        $this->source->expects($this->any())
            ->method('idOf')
            ->will($this->returnValue($sameIdForSourceAndDestinationObject));
        $this->destination->expects($this->any())
            ->method('idOf')
            ->will($this->returnValue($sameIdForSourceAndDestinationObject));

        $this->destination->expects($this->once())
            ->method('updated')
            ->with($olderVersionOfDestinationObject);

        // afterObjectProcessed() called for every object
        $this->destination->expects($this->once())
            ->method('afterObjectProcessed');

        $this->destination->expects($this->once())
            ->method('commit');

        $this->synchronizer->synchronize($this->type, function($srcObject, $destObject) use ($olderVersionOfDestinationObject) {
            return Result::changed($olderVersionOfDestinationObject);
        });
    }

    /**
     * @test
     */
    public function synchronizeDoesNotCallUpdatedForObjectsThatRemainedTheSame()
    {
        $sameIdForSourceAndDestinationObject = 1;
        $newerVersionOfSourceObject = new ObjectDummy();
        $this->setUpSourceToReturn(new \ArrayIterator(array($newerVersionOfSourceObject)));

        $olderVersionOfDestinationObject = new ObjectDummy();
        $this->setUpDestinationToReturn(new \ArrayIterator(array($olderVersionOfDestinationObject)));

        $this->source->expects($this->any())
            ->method('idOf')
            ->will($this->returnValue($sameIdForSourceAndDestinationObject));
        $this->destination->expects($this->any())
            ->method('idOf')
            ->will($this->returnValue($sameIdForSourceAndDestinationObject));

        $this->destination->expects($this->never())
            ->method('updated');

        // afterObjectProcessed() called for every object
        $this->destination->expects($this->once())
            ->method('afterObjectProcessed');

        $this->destination->expects($this->once())
            ->method('commit');

        $this->synchronizer->synchronize($this->type, function($srcObject, $destObject){
            return Result::unchanged();
        });
    }

    /**
     * @test
     */
    public function synchronizeHandlesLowerSourceIdInQueueComparisonByCreatingObject()
    {
        $idOfSourceObject = 1;
        $sourceObject = new ObjectDummy();
        $this->setUpSourceToReturn(new \ArrayIterator(array($sourceObject)));

        $idOfDestinationObject = 2;
        $destinationObject = new ObjectDummy();
        $this->setUpDestinationToReturn(new \ArrayIterator(array($destinationObject)));

        $this->source->expects($this->any(0))
            ->method('idOf')
            ->will($this->returnValue($idOfSourceObject));
        $this->destination->expects($this->any())
            ->method('idOf')
            ->will($this->returnValue($idOfDestinationObject));

        $newlyCreatedObject = new \stdClass();
        $this->destination->expects($this->once())
            ->method('createObject')
            ->with($idOfSourceObject, $this->type)
            ->will($this->returnValue($newlyCreatedObject));

        $this->synchronizer->synchronize($this->type, function($srcObject, $destObject) use ($newlyCreatedObject) {
            return Result::changed($newlyCreatedObject);
        });
    }

    /**
     * @test
     */
    public function synchronizeHandlesHigherSourceIdInQueueComparisonByCreatingObject()
    {
        $idOfSourceObject = 2;
        $sourceObject = new ObjectDummy();
        $this->setUpSourceToReturn(new \ArrayIterator(array($sourceObject)));

        $idOfDestinationObject = 1;
        $destinationObject = new ObjectDummy();
        $this->setUpDestinationToReturn(new \ArrayIterator(array($destinationObject)));

        $this->source->expects($this->any(0))
            ->method('idOf')
            ->will($this->returnValue($idOfSourceObject));
        $this->destination->expects($this->any())
            ->method('idOf')
            ->will($this->returnValue($idOfDestinationObject));

        $this->destination->expects($this->once())
            ->method('delete')
            ->with($destinationObject);

        $this->synchronizer->synchronize($this->type, function($srcObject, $destObject) use ($destinationObject) {
            return Result::changed($destinationObject);
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
