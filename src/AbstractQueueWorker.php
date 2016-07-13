<?php
namespace H69\ContentMapping;

use H69\ContentMapping\Adapter\IndexableObjectProvider;
use H69\ContentMapping\Mapper\Result;

/**
 * Class AbstractQueueWorker
 * The AbstractQueueWorker contains constructor and functions for insert, update, delete and notify
 *
 * @package H69\ContentMapping
 */
abstract class AbstractQueueWorker
{
    /**
     * @var Adapter
     */
    protected $source;

    /**
     * @var \Iterator
     */
    protected $sourceQueue;

    /**
     * @var Adapter
     */
    protected $destination;

    /**
     * @var \Iterator
     */
    protected $destinationQueue;

    /**
     * @var string
     */
    protected $type;

    /**
     * @var callable
     */
    protected $mapCallback;

    /**
     * @var array
     */
    protected $messages = [];

    /**
     * @param Adapter|IndexableObjectProvider $source
     * @param Adapter|IndexableObjectProvider $destination
     */
    public function __construct($source, $destination)
    {
        if (!$source instanceof Adapter || !$destination instanceof Adapter) {
            throw new \InvalidArgumentException('source and destination have to implement "' . Adapter::class . '"');
        }

        $this->source = $source;
        $this->destination = $destination;
    }

    /**
     * @param mixed $sourceObject
     */
    protected function insert($sourceObject)
    {
        $newObjectInDestination = $this->destination->createObject(
            $this->source->idOf($sourceObject),
            $this->type
        );

        $mapResult = call_user_func_array($this->mapCallback, [
            $sourceObject,
            $newObjectInDestination,
        ]);
        if ($mapResult instanceof Result) {
            $this->destination->updated($mapResult->getObject());
        }

        $this->sourceQueue->next();
        $this->messages[] = 'Inserted object with id ' . $this->source->idOf($sourceObject);
    }

    /**
     * @param mixed $destinationObject
     */
    protected function delete($destinationObject)
    {
        $this->destination->delete($destinationObject);
        $this->destinationQueue->next();
        $this->messages[] = 'Deleted object with id ' . $this->destination->idOf($destinationObject);
    }

    /**
     * @param mixed $sourceObject
     * @param mixed $destinationObject
     */
    protected function update($sourceObject, $destinationObject)
    {
        if ($this->destination instanceof Adapter\UpdateableObjectProvider) {
            $destinationObject = $this->destination->prepareUpdate($destinationObject);
        }

        $mapResult = call_user_func_array($this->mapCallback, [
            $sourceObject,
            $destinationObject,
        ]);
        if ($mapResult instanceof Result && $mapResult->getObjectHasChanged() === true) {
            $this->destination->updated($mapResult->getObject());
            $this->messages[] = 'Updated object with id ' . $this->source->idOf($sourceObject);
        } else {
            $this->messages[] = 'Kept object with id ' . $this->source->idOf($sourceObject);
        }

        $this->destinationQueue->next();
        $this->sourceQueue->next();
    }

    protected function notifyProgress()
    {
        if ($this->destination instanceof Adapter\ProgressListener) {
            $this->destination->afterObjectProcessed();
        }
    }
}
