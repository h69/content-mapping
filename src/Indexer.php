<?php
/*
 * (c) webfactory GmbH <info@webfactory.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace H69\ContentMapping;
use H69\ContentMapping\Mapper\Result;

/**
 * The Indexer sends specific objects from a source system into a destination system.
 */
class Indexer extends AbstractQueueWorker
{

    /**
     * indexes the $type objects from the source system to the destination system.
     *
     * @param string $type
     * @param callable $mapCallback
     * @throws \InvalidArgumentException
     */
    public function index($type, $mapCallback)
    {
        if(empty($type)){
            throw new \InvalidArgumentException('required parameter $type is empty');
        }

        if(!is_callable($mapCallback)){
            throw new \InvalidArgumentException('required parameter $mapCallback is empty or not type of callable');
        }

        $this->type = $type;
        $this->mapCallback = $mapCallback;

        $this->messages[] = 'Start of indexing for '.$this->type;

        $this->sourceQueue = $this->source->getObjectsOrderedById($this->type, true);
        $this->sourceQueue->rewind();

        while ($this->sourceQueue->valid()) {
            $sourceObject = $this->sourceQueue->current();
            $sourceObjectStatus = $this->source->statusOf($sourceObject);

            if ($sourceObjectStatus == Adapter::STATUS_NEW) {
                $this->insert($sourceObject);
            } elseif ($sourceObjectStatus == Adapter::STATUS_DELETE) {
                $this->delete($sourceObject);
            } elseif ($sourceObjectStatus == Adapter::STATUS_UPDATE) {
                $this->update($sourceObject);
            } else {
                $this->sourceQueue->next();
            }

            $this->notifyProgress();
        }

        $this->destination->commit();

        $this->messages[] = 'End of indexing for '.$this->type;
        return $this->messages;
    }

    /**
     * @param mixed $sourceObject
     */
    protected function insert($sourceObject)
    {
        $this->update($sourceObject);
    }

    /**
     * @param mixed $sourceObject
     */
    protected function delete($sourceObject)
    {
        $sourceObjectId = $this->source->idOf($sourceObject);
        $newObjectInDestination = $this->destination->createObject(
            $sourceObjectId,
            $this->type
        );

        $this->destination->delete($newObjectInDestination);
        $this->destinationQueue->next();
        $this->messages[] = 'Deleted object with id '.$sourceObjectId;
    }

    /**
     * @param mixed $sourceObject
     */
    protected function update($sourceObject)
    {
        $newObjectInDestination = $this->destination->createObject(
            $this->source->idOf($sourceObject),
            $this->type
        );

        $mapResult = call_user_func_array($this->mapCallback, [
            $sourceObject,
            $newObjectInDestination
        ]);
        if($mapResult instanceof Result){
            $this->destination->updated($mapResult->getObject());
        }

        $this->sourceQueue->next();
        $this->messages[] = 'Inserted/Updated object with id '.$this->source->idOf($sourceObject);
    }

}
