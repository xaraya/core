<?php

/**
 * deleteuser - delete a user from a group
 * @param $args['gid'] group id
 * @param $args['uid'] user id
 * @return true on success, false on failure
 */
function roles_adminapi_deleteuser($args)
{
    extract($args);

    if((!isset($gid)) && (!isset($uid))) {
        $msg = xarML('roles_adminapi_deleteuser');
        xarExceptionSet(XAR_SYSTEM_EXCEPTION,
                    'BAD_PARAM',
                     new SystemException($msg));
        return false;
    }

// Security Check
	if(!xarSecurityCheck('DeleteRole')) return;

    $roles = new xarRoles();
    $group = $roles->getRole($gid);
    if($group->isUser()) {
        $msg = xarML('Did not find a group');
        xarExceptionSet(XAR_SYSTEM_EXCEPTION,
                    'BAD_PARAM',
                     new SystemException($msg));
        return false;
    }

    $user = $roles->getRole($uid);

    if(count($user->getParents() == 1)) {
        $msg = xarML('The user only has one parent group - cannot remove');
        xarExceptionSet(XAR_SYSTEM_EXCEPTION,
                    'BAD_PARAM',
                     new SystemException($msg));
        return false;
    }

    return $group->removeMember($user);
}

?>