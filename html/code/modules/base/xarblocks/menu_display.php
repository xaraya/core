<?php
/**
 * Menu Block display interface
 *
 * @package modules
 * @subpackage base module
 * @category Xaraya Web Applications Framework
 * @version 2.3.1
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 * @link http://xaraya.com/index.php/release/68.html
 */

/**
 * Display block
 *
 * @author  John Cox <admin@dinerminor.com>
 * @access  public
 * @return  void
*/
sys::import('modules.base.xarblocks.menu');

class Base_MenuBlockDisplay extends Base_MenuBlock implements iBlock
{
    public function init()
    {
        parent::init();
    }
/**
 * Display func.
 * @param $data array containing title,content
 */
    function display(Array $data=array())
    {
        $data = $this->getContent();

        if (xarUserIsLoggedIn()) {
            if (!empty($data['showlogout'])) {
                $authmoduledata = xarMod::apiFunc('roles','user','getdefaultauthdata');
                $authmodlogout = $authmoduledata['defaultloginmodname'];
                if (xarSecurityCheck('AdminBase',0)) {
                    $data['logouturl'] = xarModURL('base', 'admin', 'confirmlogout');
                } else {
                    $data['logouturl'] = xarModURL($authmodlogout,'user', 'logout', array());
                    $data['showback'] = 0;
                }
            }
            $data['loggedin'] = 1;
        } else {
            $data['showlogout'] = 0;
            $data['showback'] = 0;
            $data['loggedin'] = 0;
        }

        // get userlinks using dedicated method
        $data['userlinks'] = self::getUserLinks();

        // Handle modulelist
        $modlinks = array();
        foreach ($this->xarmodules as $mod) {
            $modname = $mod['name'];
            if (!isset($this->modulelist[$modname])) continue;
            $link = $this->modulelist[$modname];
            $link['modname'] = $modname;
            $link = self::getModuleLink($link);
            if (!$link) continue;
            $modlinks[$modname] = $link;
        }
        $data['modlinks'] = $modlinks;

        // no links, nothing to display
        if (
            empty($data['modlinks']) &&
            empty($data['userlinks']) &&
            empty($data['showlogout']) &&
            empty($data['showback']) &&
            empty($data['displayprint']) &&
            empty($data['displayrss'])
        ) return;

        // pass through the current request info
        $data['thismodname'] = self::$thismodname;
        $data['thismodtype'] = self::$thismodtype;
        $data['thisfuncname'] = self::$thisfuncname;

        if (!empty($data['displayrss']) && !xarThemeIsAvailable('rss')) $data['displayrss'] = 0;
        if (!empty($data['displayprint']) && !xarThemeIsAvailable('print')) $data['displayprint'] = 0;

        return $data;
    }


    public function getUserLinks()
    {
        $userlinks = array();

        if (!empty($this->userlinks)) {
            foreach ($this->userlinks as $id => $link) {
                if (empty($link['visible'])) continue;
                // handle links not yet using encode/decode settings
                if (!isset($link['encodedurl'])) {
                    $check = self::_decodeURL($link['url'], true);
                    foreach ($check as $k => $v) {
                        $link[$k] = $v;
                    }
                }
                if (!empty($link['ismodlink'])) {
                    $link = self::getModuleLink($link);
                    if (!$link) continue;
                } elseif (self::$currenturl == $link['url']) {
                    $link['url'] = '';
                    $link['isactive'] = 1;
                } else {
                    $link['isactive'] = 0;
                }

                if (!empty($link['menulinks'])) {
                    foreach ($link['menulinks'] as $subid => $sublink) {
                        if (empty($sublink['visible']) &&
                            (empty($link['ismodlink']) || empty($link['isactive'])) ) {
                            unset($link['menulinks'][$subid]);
                            continue;
                        }
                        // handle links not yet using encode/decode settings
                        if (!isset($sublink['encodedurl'])) {
                            $subcheck = self::_decodeURL($sublink['url'], true);
                            foreach ($subcheck as $k => $v) {
                                $sublink[$k] = $v;
                            }
                        }
                        if (self::$currenturl == $sublink['url']) {
                            $sublink['url'] = '';
                            $sublink['isactive'] = 1;
                        } elseif (empty($link['ismodlink'])) {
                            $sublink['isactive'] = 0;
                        }
                        if (!empty($sublink['isactive']) && empty($link['isactive'])) {
                            $link['isactive'] = 1;
                        }
                        $link['menulinks'][$subid] = $sublink;
                    }
                }
                $userlinks[] = $link;
            }
        }

        return $userlinks;
    }


    public function help()
    {
        return $this->getContent();
    }
}
?>