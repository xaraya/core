<?php
/**
 * @package modules
 * @copyright (C) 2002-2006 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage privileges
 * @link http://xaraya.com/index.php/release/.html
 * @author Marc Lutolf <mfl@netspan.ch>
 */

sys::import('structures.tree');
sys::import('modules.privileges.class.privileges');
/**
 * Handle Privileges Tree property
 */
class PrivilegesTreeProperty extends DataProperty
{
    public $id         = 30045;
    public $name       = 'privilegestree';
    public $desc       = 'PrivilegesTree';
    public $reqmodules = array('privileges');

    function __construct(ObjectDescriptor $descriptor)
    {
        parent::__construct($descriptor);

        if (!isset($allowtoggle)) $allowtoggle = 0;
        $this->tplmodule = 'privileges';
        $this->filepath   = 'modules/privileges/xarproperties';
        $this->privs = new xarPrivileges();
    }

    public function showInput(Array $data = array())
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
