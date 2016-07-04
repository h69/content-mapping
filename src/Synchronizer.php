<?php
/*
 * (c) webfactory GmbH <info@webfactory.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace H69\ContentMapping;

/**
 * The Synchronizer synchronizes objects from a source system with these in a destination system.
 */
class Synchronizer extends AbstractQueueWorker
{

    /**
     * Synchronizes the $type objects from the source system to the destination system.
     *
     * @param string $type
     * @param callable $mapCallback
     * @throws \InvalidArgumentException
     */
    public function synchronize($type, $mapCallback)
    {
        if(empty($type)){
            throw new \InvalidArgumentException('required parameter $type is empty');
        }

        if(!is_callable($mapCallback)){
            throw new \InvalidArgumentException('required parameter $mapCallback is empty or not type of callable');
        }

        $this->type = $type;
        $this->mapCallback = $mapCallback;

        $this->messages[] = 'Start of synchronization for '.$this->type;

        $this->sourceQueue = $this->source->getObjectsOrderedById($this->type);
        $this->sourceQueue->rewind();

        $this->destinationQueue = $this->destination->getObjectsOrderedById($this->type);
        $this->destinationQueue->rewind();

        while ($this->sourceQueue->valid() && $this->destinationQueue->valid()) {
            $this->compareQueuesAndReactAccordingly();
        }

        $this->insertRemainingSourceObjects();
        $this->deleteRemainingDestinationObjects();

        $this->destination->commit();

        $this->messages[] = 'End of synchronization for '.$this->type;
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
        } elseif ($destinationObjectId === $sourceObjectId) {
            $this->update($sourceObject, $destinationObject);
        } else {
            $this->destinationQueue->next();
            $this->sourceQueue->next();
        }

        $this->notifyProgress();
    }

    protected function insertRemainingSourceObjects()
    {
        while ($this->sourceQueue->valid()) {
            $this->insert($this->sourceQueue->current());
            $this->notifyProgress();
        }
    }

    protected function deleteRemainingDestinationObjects()
    {
        while ($this->destinationQueue->valid()) {
            $this->delete($this->destinationQueue->current());
            $this->notifyProgress();
        }
    }
}
