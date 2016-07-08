<?php
namespace H69\ContentMapping;

/**
 * Class Synchronizer
 * The Synchronizer synchronizes objects from a source system with these in a destination system.
 *
 * @package H69\ContentMapping
 */
class Synchronizer extends AbstractQueueWorker
{

    /**
     * Synchronizes the $type objects from the source system to the destination system.
     *
     * @param string   $type
     * @param callable $mapCallback
     *
     * @throws \InvalidArgumentException
     * @return array
     */
    public function synchronize($type, $mapCallback)
    {
        if (empty($type)) {
            throw new \InvalidArgumentException('required parameter $type is empty');
        }

        if (!is_callable($mapCallback)) {
            throw new \InvalidArgumentException('required parameter $mapCallback is empty or not type of callable');
        }

        $this->type = $type;
        $this->mapCallback = $mapCallback;

        $this->messages[] = 'Start of synchronization for ' . $this->type;

        $this->sourceQueue = $this->source->getObjectsOrderedById($this->type);
        $this->sourceQueue->rewind();

        $this->destinationQueue = $this->destination->getObjectsOrderedById($this->type);
        $this->destinationQueue->rewind();

        while ($this->sourceQueue->valid() && $this->destinationQueue->valid()) {
            $this->compareQueuesAndReactAccordingly();
        }

        while ($this->sourceQueue->valid()) {
            $this->insert($this->sourceQueue->current());
            $this->notifyProgress();
        }

        while ($this->destinationQueue->valid()) {
            $this->delete($this->destinationQueue->current());
            $this->notifyProgress();
        }

        $this->destination->commit();

        $this->messages[] = 'End of synchronization for ' . $this->type;
        return $this->messages;
    }

    protected function compareQueuesAndReactAccordingly()
    {
        $sourceObject = $this->sourceQueue->current();
        $sourceObjectId = $this->source->idOf($sourceObject);
        $destinationObject = $this->destinationQueue->current();
        $destinationObjectId = $this->destination->idOf($destinationObject);

        if ($destinationObjectId > $sourceObjectId) {
            $this->insert($sourceObject);
        } elseif ($destinationObjectId < $sourceObjectId) {
            $this->delete($destinationObject);
        } else {
            // $destinationObjectId === $sourceObjectId
            $this->update($sourceObject, $destinationObject);
        }

        $this->notifyProgress();
    }
}
