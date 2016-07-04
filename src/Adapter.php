<?php
/*
 * (c) webfactory GmbH <info@webfactory.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace H69\ContentMapping;

use Iterator;

interface Adapter
{
    const STATUS_NEW = 1;
    const STATUS_UPDATE = 2;
    const STATUS_DELETE = 3;

    /**
     * Get an Iterator over all $className objects in the destination system, ordered by their ascending IDs.
     *
     * @param string $type
     * @param string $indexQueue
     * @return Iterator
     */
    public function getObjectsOrderedById($type, $indexQueue = false);

    /**
     * Get the id of an object
     *
     * @param mixed $object
     * @return int
     */
    public function idOf($object);

    /**
     * Get the current status of an object (NEW, UPDATE or DELETE)
     *
     * @param mixed $object
     * @return int
     */
    public function statusOf($object);

    /**
     * Create a new object in the target system identified by ($id and $type).
     *
     * @param int $id
     * @param string $type
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
