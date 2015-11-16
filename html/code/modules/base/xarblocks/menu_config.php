<?php
/**
 * Menu Block configuration interface
 *
 * @package modules\base
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.info
 * @link http://xaraya.info/index.php/release/68.html
 */

/**
 * Manage block config
 *
 * @author  John Cox <admin@dinerminor.com>
 * @access  public
 * @return  void
*/
sys::import('modules.base.xarblocks.menu');
class Base_MenuBlockConfig extends Base_MenuBlock implements iBlock
{
    /**
     * This method is called by the BasicBlock class constructor
     * @param void N/A
     */
    public function init()
    {
        parent::init();
    }

    /**
     * Modify Function to the Blocks Admin
     * 
     * @param array $data Data array
     * @return array Data array
     */
    public function configmodify(Array $data=array())
    {
        $data = $this->getContent();

        $data['modules'] = $this->xarmodules;
        $data['userlinks'] = self::getUserLinks();

        return $data;
    }

    /**
     * Updates the Block config from the Blocks Admin
     * 
     * @param array $data Data array
     * @return boolean Returns true on success, false on failure
     */
    public function configupdate(Array $data=array())
    {
        $data = parent::update($data);
        $vars = !empty($data['content']) ? $data['content'] : array();

        // Handle any methods specific to this block
        // CHECKME: is this the right place for handling this
        if (!xarVarFetch('menumethod',  'str:1:255', $menumethod, '', XARVAR_NOT_REQUIRED)) return;
        switch ($menumethod) {
            case  'linkorder':
                $links = array_merge($vars, $this->linkorderupdate());
                $this->blockinfo['content']['userlinks'] = $links['userlinks'];
                $this->setContent($links);
                if (!xarMod::apiFunc('blocks', 'instances', 'updateitem', $this->blockinfo)) return;
                xarController::redirect($links['return_url']);
            break;
            default:
            break;
        }

        // display options
        if (!xarVarFetch('showlogout',  'checkbox', $showlogout, false, XARVAR_NOT_REQUIRED)) return;
        if (!xarVarFetch('logoutlabel',  'str:1:255', $logoutlabel, '', XARVAR_NOT_REQUIRED)) return;
        if (!xarVarFetch('logouttitle',  'str:1:255', $logouttitle, '', XARVAR_NOT_REQUIRED)) return;
        if (!xarVarFetch('displayrss',  'checkbox', $displayrss, false, XARVAR_NOT_REQUIRED)) return;
        if (!xarVarFetch('rsslabel',  'str:1:255', $rsslabel, '', XARVAR_NOT_REQUIRED)) return;
        if (!xarVarFetch('rsstitle',  'str:1:255', $rsstitle, '', XARVAR_NOT_REQUIRED)) return;
        if (!xarVarFetch('displayprint','checkbox', $displayprint, false, XARVAR_NOT_REQUIRED)) return;
        if (!xarVarFetch('printlabel',  'str:1:255', $printlabel, '', XARVAR_NOT_REQUIRED)) return;
        if (!xarVarFetch('printtitle',  'str:1:255', $printtitle, '', XARVAR_NOT_REQUIRED)) return;
        if (!xarVarFetch('marker',      'str:0',    $marker, '', XARVAR_NOT_REQUIRED)) return;
        if (!xarVarFetch('showback',    'checkbox', $showback, false, XARVAR_NOT_REQUIRED)) return;
        if (!xarVarFetch('backlabel',  'str:1:255', $backlabel, '', XARVAR_NOT_REQUIRED)) return;
        if (!xarVarFetch('backtitle',  'str:1:255', $backtitle, '', XARVAR_NOT_REQUIRED)) return;
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
        if (!empty($new_url) || !empty($new_label) || !empty($new_title) || !empty($new_blank)) {
            $modlinks = array();
            $new_link = self::_decodeURL($new_url, true);
            $new_link['visible'] = 1;
            if (!empty($new_blank)) {
                $new_link['url'] = $new_label = $new_title = '';
                $new_link['name'] = '_blank_';
            } elseif ($new_link['ismodlink'] && $new_position > 1) {
                $new_link['ismodlink'] = 0;
                 if (empty($new_label)) {
                    $new_label = xarModGetDisplayableName($new_link['modname']);
                    $new_link['name'] = $new_link['modname'] . '_' . $new_link['modtype'] . '_main';
                }
                if (empty($new_title)) {
                    $new_title = xarModGetDisplayableDescription($new_link['modname']);
                }
            } elseif ($new_link['ismodlink']) {
                $new_link['name'] = $new_link['modname'] . '_' . $new_link['modtype'];
                // @TODO: handle module menu links?
            } elseif (!empty($new_label)) {
                $new_link['name'] = $new_label;
            } else {
                $new_link['name'] = '_nolabel_';
            }
            $new_link['label'] = $new_label;
            $new_link['title'] = $new_title;
            $new_link['menulinks'] = array();
        }

        // Now re-index our array of links, performing any selected actions along the way
        $new_links = array();
        $i = $j = 0;
        if (!empty($userlinks)) {
            foreach ($userlinks as $order => $link) {
                // add missing link settings from exisiting entry
                if (isset($this->userlinks[$order]))
                    $link += $this->userlinks[$order];
                // Insert new link before an item
                if ((!empty($new_link) && $new_position == 0) && ($new_relation == $order)) {
                    // insert new link before selected link
                    $new_link['id'] = $i;
                    $new_links[$i] = $new_link;
                    $i++;
                }
                // Perform operations on current link
                // decode the link url
                $link['encodedurl'] = $link['url'];
                $check = self::_decodeURL($link['url'], true);
                foreach ($check as $k => $v) {
                    $link[$k] = $v;
                }
                // set an appropriate name (so options in the "In Relation To" dropdown are never empty)
                if (empty($link['label']) && empty($link['title']) && empty($link['url'])) {
                    // blank link
                    $link['name'] = '_blank_';
                } elseif (!empty($link['label']) && !$link['ismodlink']) {
                    // normal link, set name as label
                   $link['name'] = $link['label'];
                } elseif ($link['ismodlink']) {
                    // module link, set name as module_type
                    $link['name'] = $link['modname'] . '_' . $link['modtype'];
                    /* @TODO: handle module menu links one day?
                    $modlinks = xarMod::apiFunc('base', 'admin', 'loadmenuarray',
                        array(
                            'modname' => $link['modname'],
                            'modtype' => $this->menumodtype,
                        ));
                    foreach ($modlinks as $key => $sublink) {
                        $name = $link['modname'] . '_' . $link['modtype'] . '_' . $key;
                        $sublink['isvisible'] = true;
                        $sublink += self::_decodeURL($sublink['url'], true);
                        $modlinks[$name] = $sublink;
                    }
                    */
                }

                // perform links_select action on selected items
                if (!empty($link['select']) && $links_select != 'none') {
                    // remove link
                    if ($links_select == 'delete') continue;
                    switch ($links_select) {
                        case 'show':
                            // make link visible
                            $link['visible'] = 1;
                        break;
                        case 'hide':
                            // make link invisible
                            $link['visible'] = 0;
                        break;
                        default:
                            // do nothing
                        break;
                    }
                }
                unset ($link['select']);
                // re-index sublinks
                $menu_links = array();
                // only if the parent isn't a module link
                if (empty($link['ismodlink'])) {
                    // insert link as first child of item
                    if ((!empty($new_link) && $new_position == 2) && ($new_relation == $order)) {
                        $new_link['id'] = $j;
                        // insert new link as first child of this link
                        $menu_links[$j] = $new_link;
                        $j++;
                    }
                    if (!empty($link['menulinks'])) {
                        foreach ($link['menulinks'] as $suborder => $sublink) {
                            // add missing link settings from existing entry
                            if (isset($this->userlinks[$order]['menulinks'][$suborder]))
                                $sublink += $this->userlinks[$order]['menulinks'][$suborder];
                            // decode the link url
                            $subcheck = self::_decodeURL($sublink['url'], true);
                            foreach ($subcheck as $k => $v) {
                                $sublink[$k] = $v;
                            }
                            // set an appropriate name (so options in the "In Relation To" dropdown are never empty)
                            if (empty($sublink['label']) && empty($sublink['title']) && empty($sublink['url'])) {
                                // blank link
                                $sublink['name'] = '_blank_';
                            } elseif (!empty($sublink['label']) && !$sublink['ismodlink']) {
                                // normal link, set name as label
                               $sublink['name'] = $sublink['label'];
                            } elseif ($sublink['ismodlink']) {
                                // module link, set name as module_type_main
                                $sublink['name'] = $subcheck['modname'] . '_' . $subcheck['modtype'] . '_main';
                                // @TODO: get name and title from module link?
                            }
                            // children can't be module links menu items
                            $sublink['ismodlink'] = 0;
                            // perform links_select action on selected items
                            if (!empty($sublink['select']) && $links_select != 'none') {
                                // remove sublink
                                if ($links_select == 'delete') continue;
                                switch ($links_select) {
                                    case 'show':
                                        // make sublink visible
                                        $sublink['visible'] = 1;
                                    break;
                                    case 'hide':
                                        // make sublink invisible
                                        $sublink['visible'] = 0;
                                    break;
                                    default:
                                        // do nothing
                                    break;
                                }
                            }
                            unset ($sublink['select']);
                            $sublink['id'] = $j;
                            $sublink['ismodlink'] = 0;
                            //$sublink['name'] = $sublink['label'];
                            $menu_links[$j] = $sublink;
                            $j++;
                        }
                    }
                    // append link as last child of item
                    if ((!empty($new_link) && $new_position == 3) && ($new_relation == $order)) {
                        $new_link['id'] = $j;
                        // insert new link as last child of this link
                        $menu_links[$j] = $new_link;
                        $j++;
                    }
                }
                // update current link values
                $link['menulinks'] = $menu_links;
                $link['id'] = $i;
                //$link['name'] = $link['label'];
                $new_links[$i] = $link;
                $i++;
                // insert link after item
                if ((!empty($new_link) && $new_position == 1) && ($new_relation == $order)) {
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
        foreach ($this->xarmodules as $mod) {
            $modname = $mod['name'];
            if (empty($modulelist[$modname]['visible']))
                $modulelist[$modname]['visible'] = 0;
            if (empty($modulelist[$modname]['alias_name']) ||
                empty($this->modulelist[$modname]['aliases']) ||
                !isset($this->modulelist[$modname]['aliases'][$modulelist[$modname]['alias_name']])) {
                $modulelist[$modname]['alias_name'] = $modname;
            }
            $isvalid = $accessproperty->checkInput('modulelist_'.$modname.'_view_access');
            $modulelist[$modname]['view_access'] = $accessproperty->getValue();
        }

        // put updated values in the content array
        $vars['userlinks'] = $new_links;
        $vars['modulelist'] = $modulelist;
        $vars['showback'] = $showback;
        $vars['backlabel'] = $backlabel;
        $vars['backtitle'] = $backtitle;
        $vars['showlogout'] = $showlogout;
        $vars['logoutlabel'] = $logoutlabel;
        $vars['logouttitle'] = $logouttitle;
        $vars['marker'] = $marker;
        $vars['displayrss'] = $displayrss;
        $vars['rsslabel'] = $rsslabel;
        $vars['rsstitle'] = $rsstitle;
        $vars['displayprint'] = $displayprint;
        $vars['printlabel'] = $printlabel;
        $vars['printtitle'] = $printtitle;

        $this->setContent($vars);
        return true;
    }

    /**
     * Admin get userlinks method
     * 
     * @param void N/A
     * @return string[] Returns user links as array
     */
    public function getUserLinks()
    {
        $userlinks = array();
        $authid = xarSecGenAuthkey();
        if (!empty($this->userlinks)) {
            $numlinks = count($this->userlinks);
            $i = 1;
            foreach ($this->userlinks as $linkid => $link) {
                if (empty($linkid)) $linkid = $i-1;
                if (!isset($link['encodedurl'])) {
                    $check = self::_decodeURL($link['url'], true);
                    foreach ($check as $k => $v) {
                        $link[$k] = $v;
                    }
                }
                $link['checkurl'] = $link['url'];
                $link['url'] = $link['encodedurl'];
                // Add order links to parent menu items
                if ($i < $numlinks) {
                    $link['downurl'] = xarServer::getCurrentUrl(array('interface' => 'config', 'menumethod' => 'linkorder', 'phase' => 'update', 'linkid' =>  $linkid, 'direction' => 'down', 'authid' => $authid, 'this' => '0'));
                    /*
                    $link['downurl'] = xarModURL('blocks', 'admin', 'modify_instance',
                        array('interface' => 'config', 'method' => 'linkorder', 'block_id' => $this->block_id, 'linkid' => $linkid, 'direction' => 'down', 'authid' => $authid, 'phase' => 'update'));
                    */
                }
                if ($i > 1) {
                    $link['upurl'] = xarServer::getCurrentUrl(array('interface' => 'config', 'menumethod' => 'linkorder', 'phase' => 'update', 'linkid' => $linkid, 'direction' => 'up', 'authid' => $authid));
                    /*
                    $link['upurl'] = xarModURL('blocks', 'admin', 'modify_instance',
                        array('interface' => 'config', 'method' => 'linkorder', 'block_id' => $this->block_id, 'linkid' => $linkid, 'direction' => 'up', 'authid' => $authid, 'phase' => 'update'));
                    */
                }
                if (!empty($link['menulinks'])) {
                    $sublinks = array();
                    $numsublinks = count($link['menulinks']);
                    $j = 1;
                    foreach ($link['menulinks'] as $sublinkid => $sublink) {
                        if (!isset($sublink['encodedurl'])) {
                            $check = self::_decodeURL($sublink['url'], true);
                            foreach ($check as $k => $v) {
                                $sublink[$k] = $v;
                            }
                        }
                        $sublink['checkurl'] = $sublink['url'];
                        $sublink['url'] = $sublink['encodedurl'];

                        // Add order links to child menu items
                        if ($j < $numsublinks) {
                            $link['downurl'] = xarServer::getCurrentUrl(array('interface' => 'config', 'menumethod' => 'linkorder', 'phase' => 'update', 'linkid' => $linkid, 'sublinkid' => $sublinkid, 'direction' => 'down', 'authid' => $authid));
                            /*
                            $sublink['downurl'] = xarModURL('blocks', 'admin', 'modify_instance',
                                array('interface' => 'config', 'method' => 'linkorder', 'block_id' => $this->block_id, 'linkid' => $linkid, 'sublinkid' => $sublinkid, 'direction' => 'down', 'authid' => $authid, 'phase' => 'update'));
                            */
                        }
                        if ($j > 1) {
                            $sublink['upurl'] = xarServer::getCurrentUrl(array('interface' => 'config', 'menumethod' => 'linkorder', 'phase' => 'update', 'linkid' => $linkid, 'sublinkid' => $sublinkid, 'direction' => 'up', 'authid' => $authid));
                            /*
                            $sublink['upurl'] = xarModURL('blocks', 'admin', 'modify_instance',
                                array('interface' => 'config', 'method' => 'linkorder', 'block_id' => $this->block_id, 'linkid' => $linkid, 'sublinkid' => $sublinkid, 'direction' => 'up', 'authid' => $authid, 'phase' => 'update'));
                            */
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
     * 
     * @param array $data Data array
     * @return array|null Returns data array containing link ordering. If linkid, sublinkid or direction have not been found null is returned.
     * @throws EmptyParameterException Thrown if linkid and direction are not given.
     */
    public function linkorderupdate(Array $data=array())
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
        $data['userlinks'] = $this->userlinks;
        $data['return_url'] = xarServer::getCurrentURL(array('interface' => 'config', 'menumethod' => null, 'authid' => null, 'direction' => null, 'sublinkid' => null, 'linkid' => null, 'phase' => null), null, 'menulinks_'.$this->block_id);
        /* 
        $data['return_url'] = xarModURL('blocks', 'admin', 'modify_instance',
            array('block_id' => $this->block_id, 'interface' => 'config'), null, 'menulinks_'.$this->block_id);
        */
        return $data;
    }

}
?>