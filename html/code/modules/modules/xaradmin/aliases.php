<?php
/**
 * @package modules
 * @subpackage modules module
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @link http://xaraya.com/index.php/release/1.html
 */

    function modules_admin_aliases($args)
    {
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
        return $data;
    }
?>