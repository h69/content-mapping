<?php
namespace H69\ContentMapping\Adapter;

/**
 * Interface IndexableObjectProvider
 * Additional interface an Adapter can implement if he is possible to handle an index queue.
 *
 * For example, some source systems return many big data objects that need too long for synchronization.
 * An additional table in the source system is queuing informations about changes that have been made.
 * These informations can be used in implementations of this interface to return only source changes with an
 * corresponding status information.
 *
 * @package H69\ContentMapping\Adapter
 */
interface IndexableObjectProvider
{
    const STATUS_NEW = 1;
    const STATUS_UPDATE = 2;
    const STATUS_DELETE = 3;

    /**
     * Get an Iterator over all $type objects in the source system that have been changed (add, updated, deleted)
     *
     * @param string $type Type of Objects to return
     *
     * @return \Iterator
     */
    public function getObjectsForIndexing($type);

    /**
     * Get the current status of an object (NEW, UPDATE or DELETE)
     *
     * @param mixed $object
     *
     * @return int
     */
    public function statusOf($object);
}
