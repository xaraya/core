<?php
/**
 * Interfaces for routes
 */

interface iController
{
    public function __construct(xarRequest $request=null);
    public function decode(Array $data=array());
    public function encode(xarRequest $request);
    public function getActionString(xarRequest $request);  
    public function getInitialPath(xarRequest $request);
}

?>