<?php
/**
 * Menu Block
 *
 * @package modules
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Base module
 * @link http://xaraya.com/index.php/release/68.html
 */

/**
 * Manage block
 *
 * @author  John Cox <admin@dinerminor.com>
 * @access  public
 * @param   none
 * @return  nothing
 * @throws  no exceptions
 * @todo    nothing
*/
sys::import('modules.base.xarblocks.menu');

class Base_MenuBlockAdmin extends Base_MenuBlock implements iBlock
{
/**
 * Modify Function to the Blocks Admin
 * @param $data array containing title,content
 */
    public function modify(Array $data=array())
    {
        $data = parent::modify($data);

        $data['modules'] = $this->usermodules;
        $data['userlinks'] = self::getUserLinks();

        return $data;
    }

/**
 * Updates the Block config from the Blocks Admin
 * @param $data array containing title,content
 */
    public function update(Array $data=array())
    {
        $data = parent::update($data);
        $vars = !empty($data['content']) ? $data['content'] : array();

        // display options
        if (!xarVarFetch('showlogout',  'checkbox', $showlogout, false, XARVAR_NOT_REQUIRED)) return;
        if (!xarVarFetch('displayrss',  'checkbox', $displayrss, false, XARVAR_NOT_REQUIRED)) return;
        if (!xarVarFetch('displayprint','checkbox', $displayprint, false, XARVAR_NOT_REQUIRED)) return;
        if (!xarVarFetch('marker',      'str:0',    $marker, '', XARVAR_NOT_REQUIRED)) return;
        if (!xarVarFetch('showback',    'checkbox', $showback, false, XARVAR_NOT_REQUIRED)) return;

        // userlinks
        if (!xarVarFetch('userlinks',   'array',    $userlinks, array(), XARVAR_NOT_REQUIRED)) return;
        if (!xarVarFetch('links_select', 'pre:trim:lower:enum:show:hide:delete', $links_select, 'none', XARVAR_NOT_REQUIRED)) return;

        // add new link
        if (!xarVarFetch('links_new_url', 'str:1:254', $new_url, '', XARVAR_NOT_REQUIRED)) return;
        if (!xarVarFetch('links_new_label', 'str:1:254', $new_label, '', XARVAR_NOT_REQUIRED)) return;
        if (!xarVarFetch('links_new_title', 'str:1:254', $new_title, '', XARVAR_NOT_REQUIRED)) return;
        if (!xarVarFetch('links_new_blank', 'checkbox', $new_blank, '', XARVAR_NOT_REQUIRED)) return;
        if (!xarVarFetch('links_new_position', 'int:0:3', $new_position, 0, XARVAR_NOT_REQUIRED)) return;
        if (!xarVarFetch('links_new_relation', 'int:0:', $new_relation, 0, XARVAR_NOT_REQUIRED)) return;

        // modulelist
        if (!xarVarFetch('modulelist',  'array',    $modulelist, array(), XARVAR_NOT_REQUIRED)) return;

        // handle user links
        // Build new link if we have any values for it
        if (!empty($new_url) || !empty($new_name) || !empty($new_title) || !empty($new_blank)) {
            if (!empty($new_blank)) $new_url = $new_label = $new_title = '';
            // we don't set the id here, since it could be anything
            $new_link = array(
                'url' => $new_url,
                'name' => $new_label,
                'label' => $new_label,
                'title' => $new_title,
                'visible' => 1,
                'menulinks' => array(),
            );
        }

        // Now re-index our array of links, performing any selected actions along the way
        $new_links = array();
        $i = $j = 0;
        if (!empty($userlinks)) {
            foreach ($userlinks as $order => $link) {
                // Insert new link before an item
                if ((!empty($new_link) && $new_position == 0) &&
                    ($new_relation == $order)) {
                    // insert new link before selected link
                    $new_link['id'] = $i;
                    $new_links[$i] = $new_link;
                    $i++;
                }
                // perform links_select action on selected items
                if (!empty($link['select']) && $links_select == 'delete') {
                    continue;
                } elseif (!empty($link['select'])) {
                    switch ($links_select) {
                        case 'show':
                            $link['visible'] = 1;
                        break;
                        case 'hide':
                            $link['visible'] = 0;
                        break;
                        case 'none':
                        default:
                            $link['visible'] = !empty($this->userlinks[$order]['visible']);
                        break;
                    }
                } else {
                    $link['visible'] = !empty($this->userlinks[$order]['visible']);
                }
                $menu_links = array();
                // insert link as first child of item
                if ((!empty($new_link) && $new_position == 2) &&
                    ($new_relation == $order)) {
                    $new_link['id'] = $j;
                    // insert new link as first child of this link
                    $menu_links[$j] = $new_link;
                    $j++;
                }
                if (!empty($link['menulinks'])) {
                    foreach ($link['menulinks'] as $suborder => $sublink) {
                        // perform links_select action on selected items
                        if (!empty($sublink['select']) && $links_select == 'delete') {
                            continue;
                        } elseif (!empty($sublink['select'])) {
                            switch ($links_select) {
                                case 'show':
                                    $sublink['visible'] = 1;
                                break;
                                case 'hide':
                                    $sublink['visible'] = 0;
                                break;
                                case 'none':
                                default:
                                    $sublink['visible'] = !empty($this->userlinks[$order]['menulinks'][$suborder]['visible']);
                                break;
                            }
                        } else {
                            $sublink['visible'] = !empty($this->userlinks[$order]['menulinks'][$suborder]['visible']);
                        }
                        $sublink['id'] = $j;
                        //$sublink['name'] = $sublink['label'];
                        $menu_links[$j] = $sublink;
                        $j++;
                    }
                }
                // append link as last child of item
                if ((!empty($new_link) && $new_position == 3) &&
                    ($new_relation == $order)) {
                    $new_link['id'] = $j;
                    // insert new link as last child of this link
                    $menu_links[$j] = $new_link;
                    $j++;
                }
                $link['menulinks'] = $menu_links;
                $link['id'] = $i;
                $link['name'] = $link['label'];
                $new_links[$i] = $link;
                $i++;
                // insert link after item
                if ((!empty($new_link) && $new_position == 1) &&
                    ($new_relation == $order)) {
                    $new_link['id'] = $i;
                    // insert new link after this link
                    $new_links[$i] = $new_link;
                    $i++;
                }
            }
        }

        // handle modulelist input
        sys::import('modules.dynamicdata.class.properties.master');
        $accessproperty = DataPropertyMaster::getProperty(array('name' => 'access'));
        foreach ($this->usermodules as $mod) {
            $modname = $mod['name'];
            if (empty($modulelist[$modname]['visible']))
                $modulelist[$modname]['visible'] = 0;
            if (empty($modulelist[$modname]['alias_name']) ||
                empty($mod['aliases']) ||
                !isset($mod['aliases'][$modulelist[$modname]['alias_name']])) {
                $modulelist[$modname]['alias_name'] = $modname;
            }
            $isvalid = $accessproperty->checkInput('modulelist_'.$modname.'_view_access');
            $modulelist[$modname]['view_access'] = $accessproperty->value;
        }

        // put updated values in the content array
        $vars['userlinks'] = $new_links;
        $vars['modulelist'] = $modulelist;
        $vars['showback'] = $showback;
        $vars['showlogout'] = $showlogout;
        $vars['marker'] = $marker;
        $vars['displayrss'] = $displayrss;
        $vars['displayprint'] = $displayprint;

        $data['content'] = $vars;

        return $data;
    }

