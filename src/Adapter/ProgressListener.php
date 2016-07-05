<?php
namespace H69\ContentMapping\Adapter;

/**
 * Interface ProgressListener
 * When an Adapter implementation also implements this interface,
 * it will be notified after every step the Synchronizer/Indexer made.
 *
 * @package H69\ContentMapping\Adapter
 */
interface ProgressListener
{
    /**
     * Callback method that will be called after every single object has been processed.
     *
     * @return void
     */
    public function afterObjectProcessed();
}
