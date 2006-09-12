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

sys::import('modules.base.xarproperties.Dynamic_Tree_Property');
sys::import('modules.roles.xarroles');

class Dynamic_RolesTree_Property extends Dynamic_Tree_Property
{
    public $roles;
    public $treenode;
    public $treeitems;
    public $levels = 0;

    // some variables we'll need to hold drawing info
    public $html;
    public $nodeindex;
    public $indent;
    public $level;
    public $isbranch;
    public $drawchildren;
    public $alreadydone;

    // convenience variables to hold strings referring to pictures
    public $expandedbox;
    public $collapsedbox;
    public $L;
    public $T;
    public $I;
    public $B;
    public $emptybox;
    public $bigblank;
    public $smallblank;

    function __construct($args)
    {
        parent::__construct($args);

        extract($args);
        if (!isset($allowtoggle)) $allowtoggle = 0;
        $this->tplmodule = 'roles';
        $this->filepath   = 'modules/roles/xarproperties';
        $this->options = array();

        $this->smallblank = xarTplObject('roles', 'spacer', 'small');
        $this->L = xarTplObject('roles', 'L', 'drawing');
        $this->T = xarTplObject('roles', 'T', 'drawing');
        $this->I = xarTplObject('roles', 'I', 'drawing');
        $this->B = xarTplObject('roles', 'B', 'drawing');
        $this->emptybox = xarTplObject('roles', 'emptybox', 'drawing');

        $data['onclick'] = $allowtoggle ? "toggleBranch(this,this.parentNode.lastChild)" : "";
        $this->expandedbox  = xarTplObject('roles', 'expandedbox', 'drawing', $data);
        $this->collapsedbox = xarTplObject('roles', 'collapsedbox', 'drawing', $data);

        $this->roles = new xarRoles();
        $this->setitem(1, "deleteitem");
        $this->setitem(2, "leafitem");
        $this->setitem(3, "emailitem");
        $this->setitem(4, "privilegesitem");
        $this->setitem(5, "testitem");
        $this->setitem(6, "treeitem");
        $this->setitem(7, "descriptionitem");

    }

    static function getRegistrationInfo()
    {
        $info = new PropertyRegistration();
        $info->reqmodules = array('roles');
        $info->id   = 30045;
        $info->name = 'rolestree';
        $info->desc = 'Dynamic Roles Tree';
        return $info;
    }

    function showInput($data = array())
    {
        if (isset($data['options'])) $this->options = $data['options'];

        $this->maketree();
        $data['roletree'] = $this->drawtree($this->tree);
        $data['treenode'] = array($this->tree);

        return parent::showInput($data);
    }

// ---------------------------------------------------------------
    protected function maketree($args=array())
    {
        if (!isset($topuid)) $topuid = xarModGetVar('roles', 'everybody');
        $args['initialnode'] = array(
                    'parent' => $this->roles->getgroup($topuid),
                    'level' => 1
                    );
        parent::maketree($args);
    }

    protected function addbranches($node)
    {
        $object = $node['parent'];
        $level = $node['level'];
        $node['children'] = array();
        if ($level == $this->levels) return $node;
        foreach($this->roles->getsubgroups($object['uid']) as $subnode) {
            $nextnode = array(
                        'parent' => $subnode,
                        'level' => $level + 1
                        );
            $node['children'][] = $this->addbranches($nextnode);
        }
        return $node;
    }
    private function drawtree($tree='')
    {
        if ($tree == '') $tree = $this->tree;
        if ($tree == '') {
            throw new EmptyParameterException(null,'A tree must be defined before attempting to display.');
        }
        $this->nodeindex = 0;
        $this->indent = array();
        $this->level = 0;
        $this->alreadydone = array();
        $data['content'] = $this->drawbranch($tree);
        return xarTplObject('roles', 'tree', 'drawing',$data);
    }
    function setitem($pos=1,$item ='')
    {
        $this->treeitems[$pos] =& $item;
    }

    function clearitems()
    {
        $this->treeitems = array();
    }
    private function drawbranch($node)
    {
        $this->level ++;
        $this->nodeindex = $this->nodeindex + 1;
        $object = $node['parent'];
        $this->treenode = $object;
        // check if we've aleady processed this entry
        if (in_array($object['uid'], $this->alreadydone)) {
            $this->drawchildren = false;
            $node['children'] = array();
        } else {
            $this->drawchildren = true;
            $this->alreadydone[] = $object['uid'];
        }
        // is this a branch?
        $this->isbranch = count($node['children']) > 0 ? true : false;
        // now begin adding rows to the string


//-------------------- Assemble the data for a single row
        $html = "";
        for ($i=1,$max = count($this->treeitems); $i <= $max; $i++) {
            $func = $this->treeitems[$i];
            $html .= $this->{$func}();
        }

//-------------------- We've finished this row; now do the children of this role
        $ind = 0;
        foreach($node['children'] as $subnode) {
            $ind = $ind + 1;
            // if this is the last child, get ready to draw an "L", otherwise a sideways "T"
            if ($ind == count($node['children'])) {
                $this->indent[] = $this->L;
            } else {
                $this->indent[] = $this->T;
            }
            // draw this child
            $html .= $this->drawbranch($subnode);
            // we're done; remove the indent string
            array_pop($this->indent);
        }
        $this->level --;

//-------------------- Put everything in the container
            $data['nodeindex'] = $this->nodeindex;
            $data['content'] = $html;
            if ($this->isbranch) {
                $data['type'] = "branch";
            } else {
                $data['type'] = "leaf";
            }
        return xarTplObject('roles', 'container', 'drawing',$data);
    }
    /**
     * Functions that define the items in each row of the display
     */

