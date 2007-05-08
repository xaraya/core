<?php
/**
 * Roles tree renderer
 *
 * @package modules
 * @copyright (C) 2002-2006 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Roles module
 * @link http://xaraya.com/index.php/release/27.html
 */

sys::import('modules.roles.xarroles');

// @ todo DOUBLE DEFINITION of same class (in privileges/xartreerenderer)
class xarTreeRenderer extends Object
{
    public $roles;
    public $tree;
    public $treenode;
    public $treeitems;
    public $levels;

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

    /**
     * Constructor
     */
    function __construct($allowtoggle=0)
    {
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

    /**
     * maketree: make a tree of the roles that are groups
     *
     * We don't include users in the tree because there are too many to display
     *
     * @author Marc Lutolf <marcinmilan@xaraya.com>
     * @access private
     * @param none $
     * @return boolean
     * @throws none
     * @todo none
     */
    function maketree($topuid='',$levels=0)
    {
        $this->levels = $levels;
        if ($topuid == '') $topuid = xarModGetVar('roles', 'everybody');
        $initialnode = array(
                    'parent' => $this->roles->getgroup($topuid),
                    'level' => 1
                    );
//        $this->tree = $this->addbranches($initialnode);
        return $this->addbranches($initialnode);
    }

    /**
     * addbranches: given an initial tree node, add on the branches that are groups
     *
     * We don't include users in the tree because there are too many to display
     *
     * @author Marc Lutolf <marcinmilan@xaraya.com>
     * @access private
     * @param tree $ node
     * @return tree node
     * @throws none
     * @todo none
     */
    function addbranches($node)
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

    /**
     * drawtree: create a crude html drawing of the role tree
     *
     * We use the data from maketree to create a tree layout
     * This should be in a template or at least in the xaradmin file, but it's easier here
     *
     * @author Marc Lutolf <marcinmilan@xaraya.com>
     * @access private
     * @param array $ representing an initial node
     * @return none
     * @throws none
     * @todo none
     */

    /**
     * drawtree: draws the role tree
     * sets everything up and draws the first node
     *
     * @author Marc Lutolf <marcinmilan@xaraya.com>
     * @access private
     * @param nested array representing a tree
     * @return none
     * @throws none
     * @todo none
     */

    function drawtree($tree='')
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

    /**
     * drawbranch: draw a branch of the role tree
     *
     * This is a recursive function
     * This should be in a template or at least in the xaradmin file, but it's easier here
     *
     * @author Marc Lutolf <marcinmilan@xaraya.com>
     * @access private
     * @param array $ representing a tree node
     * @return none
     * @throws none
     * @todo none
     */

    function drawbranch($node)
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
     * drawindent: draws the graphic part of the tree
     *
     * A helper funtion to output a HTML string containing the pictures for
     * a line of the tree
     *
     * @author Marc Lutolf <marcinmilan@xaraya.com>
     * @access public
     * @param none $
     * @return string
     * @throws none
     * @todo none
     */

    function drawindent()
    {
        $html = '';
        foreach ($this->indent as $column) {
            $html .= $column;
        }
        return $html;
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
            $data['leafitemurl'] = xarModURL('roles', 'admin', 'delete',
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
            $data['leafitemurl'] = xarModURL('roles', 'admin', 'modify',
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

//-----------------------------------------------------------------------

    function setitem($pos=1,$item ='')
    {
        $this->treeitems[$pos] =& $item;
    }

    function clearitems()
    {
        $this->treeitems = array();
    }
}

?>
