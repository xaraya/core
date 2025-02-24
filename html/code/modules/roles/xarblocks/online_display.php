<?php
/**
 * Online Block display interface
 *
 * @package modules\roles
 * @subpackage roles
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://xaraya.info/index.php/release/27.html
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
	 * @todo this method inefficiently runs db queries, whether required for display or not
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
        $dbconn = $this->db()->getConn();
        $xartable = $this->db()->getTables();
        $sessioninfotable = $xartable['session_info'];
        $activetime = time() - ($this->config()->getVar('Site.Session.Duration') * 60);
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
        $activeusers = $this->mod()->apiFunc('roles', 'user', 'getallactive',
            array(
                'order' => 'name',
                'startnum' => 0,
                'include_anonymous' => false,
            )
        );

        foreach ($activeusers as $key => $thisuser) {
            $data['activeusers'][$key] = array(
                'name' => $thisuser['name'],
                'userurl' => $this->ctl()->getModuleURL(
                    'roles', 'user', 'display',
                         array('id' => $thisuser['id'])
                ),
                'total' => '',
                'unread' => '',
                'messagesurl' => ''
            );

            if ($thisuser['name'] == xarUser::getVar('name')) {
                if ($this->mod()->isAvailable('messages')) {
                    $data['activeusers'][$key]['total'] = $this->mod()->apiFunc(
                        'messages', 'user', 'count_total',
                        array('id'=>$thisuser['id'])
                    );

                    $data['activeusers'][$key]['unread'] = $this->mod()->apiFunc(
                        'messages', 'user', 'count_unread',
                        array('id'=>$thisuser['id'])
                    );

                    $data['activeusers'][$key]['messagesurl'] =$this->ctl()->getModuleURL(
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
             $data['guests'] = $this->ml('guest');
        } else {
             $data['guests'] = $this->ml('guests');
        }

        if ($data['numusers'] == 1) {
             $data['users'] = $this->ml('user');
        } else {
             $data['users'] = $this->ml('users');
        }

        $id = $this->mod('roles')->getVar('lastuser');

        // Make sure we have a lastuser
        if (!empty($id)) {
            if(!is_numeric($id)) {
            //Remove this further down the line
                $status = $this->mod()->apiFunc(
                'roles', 'user', 'get',
                array('uname' => $id)
                );

            } else {
                $status = $this->mod()->apiFunc(
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
