<?php

/**
 * deletegroup - delete a group & info
 * @param $args['uid']
 * @return true on success, false otherwise
 */
function roles_adminapi_clearsessions($spared)
{
    if(!isset($spared)) {
        $msg = xarML('Wrong arguments to groups_adminapi_clearsessions');
        xarExceptionSet(XAR_SYSTEM_EXCEPTION,
                    'BAD_PARAM',
                     new SystemException($msg));
        return false;
    }

    list($dbconn) = xarDBGetConn();
    $xartable = xarDBGetTables();
    $sessionstable = $xartable['session_info'];
    $roles = new xarRoles();

    $query = "SELECT xar_sessid, xar_uid FROM $sessionstable";
    $result = $dbconn->Execute($query);
    if (!$result) return;
    while (!$result->EOF) {
       list($thissession, $thisuid) = $result->fields;
       foreach ($spared as $uid) {
            $thisrole = $roles->getRole($thisuid);
            $thatrole = $roles->getRole($uid);
            if (!$thisuid == $uid && !$thisrole->isParent($thatrole)) {
                $query = "DELETE FROM $sessionstable
                  WHERE xar_sessid = '" . $thissession . "'";
                if (!$dbconn->Execute($query)) return;
                break;
            }
        }
       $result->MoveNext();
   }

// Security Check
    if(!xarSecurityCheck('EditRole')) return;


    return true;
}

?>