<?php
namespace H69\ContentMapping\Adapter;

/**
 * Interface UpdateableObjectProvider
 * Additional interface an Adapter can implement if it wishes to have different objects
 * updated than those returned from the Adapter::getObjectsOrderedById method.
 *
 * For example, some "destination" systems return read-only objects from their query methods.
 * By implementing this interface, a destination adapter may return these read-only objects
 * from the Adapter::getObjectsOrderedById() method. In the case that an object needs to
 * be updated, the Synchronizer/Indexer implementation will call the prepareUpdate() method,
 * pass in the read-only object and then use the returned object when calling the mapping function.
 *
 * Another advantage is that the objects being updated can be different from those contained
 * in the result set used internally by the destination system. This allows a more efficient
 * memory usage / GC, as the updated object (containing chunks of data that needs to be written
 * to the destination) can be pruned/GC'd after the update has been performed, while the
 * result set must be kept around until the entire process has finished.
 *
 * @package H69\ContentMapping\Adapter
 */
interface UpdateableObjectProvider
{
    /**
     * Create the object instance that can be used to update data in the target system.
     *
     * @param mixed $object A destination object as returned from getObjectsOrderedById()
     *
     * @return mixed The (possibly new) object that will be passed to the mapping function.
     */
    public function prepareUpdate($object);
}