    function leafitem()
    {
        if ($this->treenode['users'] == 0 || (!$this->drawchildren)) {
            return xarTplObject('roles', 'spacer', 'large');
        } else {
            $data['leafitemurl'] = xarModURL('roles', 'admin', 'showusers',
                            array('uid' => $this->treenode['uid'], 'reload' => 1));
            $data['leafitemtitle'] = xarML('Show the Users in this Group');
            $data['leafitemimage'] = xarTplGetImage('users.png');
            return xarTplObject('roles', 'leaf', 'showuser', $data);
        }
    }

    function deleteitem()
    {
        if (!xarSecurityCheck('DeleteRole',0,'Roles',$this->treenode['name']) || ($this->treenode['users'] > 0) || (!$this->drawchildren)) {
            return xarTplObject('roles', 'spacer', 'large');
        } else {
            $data['leafitemurl'] = xarModURL('roles', 'admin', 'deleterole',
                            array('uid' => $this->treenode['uid']));
            $data['leafitemtitle'] = xarML('Delete this Group');
            $data['leafitemimage'] = xarTplGetImage('delete.png');
            return xarTplObject('roles', 'leaf', 'deleteuser', $data);
        }
    }

    function emailitem()
    {
        if ($this->treenode['users'] == 0 || (!$this->drawchildren)) {
            return xarTplObject('roles', 'spacer', 'large');
        } else {
            $data['leafitemurl'] = xarModURL('roles', 'admin', 'createmail',
                            array('uid' => $this->treenode['uid']));
            $data['leafitemtitle'] = xarML('Email the Users in this Group');
            $data['leafitemimage'] = xarTplGetImage('email.png');
            return xarTplObject('roles', 'leaf', 'email', $data);
        }
    }

    function privilegesitem()
    {
        if (!$this->drawchildren) {
            return xarTplObject('roles', 'spacer', 'large');
        } else {
            $data['leafitemurl'] = xarModURL('roles', 'admin', 'showprivileges',
                            array('uid' => $this->treenode['uid']));
            $data['leafitemtitle'] = xarML('Show the Privileges assigned to this Group');
            $data['leafitemimage'] = xarTplGetImage('privileges.png');
            return xarTplObject('roles', 'leaf', 'showprivileges', $data);
        }
    }

    function testitem()
    {
        if (!$this->drawchildren) {
            return xarTplObject('roles', 'spacer', 'large');
        } else {
            $data['leafitemurl'] = xarModURL('roles', 'admin', 'testprivileges',
                            array('uid' => $this->treenode['uid']));
            $data['leafitemtitle'] = xarML("Test this Groups's Privileges");
            $data['leafitemimage'] = xarTplGetImage('test.png');
            return xarTplObject('roles', 'leaf', 'testprivileges', $data);
        }
    }

    function descriptionitem()
    {
        // if we've already done this entry skip the links and just tell the user
        if (!$this->drawchildren) {
            $data['leafitemtext'] = $this->treenode['name'];
            return xarTplObject('roles', 'leaf', 'placeholder', $data);
        } else {
            $numofsubgroups = count($this->roles->getsubgroups($this->treenode['uid']));
            $subgroups = $numofsubgroups == 1 ? xarML('subgroup') : xarML('subgroups');
            $users = $this->treenode['users'] == 1 ? xarML('user') : xarML('users');
            $data['leafitemurl'] = xarModURL('roles', 'admin', 'modifyrole',
                            array('uid' => $this->treenode['uid']));
            $data['leafitemtitle'] = xarML("Modify this Group");
            $data['leafitemtext'] = $this->treenode['name'];
            $data['leafitemdescription'] = $numofsubgroups . " " . $subgroups . ' | ' . $this->treenode['users'] . " " . $users;
            return xarTplObject('roles', 'leaf', 'modifyuser', $data);
        }
    }

    function treeitem()
    {
        $html = $this->smallblank;
        $html .= $this->drawindent();
        if ($this->isbranch) {
            if ($this->nodeindex != 1) {
                $lastindent = array_pop($this->indent);
                if ($lastindent == $this->L) {
                    $this->indent[] = $this->smallblank . $this->smallblank;
                } else {
                    $this->indent[] = $this->I . $this->smallblank;
                }
                $html .= $this->B;
            }
            $html .= $this->expandedbox;
        } else {
            if ($this->nodeindex != 1) {
                $html .= $this->B;
            }
            $html .= $this->emptybox;
        }
        return $html;
    }
    function drawindent()
    {
        $html = '';
        foreach ($this->indent as $column) {
            $html .= $column;
        }
        return $html;
    }
}
?>
