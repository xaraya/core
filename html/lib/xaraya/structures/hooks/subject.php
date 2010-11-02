<?php
/**
 * @package core
 * @subpackage hooks
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 * 
 * 
 */

sys::import('xaraya.structures.hooks.request');

class BasicSubject extends RequestObject implements SplSubject
{
    function attach(SplObserver $observer)  { }
    function detach(SplObserver $observer)  { }
    function notify() { }
}
class HookSubject extends BasicSubject
{
    private $messenger;

    function attach(SplObserver $observer, $callerItemType = '')
    {
        if (!xarModIsHooked($observer->getmodule(), $this->getmodule(), $callerItemType))
        xarMod::apiFunc('modules','admin','enablehooks',array('callerModName' => $this->getmodule(), 'hookModName' => $observer->getmodule(), 'callerItemType' => $callerItemType));
    }

    function detach(SplObserver $observer, $callerItemType = '')
    {
        xarMod::apiFunc('modules','admin','disablehooks',array('callerModName' => $this->getmodule(), 'hookModName' => $observer->getmodule(), 'callerItemType' => $callerItemType));
    }

    function getMessenger($itemid=0, $extrainfo=array())
    {
        sys::import('xaraya.structures.hooks.messenger');
        $this->messenger = new HookMessenger($this->module, $this->itemtype, $itemid, $extrainfo);
        return $this->messenger;
    }

    function notify()
    {
        if (empty($this->module)) $this->module = null;
        if ($this->itemtype == 'All') $this->itemtype = '';

        return xarModCallHooks($this->messenger->gethookObject(), $this->messenger->gethookAction(), $this->messenger->getitemid(), $this->messenger->getextraInfo(), $this->module, $this->itemtype);
    }
    function getHooklist()
    {
        if ($this->itemtype == "All") $this->itemtype = '';
        return xarModGetHookList($this->module, $this->messenger->gethookObject(), $this->messenger->gethookAction(), $this->itemtype);
    }
    function isHooked($hookModName)
    {
        if ($this->itemtype == "All") $this->itemtype = '';
        return xarModIsHooked($hookModName, $this->module, $this->itemtype);
    }

}
?>
