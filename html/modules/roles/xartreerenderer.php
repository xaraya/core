<?php
/**
 * Roles tree renderer
 *
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2005 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Roles module
 */
/**
 * Purpose of file:  Roles tree renderer
 * @author Marc Lutolf <marcinmilan@xaraya.com>
 */

include_once 'modules/roles/xarroles.php';

class xarTreeRenderer
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
    public $el;
    public $tee;
    public $aye;
    public $bar;
    public $emptybox;
    public $blank;
    public $bigblank;
    public $smallblank;

    /**
     * Constructor
     */
    function xarTreeRenderer($allowtoggle=0)
    {
        $boxwidth = '1em';
        $boxheight = '21px';
        $this->el = '<img src="' . xarTplGetImage("el.gif",'roles') . '" alt="" style="vertical-align: middle; width: ' . $boxwidth . '; height: ' . $boxheight . ';" />';
        $this->tee = '<img src="' . xarTplGetImage("T.gif",'roles') . '" alt="" style="vertical-align: middle; width: ' . $boxwidth . '; height: ' . $boxheight . ';" />';
        $this->aye = '<img src="' . xarTplGetImage("I.gif",'roles') . '" alt="" style="vertical-align: middle; width: ' . $boxwidth . '; height: ' . $boxheight . ';" />';
        $this->bar = '<img src="' . xarTplGetImage("s.gif",'roles') . '" alt="" style="vertical-align: middle; width: ' . $boxwidth . '; height: ' . $boxheight . ';" />';
        $this->emptybox = '<img src="' . xarTplGetImage("k1.gif",'roles') . '" alt="" style="vertical-align: middle" />';
        $this->blank = '<img src="' . xarTplGetImage("blank.gif",'roles') . '" alt="" style="vertical-align: middle" />';
        $this->bigblank = '<span style="padding-left: 0.25em; padding-right: 0.25em;"><img src="' . xarTplGetImage("blank.gif",'roles') . '" alt="" style="vertical-align: middle; width: 16px; height: 16px;" /></span>';
        $this->smallblank     = '<span style="padding-left: 0em; padding-right: 0em;"><img src="' . xarTplGetImage("blank.gif",'roles') . '" alt="" style="vertical-align: middle; width: ' . $boxwidth . '; height: ' . $boxheight . ';" /></span>';
        $this->roles = new xarRoles();
        $this->setitem(1, "deleteitem");
        $this->setitem(2, "leafitem");
        $this->setitem(3, "emailitem");
        $this->setitem(4, "privilegesitem");
        $this->setitem(5, "testitem");
        $this->setitem(6, "treeitem");
        $this->setitem(7, "descriptionitem");
        if ($allowtoggle) {
            $this->expandedbox    = '<img class="xar-roletree-box" src="modules/roles/xarimages/k2.gif" alt="" style="vertical-align: middle" onclick="toggleBranch(this,this.parentNode.lastChild)" />';
            $this->collapsedbox   = '<img class="xar-roletree-box" src="modules/roles/xarimages/k3.gif" alt="" style="vertical-align: middle" onclick="toggleBranch(this,this.parentNode.lastChild)"/>';
        }
        else {
            $this->expandedbox    = '<img class="xar-roletree-box" src="modules/roles/xarimages/k2.gif" alt="" style="vertical-align: middle" />';
            $this->collapsedbox   = '<img class="xar-roletree-box" src="modules/roles/xarimages/k3.gif" alt="" style="vertical-align: middle" />';
        }
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
        $this->tree = $this->addbranches($initialnode);
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
        $this->html = '<div name="RolesTree" id="RolesTree">';
        $this->nodeindex = 0;
        $this->indent = array();
        $this->level = 0;
        $this->alreadydone = array();

        $this->drawbranch($tree);
        $this->html .= '</div>';
        return $this->html;
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
        $this->html .= $this->isbranch ?
            '<div class="xar-roletree-branch" id="branch' . $this->nodeindex . '">' :
            '<div class="xar-roletree-leaf" id="leaf' . $this->nodeindex . '" >';

        for ($i=1,$max = count($this->treeitems); $i <= $max; $i++) {
            $func = $this->treeitems[$i];
            $this->html .= $this->{$func}();
        }

        // we've finished this row; now do the children of this role
        $ind = 0;
        foreach($node['children'] as $subnode) {
            $ind = $ind + 1;
            // if this is the last child, get ready to draw an "L", otherwise a sideways "T"
            if ($ind == count($node['children'])) {
                $this->indent[] = $this->el;
            } else {
                $this->indent[] = $this->tee;
            }
            // draw this child
            $this->drawbranch($subnode);
            // we're done; remove the indent string
            array_pop($this->indent);
        }
        $this->level --;
        // write the closing tags
//        $this->html .= $this->isbranch ? '</div>' : '';
        // close the html row
        $this->html .= '</div>';
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
            $html = $this->bigblank;
        } else {
            $html = '<a href="' .
            xarModURL('roles',
                'admin',
                'showusers',
                array('uid' => $this->treenode['uid'], 'reload' => 1)) . '" title="' . xarML('Show the Users in this Group') . '" style="padding-left: 0.25em; padding-right: 0.25em;"><img src="modules/roles/xarimages/users.gif" style="vertical-align: middle;" alt="" /></a>';
        }
        return $html;
    }

    function deleteitem()
    {
        if (!xarSecurityCheck('DeleteRole',0,'Roles',$this->treenode['name']) || ($this->treenode['users'] > 0) || (!$this->drawchildren)) {
            $html = $this->bigblank;
        } else {
            $html = '<a href="' .
            xarModURL('roles',
                'admin',
                'deleterole',
                array('uid' => $this->treenode['uid'])) . '" title="' . xarML('Delete this Group') . '" style="padding-left: 0.25em; padding-right: 0.25em;"><img src="modules/roles/xarimages/delete.gif" style="vertical-align: middle;" alt="" /></a>';
        }
        return $html;
    }

    function emailitem()
    {
        if ($this->treenode['users'] == 0 || (!$this->drawchildren)) {
            $html = $this->bigblank;
        } else {
            $html = '<a href="' .
            xarModURL('roles',
                'admin',
                'createmail',
                array('uid' => $this->treenode['uid'])) . '" title="' . xarML('Email the Users in this Group') . '" style="padding-left: 0.25em; padding-right: 0.25em;"><img src="modules/roles/xarimages/email.gif" style="vertical-align: middle;" alt=""/></a>';
        }
        return $html;
    }

    function privilegesitem()
    {
        if (!$this->drawchildren) {
            $html = $this->bigblank;
        } else {
            $html = '<a href="' .
            xarModURL('roles',
                'admin',
                'showprivileges',
                array('uid' => $this->treenode['uid'])) . '" title="' . xarML('Show the Privileges assigned to this Group') . '" style="padding-left: 0.25em; padding-right: 0.25em;"><img src="modules/roles/xarimages/privileges.gif" style="vertical-align: middle;" alt="" /></a>';
        }
        return $html;
    }

    function testitem()
    {
        if (!$this->drawchildren) {
            $html = $this->bigblank;
        } else {
            $html = '<a href="' .
            xarModURL('roles',
                'admin',
                'testprivileges',
                array('uid' => $this->treenode['uid'])) . '" title="' . xarML('Test this Groups\'s Privileges') . '" style="padding-left: 0.25em; padding-right: 0.25em;"><img src="modules/roles/xarimages/test.gif" style="vertical-align: middle;" alt=""/></a>';
        }
        return $html;
    }

    function branchitem()
    {
        $html = '<a href="' .
            xarModURL('roles',
                'admin',
                'modifyrole',
                array('uid' => $this->treenode['uid'])) .'" title="' . xarML('Modify this Group') . '" style="padding-left: 0.25em; padding-right: 0.25em;"><img src="modules/roles/xarimages/infoicon.gif" style="vertical-align: middle;" alt=""/></a>';
        return $html;
    }

    function descriptionitem()
    {
        $html = '<span style="padding-left: 1em">';
        // if we've already done this entry skip the links and just tell the user
        if (!$this->drawchildren) {
            $html .= '<b>' . $this->treenode['name'] . '</b>: ';
            $html .= ' see the entry above';
        } else {
            $html .= '<a href="' .
            xarModURL('roles',
                'admin',
                'modifyrole',
                array('uid' => $this->treenode['uid'])) . ' " title="' . xarML('Modify this Group') . '">' . $this->treenode['name'] . '</a>: &nbsp;';
            $numofsubgroups = count($this->roles->getsubgroups($this->treenode['uid']));
            $subgroups = $numofsubgroups == 1 ? xarML('subgroup') : xarML('subgroups');
            $html .= $numofsubgroups . " " . $subgroups;
            $users = $this->treenode['users'] == 1 ? xarML('user') : xarML('users');
            $html .= ' | ' . $this->treenode['users'] . " " . $users . '</span>';
        }
        return $html;
    }

    function treeitem()
    {
        $html = $this->smallblank;
        $html .= $this->drawindent();
        if ($this->isbranch) {
            if ($this->nodeindex != 1) {
                $lastindent = array_pop($this->indent);
                if ($lastindent == $this->el) {
                    $this->indent[] = $this->smallblank . $this->smallblank;
                } else {
                    $this->indent[] = $this->aye . $this->smallblank;
                }
                $html .= $this->bar;
            }
            $html .= $this->expandedbox;
        } else {
            if ($this->nodeindex != 1) {
                $html .= $this->bar;
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
