<?php
namespace H69\ContentMapping;

/**
 * Interface Adapter
 * default interface to implement when creating a source/destination adapter
 * for using in Synchronizer or Indexer
 *
 * @package H69\ContentMapping
 */
interface Adapter
{

    /**
     * Get an Iterator over all $type objects in the source/destination system, ordered by their ascending IDs.
     *
     * @param string $type Type of Objects to return
     *
     * @return \Iterator
     */
    public function getObjectsOrderedById($type);

    /**
     * Get the id of an object
     *
     * @param mixed $object
     *
     * @return int
     */
    public function idOf($object);

    /**
     * Create a new object in the target system identified by ($id and $type).
     *
     * @param int    $id   ID of the newly created Object
     * @param string $type Type of the newly created Object
     *
     * @return mixed
     */
    public function createObject($id, $type);

    /**
     * Delete the $object from the target system.
     *
     * @param mixed $object
     */
    public function delete($object);

    /**
     * This method is a hook e.g. to notice an external change tracker that the $object has been updated.
     *
     * Although the name is somewhat misleading, it will be called after the Mapper has processed
     *   a) new objects created by the createObject() method
     *   b) changed objects created by the prepareUpdate() method *only if* the object actually changed.
     *
     * @param mixed $object
     */
    public function updated($object);

    /**
     * This method is a hook e.g. to notice an external change tracker that all the in memory synchronization is
     * finished, i.e. can be persisted (e.g. by calling an entity manager's flush()).
     */
    public function commit();
}
