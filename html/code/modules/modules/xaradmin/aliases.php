<?php
/**
 * @package modules
 * @subpackage modules module
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 * @link http://xaraya.com/index.php/release/1.html
 */
/**
 * @return array data for the template display
 */

    function modules_admin_aliases(Array $args=array())
    {
    // Security
    if (!xarSecurityCheck('AdminModules')) return; 
    
        if (!xarVarFetch('name',   'str', $modname,     NULL, XARVAR_NOT_REQUIRED)) {return;}
        if (!xarVarFetch('remove', 'str', $removealias, NULL, XARVAR_NOT_REQUIRED)) {return;}
        if (!xarVarFetch('add',    'str', $addalias,    NULL, XARVAR_NOT_REQUIRED)) {return;}
        if (!empty($removealias) && !empty($modname)) {
            xarModAlias::delete($removealias, $modname);
        } elseif (!empty($addalias) && !empty($modname)) {
            xarModAlias::set($addalias, $modname);
        }
        $data['modname'] = $modname;
        $data['aliasesMap'] = xarConfigVars::get(null,'System.ModuleAliases');
        ksort($data['aliasesMap']);
        return $data;
    }
?>
