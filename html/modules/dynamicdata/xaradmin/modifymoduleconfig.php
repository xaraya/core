<?php

function dynamicdata_admin_modifymoduleconfig()
{
    if (!xarSecurityCheck('AdminDynamicData')) return;
    if (!xarVarFetch('phase', 'str:1:100', $phase, 'modify', XARVAR_NOT_REQUIRED, XARVAR_PREP_FOR_DISPLAY)) return;

    switch (strtolower($phase)) {
        case 'modify':
        default:

            break;

        case 'update':
            // Confirm authorisation code
            if (!xarSecConfirmAuthKey()) return;
            if (!xarVarFetch('itemsperpage', 'int',      $itemsperpage, xarModVars::get('dynamicdata', 'itemsperpage'), XARVAR_NOT_REQUIRED, XARVAR_PREP_FOR_DISPLAY)) return;
            if (!xarVarFetch('shorturls',    'checkbox', $shorturls, false, XARVAR_NOT_REQUIRED)) return;
            if (!xarVarFetch('modulealias',  'checkbox', $useModuleAlias,  xarModVars::get('dynamicdata', 'useModuleAlias'), XARVAR_NOT_REQUIRED)) return;
            if (!xarVarFetch('aliasname',    'str',      $aliasname,  xarModVars::get('dynamicdata', 'aliasname'), XARVAR_NOT_REQUIRED)) return;
            if (!xarVarFetch('debugmode',    'checkbox', $debugmode, xarModVars::get('dynamicdata', 'debugmode'), XARVAR_NOT_REQUIRED)) return;
            if (!xarVarFetch('administrators', 'str', $administrators, '', XARVAR_NOT_REQUIRED)) return;

            $admins = explode(',',$administrators);
            $validadmins = array();
            foreach ($admins as $admin) {
                if (empty($admin)) continue;
                $user = xarModAPIFunc('roles','user','get',array('uname' => trim($admin)));
                if(!empty($user)) $validadmins[$user['uname']] = $user['uname'];
            }
            xarModVars::set('dynamicdata', 'administrators', serialize($validadmins));
            xarModVars::set('dynamicdata', 'itemsperpage', $itemsperpage);
            xarModVars::set('dynamicdata', 'supportshorturls', $shorturls);
            xarModVars::set('dynamicdata', 'useModuleAlias', $useModuleAlias);
            xarModVars::set('dynamicdata', 'aliasname', $aliasname);
            xarModVars::set('dynamicdata', 'debugmode', $debugmode);

            // Get the users to be shown the debug messages
            if (!xarVarFetch('debugusers', 'str', $candidates, '', XARVAR_NOT_REQUIRED)) return;
            if (empty($candidates)) {
                $candidates = array();
            } else {
                $candidates = explode(',',$candidates);
            }
            $newusers = array();
            foreach ($candidates as $candidate) {
                $user = xarModAPIFunc('roles','user','get',array('uname' => trim($candidate)));
                if(!empty($user)) $newusers[$user['uname']] = array('id' => $user['id']);
            }
            xarModVars::set('dynamicdata', 'debugusers', serialize($newusers));

            xarResponseRedirect(xarModURL('dynamicdata', 'admin', 'modifymoduleconfig'));
            return true;
            break;

    }
    $data['authid'] = xarSecGenAuthKey();
    return $data;
}
?>
