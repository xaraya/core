<?php

/* Include the base class */

/**
 * The RolesTree property displays groups and users in a tree format
 *
 * @package modules\roles
 * @subpackage roles
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://xaraya.info/index.php/release/27.html
 * @author Marc Lutolf <mfl@netspan.ch>
 */
class RolesTreeProperty extends DataProperty
{
    public $id         = 30044;
    public $name       = 'rolestree';
    public $desc       = 'Roles Tree';
    public $reqmodules = ['roles'];

    public function __construct(ObjectDescriptor $descriptor)
    {
        parent::__construct($descriptor);

        $this->tplmodule = 'roles';
        $this->filepath   = 'modules/roles/xarproperties';
    }

    /**
     * Display the property for input
     *
     * @param array<string, mixed> $data An array of input parameters
     * @return string     HTML markup to display the property for input on a web page
     */
    public function showInput(array $data = [])
    {
        if (!isset($data['topid'])) {
            $data['topid'] = (int) $this->mod()->getVar('everybody');
        }
        $node = new TreeNode($data['topid']);
        $tree = new RolesTree($node);
        $data['nodes'] = $node->depthfirstenumeration();
        return parent::showInput($data);
    }
}

/* Include the base class */

/**
 * The RolesTree class models a tree structure of Xaraya users and groups
 *
 * @package modules\roles
 * @subpackage roles
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://xaraya.info/index.php/release/27.html
 * @author Marc Lutolf <mfl@netspan.ch>
 */
class RolesTree extends Tree
{
    /**
    *  Create nodes for a tree format
    *
    * @param  TreeNode data An array of input parameters
    */
    public function createnodes(TreeNode $node)
    {
        $data = xarRoles::getgroups();
        foreach ($data as $row) {
            $nodedata = [
                'id' => $row['id'],
                'parent' => $row['parentid'],
                'name' => $row['name'],
                'users' => $row['users'],
            ];
            $this->treedata[] = $nodedata;
        }
        parent::createnodes($node);
    }
}
