<?php
    function modules_admin_aliases($args)
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