    public function help()
    {
        return $this->getInfo();
    }

/**
 * Admin get userlinks method
 * Adds links to order the menu links, used by updatelinkorder method
**/
    public function getUserLinks()
    {
        $userlinks = array();
        $authid = xarSecGenAuthkey();
        if (!empty($this->userlinks)) {
            $numlinks = count($this->userlinks);
            $i = 1;
            foreach ($this->userlinks as $linkid => $link) {
                if ($i < $numlinks) {
                    $link['downurl'] = xarModURL('blocks', 'admin', 'update_instance',
                        array('tab' => 'linkorder', 'bid' => $this->bid, 'linkid' => $linkid, 'direction' => 'down', 'authid' => $authid));
                }
                if ($i > 1) {
                    $link['upurl'] = xarModURL('blocks', 'admin', 'update_instance',
                        array('tab' => 'linkorder', 'bid' => $this->bid, 'linkid' => $linkid, 'direction' => 'up', 'authid' => $authid));
                }
                if (!empty($link['menulinks'])) {
                    $sublinks = array();
                    $numsublinks = count($link['menulinks']);
                    $j = 1;
                    foreach ($link['menulinks'] as $sublinkid => $sublink) {
                        if ($j < $numsublinks) {
                            $sublink['downurl'] = xarModURL('blocks', 'admin', 'update_instance',
                                array('tab' => 'linkorder', 'bid' => $this->bid, 'linkid' => $linkid, 'sublinkid' => $sublinkid, 'direction' => 'down', 'authid' => $authid));
                        }
                        if ($j > 1) {
                            $sublink['upurl'] = xarModURL('blocks', 'admin', 'update_instance',
                                array('tab' => 'linkorder', 'bid' => $this->bid, 'linkid' => $linkid, 'sublinkid' => $sublinkid, 'direction' => 'up', 'authid' => $authid));
                        }
                        $sublinks[$sublinkid] = $sublink;
                        $j++;
                    }
                    $link['menulinks'] = $sublinks;
                }
                $userlinks[$linkid] = $link;
                $i++;
            }
        }
        return $userlinks;
    }

/**
 * Custom update method to handle link ordering
**/
    public function updatelinkorder(Array $data=array())
    {
        $data = $this->getInfo();
        if (!xarVarFetch('linkid', 'int:0:', $linkid, null, XARVAR_DONT_SET)) return;
        if (!xarVarFetch('sublinkid', 'int:0:', $sublinkid, null, XARVAR_DONT_SET)) return;
        if (!xarVarFetch('direction', 'pre:trim:lower:enum:up:down', $direction, null, XARVAR_DONT_SET)) return;

        if (!isset($linkid)) throw new EmptyParameterException('linkid');
        if (!isset($direction)) throw new EmptyParameterException('direction');

        foreach ($this->userlinks as $order => $link) {
            if ($order == $linkid) {
                if (!isset($sublinkid)) {
                    if ($direction == 'up' && isset($this->userlinks[$order-1])) {
                        $temp = $this->userlinks[$order-1];
                        $this->userlinks[$order-1] = $link;
                        $this->userlinks[$order] = $temp;
                    } elseif ($direction == 'down' && isset($this->userlinks[$order+1])) {
                        $temp = $this->userlinks[$order+1];
                        $this->userlinks[$order+1] = $link;
                        $this->userlinks[$order] = $temp;
                    }
                } else {
                    if (!empty($link['menulinks'])) {
                        foreach ($link['menulinks'] as $suborder => $sublink) {
                            if ($suborder == $sublinkid) {
                                if ($direction == 'up' && isset($this->userlinks[$order]['menulinks'][$suborder-1])) {
                                    $temp = $this->userlinks[$order]['menulinks'][$suborder-1];
                                    $this->userlinks[$order]['menulinks'][$suborder-1] = $sublink;
                                    $this->userlinks[$order]['menulinks'][$suborder] = $temp;
                                } elseif ($direction == 'down' && isset($this->userlinks[$order]['menulinks'][$suborder+1])) {
                                    $temp = $this->userlinks[$order]['menulinks'][$suborder+1];
                                    $this->userlinks[$order]['menulinks'][$suborder+1] = $sublink;
                                    $this->userlinks[$order]['menulinks'][$suborder] = $temp;
                                }
                                break;
                            }
                        }
                    }
                }
                break;
            }
        }
        $data['content']['userlinks'] = $this->userlinks;
        $data['return_url'] = xarModURL('blocks', 'admin', 'modify_instance',
            array('bid' => $this->bid), null, 'menulinks_'.$this->bid);
        return $data;
    }

}
?>