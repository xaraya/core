<?php
/**
 * Categories Module
 * Modify one or more categories
 *
 * @package modules\categories
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.info
 * @link http://xaraya.info/index.php/release/147.html
 *
 * @author Marc Lutolf <mfl@netspan.ch>
 */

/**
 * Function to modify category
 * 
 * @param void N/A
 * @return array|null Returns display data array on success, null on failure
 */
function categories_admin_clone()
{
    if (!xarVarFetch('return_url',  'isset',  $data['return_url'], NULL, XARVAR_DONT_SET)) {return;}
    if (!xarVarFetch('itemid',      'int',    $data['itemid'], 0, XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('confirm',     'str:1:', $confirm,'',XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('newname',     'str:1:', $newname,   "", XARVAR_NOT_REQUIRED)) {return;}
    
    // Support old cids for now
    if (!xarVarFetch('cid','int::', $cid, NULL, XARVAR_DONT_SET)) {return;}
    $data['itemid'] = !empty($data['itemid']) ? $data['itemid'] : $cid;

    // Security check
    if(!xarSecurityCheck('AddCategories',1,'All',"All:$cid")) return;

    // Setting up necessary data.
    sys::import('modules.dynamicdata.class.objects.master');
    $data['object'] = DataObjectMaster::getObject(array('name' => xarModVars::get('categories','categoriesobject')));
    $data['object']->getItem(array('itemid' => $data['itemid']));

    if ($confirm) {
        $access = xarSecurityCheck('',0,'All',"All:" . $data['object']->name . ":" . "All",0,'',0,700);

        if (!$access)
            return xarTplModule('privileges','user','errors', array('layout' => 'no_privileges'));

        $data['name'] = $data['object']->properties['name']->value;
        if(!xarVarFetch('newname',   'str', $newname,   "", XARVAR_NOT_REQUIRED)) {return;}
        if (empty($newname)) $newname = $data['name'] . "_copy";
        if ($newname == $data['name']) $newname = $data['name'] . "_copy";
        $newname = str_ireplace(" ", "_", $newname);
        
        sys::import('modules.categories.class.worker');
        $worker = new CategoryWorker();
        $toplevel = $worker->appendTree($data['itemid']);

        // Change the name of the top level category we added
        $data['object']->updateItem(array('itemid' => $toplevel, 'name' => $newname));

        xarController::redirect(xarModURL('categories','admin','view'));
        return true;
    }  
    return $data;
}
?>