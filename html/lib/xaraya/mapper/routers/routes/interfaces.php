<?php
/**
 * Interfaces for routes
 */

interface iRoute
{
    public function __construct(Array $defaults=array(), xarDispatcher $dispatcher=null);
}

?>