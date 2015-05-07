<?php

namespace Webfactory\ContentMapping;

use \Iterator;
use Psr\Log\LoggerInterface;

/**
 * Quell-System (z.B. Anbindung an eine Datenbank) für Objekte, die in ein Ziel-System (z.B. einen Solr-Index)
 * abgebildet werden sollen.
 */
interface Source
{
    /**
     * Hole String, der den Content-Typ identifiziert.
     *
     * @return string
     */
    public function getObjectClass();

    /**
     * Hole einen Iterator, der \Webfactory\ContentMapping\Mappable liefert, deren IDs jeweils eindeutig im Kontext
     * einer ObjectClass sind, und die nach ihrer ID aufsteigend sortiert sind.
     *
     * Zielsysteme (\Webfactory\ContentMapping\Destination) können weitere Anforderungen an die vom Iterator gelieferten
     * Objekte stellen (z.B. speziellere Interfaces wie \Webfactory\ContentMapping\Solr\Mappable).
     *
     * @return Iterator
     */
    public function getObjectIterator();

    /**
     * @param LoggerInterface $logger
     */
    public function setLogger(LoggerInterface $logger);
}
