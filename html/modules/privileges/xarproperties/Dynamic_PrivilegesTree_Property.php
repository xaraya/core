<?php
/**
 *
 * PrivilegesTree Property
 *
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2006 by to be added
 * @license GPL <http://www.gnu.org/licenses/gpl.html>
 * @link to be added
 * @subpackage Privileges Module
 * @author Marc Lutolf <mfl@netspan.ch>
 *
 */

sys::import('structures.tree');
sys::import('modules.privileges.xarprivileges');

class Dynamic_PrivilegesTree_Property extends Dynamic_Property
{
    public $id         = 30045;
    public $name       = 'privilegestree';
    public $desc       = 'PrivilegesTree';
    public $reqmodules = array('privileges');

    function __construct($args)
    {
        parent::__construct($args);

        if (!isset($allowtoggle)) $allowtoggle = 0;
        $this->tplmodule = 'privileges';
        $this->filepath   = 'modules/privileges/xarproperties';
        $this->privs = new xarPrivileges();
    }

    function showInput($data = array())
    {
        if (!isset($data['show'])) $data['show'] = 'assigned';
        $trees = array();
        foreach ($this->privs->gettoplevelprivileges($data['show']) as $entry) {
            $node = new TreeNode($entry['pid']);
            $tree = new PrivilegesTree($node);
            $trees[] = $node->depthfirstenumeration();
        }
        $data['trees'] = $trees;
        return parent::showInput($data);
    }

}
// ---------------------------------------------------------------
class PrivilegesTree extends Tree
{
    function createnodes(TreeNode $node)
    {
        $r = new xarPrivileges();
        $data = $r->getprivileges();
         foreach ($data as $row) {
            $nodedata = array(
                'id' => $row['pid'],
                'parent' => $row['parentid'],
                'name' => $row['name'],
                'realm' => $row['realm'],
                'module' => $row['module'],
                'component' => $row['component'],
                'instance' => $row['instance'],
                'level' => $row['level'],
                'description' => $row['description'],
            );
            $this->treedata[] = $nodedata;
        }
        parent::createnodes($node);
    }
}

?>
