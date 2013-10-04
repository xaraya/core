<?php
/**
 * Online Block display interface
 *
 * @package modules
 * @subpackage roles module
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 * @link http://xaraya.com/index.php/release/27.html
 */

/**
 * Display block
 *
 * @author Jim McDonald
 * @author Greg Allan
 * @author John Cox
 * @author Michael Makushev
 * @author Marc Lutolf
 */
sys::import('modules.roles.xarblocks.online');
class Roles_OnlineBlockDisplay extends Roles_OnlineBlock
{
/**
 * Display method
 * @FIXME: this method inefficiently runs db queries, whether required for display or not
**/
    function display()
    {
        $data = $this->getContent();
        
        if (!isset($data['showusers']))     $data['showusers'] = true;
        if (!isset($data['showusertotal'])) $data['showusertotal'] = false;
        if (!isset($data['showanontotal'])) $data['showanontotal'] = false;
        if (!isset($data['showlastuser']))  $data['showlastuser'] = false;

        // Bail if there is nothing to show
        if (!$data['showusers'] && !$data['showusertotal'] && !$data['showanontotal'] && !$data['showlastuser']) return array('content' => '');

        // Database setup
        // TODO: do we need this query? I'd have thought userapi/getallactive gives
        // us everything we need.
        $dbconn = xarDB::getConn();
        $xartable =& xarDB::getTables();
        $sessioninfotable = $xartable['session_info'];
        $activetime = time() - (xarConfigVars::get(null, 'Site.Session.Duration') * 60);
        if($dbconn->databaseType == 'sqlite') {
            $sql = "SELECT COUNT(*)
                    FROM (SELECT DISTINCT role_id FROM $sessioninfotable
                    WHERE last_use > ? AND role_id > ?)";
        } else {
            $sql = "SELECT COUNT(DISTINCT role_id)
                    FROM $sessioninfotable
                    WHERE last_use > ? AND role_id > ?";
        }
        try {
            $result = $dbconn->Execute($sql, array($activetime,2));
            list($data['numusers']) = $result->fields;
            $result->Close();
            if (empty($data['numusers'])) $data['numusers'] = 0;
        } catch (Exception $e) {
            $data['numusers'] = 0;
        }

        // FIXME: there could be many active users, but we only want a handful of them.
        $activeusers = xarMod::apiFunc('roles', 'user', 'getallactive',
            array(
                'order' => 'name',
                'startnum' => 0,
                'include_anonymous' => false,
            )
        );

        foreach ($activeusers as $key => $thisuser) {
            $data['activeusers'][$key] = array(
                'name' => $thisuser['name'],
                'userurl' => xarModURL(
                    'roles', 'user', 'display',
                         array('id' => $thisuser['id'])
                ),
                'total' => '',
                'unread' => '',
                'messagesurl' => ''
            );

            if ($thisuser['name'] == xarUserGetVar('name')) {
                if (xarModIsAvailable('messages')) {
                    $data['activeusers'][$key]['total'] = xarMod::apiFunc(
                        'messages', 'user', 'count_total',
                        array('id'=>$thisuser['id'])
                    );

                    $data['activeusers'][$key]['unread'] = xarMod::apiFunc(
                        'messages', 'user', 'count_unread',
                        array('id'=>$thisuser['id'])
                    );

                    $data['activeusers'][$key]['messagesurl'] =xarModURL(
                        'messages', 'user', 'display',
                        array('id'=>$thisuser['id'])
                    );
                }
            }
        }


        if($dbconn->databaseType == 'sqlite') {
            $query2 = "SELECT COUNT(*)
                       FROM (SELECT DISTINCT ip_addr FROM $sessioninfotable
                             WHERE last_use > ? AND role_id = ?)";
        } else {
            $query2 = "SELECT COUNT(DISTINCT ip_addr)
                       FROM $sessioninfotable
                       WHERE last_use > ? AND role_id = ?";
        }
        try {
            $result2 = $dbconn->Execute($query2, array($activetime,2));
            list($data['numguests']) = $result2->fields;
            $result2->Close();
            if (empty($data['numguests'])) $data['numguests'] = 0;
        } catch (Exception $e) {
            $data['numguests'] = 0;
        }

        // Pluralise
        if ($data['numguests'] == 1) {
             $data['guests'] = xarML('guest');
        } else {
             $data['guests'] = xarML('guests');
        }

        if ($data['numusers'] == 1) {
             $data['users'] = xarML('user');
        } else {
             $data['users'] = xarML('users');
        }

        $id = xarModVars::get('roles', 'lastuser');

        // Make sure we have a lastuser
        if (!empty($id)) {
            if(!is_numeric($id)) {
            //Remove this further down the line
                $status = xarMod::apiFunc(
                'roles', 'user', 'get',
                array('uname' => $id)
                );

            } else {
                $status = xarMod::apiFunc(
                'roles', 'user', 'get',
                array('id' => $id)
                );

            }
            // Check return
            if ($status) {$data['lastuser'] = $status;}
        }

        return $data;
    }
}
?>