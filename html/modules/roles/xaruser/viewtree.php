<?php
/**
 * Display part of the roles hierarchy as a tree
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
 * viewTree - display part of the roles hierarchy as a tree
 * 
 * @author  Marc Lutolf <marcinmilan@xaraya.com>
 * @access  public
 * @param   none
 * @return  none
 * @throws  none
 * @todo    none
 */
function roles_user_viewtree()
{

    // Security Check
    if (!xarSecurityCheck('ViewRoles')) return;
    // Define at which uid the tree starts
    // If not set the uid becomes that of Everybody
    if(!xarVarFetch('uid',     'int', $uid,    xarModGetVar('roles','everybody'), XARVAR_NOT_REQUIRED)) {return;}
    // Define the number of levels to be displayed
    // If not set then defaults to 0 (all the levels below the first node are displayed)
    if(!xarVarFetch('levels',  'int', $levels, 0, XARVAR_NOT_REQUIRED)) {return;}
    // This is just shorthand to include the necessary files and create a new tree renderer
    $tree = xarTree();
    // We need to extend the renderer class because we need to add some stuff
    class orgChart extends xarTreeRenderer
    {
        // This defines an item for displaying the name of each group and the number of users
        // it contains
        function descriptionitem()
        {
            $html    = '<span style="padding-left: 1em">';
            $html   .= $this->treenode['name'];
            $members = $this->treenode['users'] == 1 ? xarML('member') : xarML('members');
            $html   .= ' (' . $this->treenode['users'] . " " . $members .')</span>';
            return $html;
        }
    }

    // Create a new tree based on the class we just defined
    // Give it a 1 as a parameter if you want to be able to expand or collapse parts
    // of the tree. This is not working yet.
    // The default draws a static tree.
    $mytree = new orgChart();
    // Remove the items that are displayed by default in each row
    // so we start with a clean slate
    $mytree->clearitems();
    // Add the display of the tree itself as the first item
    // We'll take the default display from the paarent class cause it looks OK
    $mytree->setitem(1,'treeitem');
    // Add the display of the name (defined above) as the second item
    $mytree->setitem(2,'descriptionitem');
    // Define at which uid the tree starts
    // If not set the uid becomes that of Everybody
    $firstnode = xarFindRole('Everybody');
    $firstuid = $firstnode->getID();
    // Create the tree
    $mytree->maketree($uid,$levels);
    $treedrawing = $mytree->drawtree();
    // Or alternatively, shorthand:
    //$treedrawing = $mytree->drawtree($mytree->maketree($firstuid,$levels));
    // Send the rendering to a template
    $data['chart'] = $treedrawing;
    return $data;
}
?>