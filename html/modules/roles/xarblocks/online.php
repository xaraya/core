<?php
/**
 * Online Block
 *
 * @package modules
 * @copyright (C) 2002-2006 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Roles module
 * @link http://xaraya.com/index.php/release/27.html
 */

/**
 * Online Block
 * @author Jim McDonald, Greg Allan, John Cox, Michael Makushev, Marc Lutolf
 */
    sys::import('xaraya.structures.containers.blocks.basicblock');

    class OnlineBlock extends BasicBlock
    {
        public $name                = 'OnlineBlock';
        public $module              = 'roles';
        public $text_type           = 'Online';
        public $text_type_long      = 'Display who is online';
        public $allow_multiple      = true;
        public $show_preview        = true;


        function display(Array $data=array())
        {
            $data = parent::display($data);
            if (!xarSecurityCheck('ViewRoles',0,'Block',"online:$data[title]:$data[bid]")) {return;}

            // Get variables from content block
            if (!is_array($data['content'])) {
                $args = unserialize($data['content']);
            } else {
                $args = $data['content'];
            }
            if (!isset($args['showusers']))     $args['showusers'] = 0;
            if (!isset($args['showusertotal'])) $args['showusertotal'] = 0;
            if (!isset($args['showanontotal'])) $args['showanontotal'] = 0;
            if (!isset($args['showlastuser']))  $args['showlastuser'] = 0;

            // Database setup
            // TODO: do we need this query? I'd have thought userapi/getallactive gives
            // us everything we need.
            $dbconn = xarDB::getConn();
            $xartable = xarDB::getTables();
            $sessioninfotable = $xartable['session_info'];
            $activetime = time() - (xarConfigVars::get(null, 'Site.Session.Duration') * 60);
            if($dbconn->databaseType == 'sqlite') {
                $sql = "SELECT COUNT(*)
                        FROM (SELECT DISTINCT toles_id FROM $sessioninfotable
                              WHERE last_use > ? AND role_id > ?)";
            } else {
                $sql = "SELECT COUNT(DISTINCT role_id)
                    FROM $sessioninfotable
                    WHERE last_use > ? AND role_id > ?";
            }
            $result = $dbconn->Execute($sql, array($activetime,2));
            // CHECKME: do we catch the exception here?
            list($args['numusers']) = $result->fields;
            $result->Close();
            if (empty($args['numusers'])) {
                $args['numusers'] = 0;
            }

            // FIXME: there could be many active users, but we only want a handful of them.
            $activeusers = xarModAPIFunc(
                'roles', 'user', 'getallactive',
                array(
                    'order' => 'name',
                    'startnum' => 0,
                    'include_anonymous' => false,
                    'include_myself' => false
                )
            );

            foreach ($activeusers as $key => $thisuser) {
                $args['activeusers'][$key] = array(
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
                        $args['activeusers'][$key]['total'] = xarModAPIFunc(
                            'messages', 'user', 'count_total',
                            array('id'=>$thisuser['id'])
                        );

                        $args['activeusers'][$key]['unread'] = xarModAPIFunc(
                            'messages', 'user', 'count_unread',
                            array('id'=>$thisuser['id'])
                        );

                        $args['activeusers'][$key]['messagesurl'] =xarModURL(
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
            $result2 = $dbconn->Execute($query2, array($activetime,2));
            // CHECKME: do we catch the exception here?
            list($args['numguests']) = $result2->fields;
            $result2->Close();
            if (empty($args['numguests'])) {
                $args['numguests'] = 0;
            }

            // Pluralise
            if ($args['numguests'] == 1) {
                 $args['guests'] = xarML('guest');
            } else {
                 $args['guests'] = xarML('guests');
            }

            if ($args['numusers'] == 1) {
                 $args['users'] = xarML('user');
            } else {
                 $args['users'] = xarML('users');
            }

            $id = xarModVars::get('roles', 'lastuser');

            // Make sure we have a lastuser
            if (!empty($id)) {
                if(!is_numeric($id)) {
                //Remove this further down the line
                    $status = xarModAPIFunc(
                    'roles', 'user', 'get',
                    array('uname' => $id)
                    );

                } else {
                    $status = xarModAPIFunc(
                    'roles', 'user', 'get',
                    array('id' => $id)
                    );

                }
                 // Check return
                 if ($status) {$args['lastuser'] = $status;}
            }

            $args['blockid'] = $data['bid'];
            $data['content'] = $args;
            return $data;
        }

        function modify(Array $data=array())
        {
            $data = parent::modify($data);
            if (!isset($data['showusers']))     $data['showusers'] = 0;
            if (!isset($data['showusertotal'])) $data['showusertotal'] = 0;
            if (!isset($data['showanontotal'])) $data['showanontotal'] = 0;
            if (!isset($data['showlastuser']))  $data['showlastuser'] = 0;
            return $data;
        }

        public function update(Array $data=array())
        {
            $data = parent::update($data);
            if (!xarVarFetch('showusers',     'checkbox', $args['showusers'], false, XARVAR_NOT_REQUIRED)) return;
            if (!xarVarFetch('showusertotal', 'checkbox', $args['showusertotal'], false, XARVAR_NOT_REQUIRED)) return;
            if (!xarVarFetch('showanontotal', 'checkbox', $args['showanontotal'], false, XARVAR_NOT_REQUIRED)) return;
            if (!xarVarFetch('showlastuser',  'checkbox', $args['showlastuser'], false, XARVAR_NOT_REQUIRED)) return;
            $data['content'] = $args;            
            return $data;
        }
    }

?>
