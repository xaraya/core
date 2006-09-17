<?php
/**
 *
 * RolesTree Property
 *
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2006 by to be added
 * @license GPL <http://www.gnu.org/licenses/gpl.html>
 * @link to be added
 * @subpackage Roles Module
 * @author Marc Lutolf <mfl@netspan.ch>
 *
 */

sys::import('structures.tree');
sys::import('modules.roles.xarroles');

class Dynamic_RolesTree_Property extends Dynamic_Property
{
     function __construct($args)
    {
        parent::__construct($args);

        $this->template = $this->getTemplate();
        $this->tplmodule = 'roles';
        $this->filepath   = 'modules/roles/xarproperties';
    }

    static function getRegistrationInfo()
    {
        $info = new PropertyRegistration();
        $info->reqmodules = array('roles');
        $info->id   = 30044;
        $info->name = 'rolestree';
        $info->desc = 'Dynamic Roles Tree';
        return $info;
    }

    function showInput($data = array())
    {
        if (!isset($topuid)) $topuid = xarModGetVar('roles', 'everybody');
        $node = new TreeNode($topuid);
        $tree = new RolesTree($node);
        $data['nodes'] = $node->depthfirstenumeration();
        return parent::showInput($data);
    }
}
// ---------------------------------------------------------------
class RolesTree extends Tree
{
    function createnodes(TreeNode $node)
    {
        $r = new xarRoles();
        $data = $r->getgroups();
         foreach ($data as $row) {
            $nodedata = array(
                'id' => $row['uid'],
                'parent' => $row['parentid'],
                'name' => $row['name'],
                'users' => $row['users'],
            );
            $this->treedata[] = $nodedata;
        }
        parent::createnodes($node);
    }
}
?>
