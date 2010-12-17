<?php
/**
 * Show configuration of some theme
 * @package modules
 * @subpackage themes module
 * @category Xaraya Web Applications Framework
 * @version 2.2.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 * @link http://xaraya.com/index.php/release/71.html
 *
 * @author Marc Lutolf <mfl@netspan.ch>
 */
/**
 * Show configuration of some theme
 * @return array data for the template display
 */
function themes_admin_showthemeconfig(Array $args=array())
{
    extract($args);

    if (!xarVarFetch('id',  'id',    $themeid, NULL, XARVAR_NOT_REQUIRED)) {return;}
    if (!xarVarFetch('exit', 'isset', $exit, NULL, XARVAR_DONT_SET)) {return;}
    if (!xarVarFetch('confirm', 'isset', $confirm, NULL, XARVAR_DONT_SET)) {return;}
    if (!xarVarFetch('preview', 'isset', $preview, NULL, XARVAR_DONT_SET)) {return;}

    if (empty($themeid)) return xarResponse::NotFound();

    // get the theme object corresponding to this theme
    sys::import('modules.dynamicdata.class.objects.master');
    $myobject = & DataObjectMaster::getObject(array('name'   => 'themes',
                                                    'itemid' => $themeid));
    if (empty($myobject)) return;

    $newid = $myobject->getItem();
    
    $data['theme'] = $myobject;
    $data['properties'] = $myobject->properties;

    return $data;
}

?>
