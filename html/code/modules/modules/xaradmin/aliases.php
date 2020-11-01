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
/**
 * @return array data for the template display
 */

    function modules_admin_aliases(Array $args=array())
    {
    // Security
    if (!xarSecurity::check('AdminModules')) return; 
    
        if (!xarVar::fetch('name',   'str', $modname,     NULL, xarVar::NOT_REQUIRED)) {return;}
        if (!xarVar::fetch('remove', 'str', $removealias, NULL, xarVar::NOT_REQUIRED)) {return;}
        if (!xarVar::fetch('add',    'str', $addalias,    NULL, xarVar::NOT_REQUIRED)) {return;}
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
