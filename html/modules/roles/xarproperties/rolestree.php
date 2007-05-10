<?php
/**
 * @package modules
 * @copyright (C) 2002-2006 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage roles
 * @link http://xaraya.com/index.php/release/27.html
 * @author Marc Lutolf <mfl@netspan.ch>
 */

sys::import('xaraya.structures.tree');
sys::import('modules.roles.class.roles');

/**
 * Handle Roles Tree Property
 */
class RolesTreeProperty extends DataProperty
{
    public $id         = 30044;
    public $name       = 'rolestree';
    public $desc       = 'Roles Tree';
    public $reqmodules = array('roles');

     function __construct(ObjectDescriptor $descriptor)
    {
        parent::__construct($descriptor);

        $this->tplmodule = 'roles';
        $this->filepath   = 'modules/roles/xarproperties';
    }

    public function showInput(Array $data = array())
    {
        if (!isset($topuid)) $topuid = xarModVars::get('roles', 'everybody');
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
        $data = xarRoles::getgroups();
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
