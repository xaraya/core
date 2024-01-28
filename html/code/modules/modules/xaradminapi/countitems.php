<?php
/**
 * @package modules\modules
 * @subpackage modules
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://xaraya.info/index.php/release/1.html
 */
function modules_adminapi_countitems(Array $args=array())
{
    extract($args);
    
    // Set some defaults
    if (!isset($state)) $state = xarMod::STATE_ACTIVE;
    if (!isset($include_core)) $include_core = true;
        
    // Determine the tables we are going to use
    $tables = xarDB::getTables();
    $q = new Query('SELECT', $tables['modules']);
    
    if (!empty($regid)) $q->eq('regid', $regid);
    
    if (!empty($name)) {
        if (is_array($name)) {
            $q->in('name', $name);
        } else {             
            $q->eq('name', $name);
        }
    }
    
    if (!empty($systemid)) $q->eq('id', $systemid);

    if ($state != xarMod::STATE_ANY) {
        if ($state != xarMod::STATE_INSTALLED) {
            $q->eq('state', $state);
        } else {
            $q->ne('state', xarMod::STATE_UNINITIALISED);
            $q->lt('state', xarMod::STATE_MISSING_FROM_INACTIVE);
            $q->ne('state', xarMod::STATE_MISSING_FROM_UNINITIALISED);
        }    
    }
    
    if (!empty($modclass)) $q->eq('class', $modclass);
    if (!empty($category)) $q->eq('category', $category);
    
    if (!$include_core) {
        $coremods = array('base','roles','privileges','blocks','themes','authsystem','mail','dynamicdata','installer','modules','categories');
        $q->notin('name', $coremods);
    }

    if (!empty($user_capable)) $q->eq('user_capable', (int)$user_capable);
    if (!empty($admin_capable)) $q->eq('admin_capable', (int)$admin_capable);    
    
    $q->run();
    $result = $q->output();
    return count($result);
}
