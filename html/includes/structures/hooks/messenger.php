<?php

class HookMessenger extends Object
{
    private $module;
    private $itemtype;
    private $itemid;
    private $hookObject;
    private $hookAction;

    function __construct($module='base', $itemtype='All', $itemid=0)
    {
        $this->module = $module;
        $this->itemtype = $itemtype;
        $this->itemid = $itemid;
    }
    function setHook($hookObject='module', $hookAction='')
    {
        $this->hookObject = $hookObject;
        $this->hookAction = $hookAction;
    }
    function getmodule() 
    { return $this->module; }
    
    function getitemtype() 
    { return $this->itemtype; }
    
    function getitemid() 
    { return $this->itemid; }
    
    function gethookObject() 
    { return $this->hookObject; }
    
    function gethookAction() 
    { return $this->hookAction; }
}
?>
