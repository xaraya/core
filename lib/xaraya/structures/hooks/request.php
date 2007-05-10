<?php

class RequestObject extends Object
{
    protected $module;
    protected $type;
    protected $function;

    function __construct($module='base', $type='user', $function='main', $itemtype='All')
    {
        $this->module = $module;
        $this->type = $type;
        $this->function = $function;
        $this->itemtype = $itemtype;
    }

    function getmodule()
    { return $this->module; }

    function getitemtype()
    { return $this->itemtype; }

    function gettype()
    { return $this->type; }

    function getfunction()
    { return $this->function; }

    function register($hookObject='module', $hookAction='', $hookArea='API')
    {
        if (empty($hookAction)) return true;

        // Check if there's already a hook registered
        $dbconn   = xarDB::getConn();
        $xartable = xarDB::getTables();
        $hookstable = $xartable['hooks'];
        $tmodInfo = xarMod::getBaseInfo($this->getmodule());
        $tmodId = $tmodInfo['systemid'];
        $query = "SELECT COUNT(*) FROM " . $hookstable .
                 " WHERE object = ? AND action = ? AND
                   t_area = ? AND  t_module_id = ? AND t_type = ? AND t_func = ?";
        $bindvars = array($hookObject,$hookAction,$hookArea,$tmodId,$this->gettype(),$this->getfunction());
        $stmt = $dbconn->prepareStatement($query);
        $result = $stmt->executeQuery($bindvars);
        if (!$result->first()) return;
        $numitems = $result->getInt(1);
        $result->close();

        if (empty($numitems))
            xarModRegisterHook($hookObject, $hookAction, $hookArea, $this->getmodule(), $this->gettype(), $this->getfunction());
    }
    function unregister($hookObject='module', $hookAction='', $hookArea='API')
    {
        if (empty($hookAction)) return true;
        xarModUnregisterHook($hookObject, $hookAction, $hookArea, $this->getmodule(), $this->gettype(), $this->getfunction());
    }
}
?>
