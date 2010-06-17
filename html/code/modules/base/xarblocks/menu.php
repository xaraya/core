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
 * initialise block
 *
 * @author  John Cox <admin@dinerminor.com>
 * @access  public
 * @param   none
 * @return  nothing
 * @throws  no exceptions
 * @todo    nothing
*/
// Inherit properties and methods from MenuBlock class
sys::import('xaraya.structures.containers.blocks.menublock');

class Base_MenuBlock extends MenuBlock implements iBlock
{
    public $name                = 'MenuBlock';
    public $module              = 'base';
    public $text_type           = 'Menu';
    public $text_type_long      = 'Generic menu';
    public $allow_multiple      = true;
    public $show_preview        = true;
    public $nocache             = 1;
    public $pageshared          = 0;
    public $usershared          = 1;

    public $marker              = '[x]';
    public $showlogout          = true;
    public $showback            = true;
    public $displayrss          = false;
    public $rssurl;
    public $displayprint        = false;
    public $printurl;

    public $userlinks           = array();
    public $links_default       = array(
                                    array(
                                        'id' => 0,
                                        'name' => 'Documentation',
                                        'url' => '[base]&page=docs',
                                        'label'=> 'Documentation',
                                        'title' => 'General Documentation',
                                        'visible' => 1,
                                        'menulinks' => array(),
                                    ),
                                  );

    public $menumodtype         = 'user';

    public function __construct(Array $data=array())
    {
        $data['rssurl'] = xarServer::getCurrentURL(array('theme' => 'rss'));
        $data['printurl'] = xarServer::getCurrentURL(array('theme' => 'print'));
        parent::__construct($data);

        // convert the old modulelist string to an array, one time deal coming from < 2.2.0
        if (!empty($this->modulelist) && !is_array($this->modulelist)) {
            $oldlist = @explode(',', $this->modulelist);
            $modulelist = array();
            if (is_array($oldlist)) {
                foreach ($oldlist as $modname) {
                    $modname = trim($modname);
                    $modulelist[$modname] = 1;
                }
            }
            unset($oldlist);
            $this->modulelist = array();
        }
        if (isset($this->content['displaymodules'])) {
            foreach ($this->xarmodules as $key => $mod) {
                $modname = $mod['name'];
                // convert the old modulelist, one time deal coming from < 2.2.0
                if ($this->content['displaymodules'] == 'All' || isset($modulelist[$modname])) {
                    $this->modulelist[$modname]['visible'] = 1;
                } else {
                    $this->modulelist[$modname]['visible'] = 0;
                }
            }
            unset($this->content['displaymodules']);
        }
        // make sure we keep the content array in sync
        $this->content['modulelist'] = $this->modulelist;

        // convert the old user_content/lines to userlinks array
        if (!empty($this->content['lines'])) {
            $userlinks = array();
            foreach ($this->content['lines'] as $id => $line) {
                $userlinks[] = array(
                    'id' => $id,
                    'name' => $line['label'],
                    'label' => $line['label'],
                    'title' => $line['description'],
                    'url' => $line['url'],
                    'visible' => $line['visible'],
                    'menulinks' => array(),
                    'isactive' => 0,
                );
            }
            $this->userlinks = $this->content['userlinks'] = $userlinks;
            unset($this->content['lines']);
        }
        // load the default link if userlinks are empty
        if (empty($this->userlinks))
            $this->userlinks = $this->content['userlinks'] = $this->links_default;
    }

/**
 * Display func.
 * @param $data array containing title,content
 */
    function display(Array $data=array())
    {
        $data = parent::display($data);
        if (empty($data)) return;

        $vars = !empty($data['content']) ? $data['content'] : array();

        //print_r($this->modulelist);
        if (xarUserIsLoggedIn()) {
            if (!empty($vars['showlogout'])) {
                $authmoduledata = xarMod::apiFunc('roles','user','getdefaultauthdata');
                $authmodlogout = $authmoduledata['defaultloginmodname'];
                if (xarSecurityCheck('AdminBase',0)) {
                    $vars['logouturl'] = xarModURL('base', 'admin', 'confirmlogout');
                } else {
                    $vars['logouturl'] = xarModURL($authmodlogout,'user', 'logout', array());
                    $vars['showback'] = 0;
                }
                $vars['logoutlabel'] = xarVarPrepForDisplay(xarML('Logout'));
            }
            $vars['loggedin'] = 1;
        } else {
            $vars['showlogout'] = 0;
            $vars['showback'] = 0;
            $vars['loggedin'] = 0;
        }

        // get userlinks using dedicated method
        $vars['userlinks'] = self::getUserLinks();

        // Handle modulelist
        $accessproperty = DataPropertyMaster::getProperty(array('name' => 'access'));
        $modlinks = array();
        foreach ($this->xarmodules as $mod) {
            $modname = $mod['name'];
            if (!empty($vars['modulelist'][$modname]['view_access'])) {
                // Decide whether this menu item is displayable to the current user
                $args = array(
                    'module' => 'base',
                    'component' => 'Block',
                    'instance' => $data['title'] . "All:All",
                    'group' => $vars['modulelist'][$modname]['view_access']['group'],
                    'level' => $vars['modulelist'][$modname]['view_access']['level'],
                );
                if (!$accessproperty->check($args)) continue;
            }
            // @TODO: deprecate this
            if ((bool)xarModVars::get($modname, $this->menumodtype . '_menu_link')) continue;
            // Use this instead :)
            if (empty($vars['modulelist'][$modname]['visible'])) continue;

            if (!empty($vars['modulelist'][$modname]['alias_name'])) {
                $displayname = $vars['modulelist'][$modname]['alias_name'];
                if (empty($mod['aliases']) || !isset($mod['aliases'][$displayname])) {
                    $displayname = $mod['displayname'];
                }
            } else {
                $displayname = $mod['displayname'];
            }

            // get menu links if module is active
            if ($modname == self::$thismodname && (self::$thismodtype == $this->menumodtype || !empty($this->menumodtypes) && in_array(self::$thismodtype, $this->menumodtypes)) ) {
                $menulinks = xarMod::apiFunc('base', 'admin', 'loadmenuarray',
                    array(
                        'modname' => $modname,
                        'modtype' => $this->menumodtype, // make sure we get user menu links
                    ));
                $isactive = true;
            } else {
                $menulinks = array();
                $isactive = false;
            }
            $modurl = xarModURL($modname, $this->menumodtype, 'main', array());
            $modlinks[$modname] = array(
                'label' => $displayname,
                'title' => $mod['description'],
                'url' => $modurl == self::$currenturl ? '' : $modurl,
                'isactive' => $isactive,
                'menulinks' => $menulinks,
            );
        }
        $vars['modlinks'] = $modlinks;

        // no links, nothing to display
        if (empty($vars['modlinks']) && empty($vars['userlinks'])) return;

        $vars['thismodname'] = self::$thismodname;

        $data['content'] = $vars;

        return $data;
    }

    public function getUserLinks()
    {
        $userlinks = array();

        if (!empty($this->userlinks)) {
            foreach ($this->userlinks as $id => $link) {
                if (empty($link['visible'])) continue;
                if (!empty($link['url'])) {
                    $link['url'] = self::_decodeURL($link['url']);
                }
                if (self::$currenturl == $link['url']) {
                    $link['url'] = '';
                    $link['isactive'] = 1;
                } else {
                    $link['isactive'] = 0;
                }
                if (!empty($link['menulinks'])) {
                    foreach ($link['menulinks'] as $subid => $sublink) {
                        if (empty($sublink['visible'])) {
                            unset($link['menulinks'][$subid]);
                            continue;
                        }
                        if (!empty($sublink['url'])) {
                            $sublink['url'] = self::_decodeURL($sublink['url']);
                        }
                        if (self::$currenturl == $sublink['url']) {
                            $sublink['url'] = '';
                            $sublink['isactive'] = 1;
                            $link['isactive'] = 1;
                        } else {
                            $sublink['isactive'] = 0;
                        }
                        $link['menulinks'][$subid] = $sublink;
                    }
                }
                $userlinks[] = $link;
            }
        }

        return $userlinks;
    }
/**
 * Decode urls
**/
    private function _decodeURL($url)
    {
        $url = preg_replace('/&amp;/','&', $url);

        if (strpos($url, '[') === 0) {
            // Generic module url shortcut syntax [module:type:func]&param=val
            // Credit to Elek M?ton for further expansion

            $sections = explode(']',substr($url,1));
            $modinfo = explode(':', $sections[0]);
            $modname = $modinfo[0];
            $modtype = !empty($modinfo[1]) ? $modinfo[1] : 'user';
            $funcname = !empty($modinfo[2]) ? $modinfo[2] : 'main';
            $args = array();

            if (!empty($sections[1])) {
                $pairs = $sections[1];
                if (preg_match('/^(&|\?)/',$pairs)) {
                    $pairs = substr($pairs, 1);
                }
                $pairs = explode('&', $pairs);
                foreach ($pairs as $pair) {
                    $params = explode('=', $pair);
                    $key = $params[0];
                    $val = !empty($params[1]) ? $params[1] : null;
                    $args[$key] = $val;
                }
            }

            $url = xarModURL($modname, $modtype, $funcname, $args);

        } elseif (xarMod::$genXmlUrls) {
            // regular url, prepped for xml display if necessary
            $url = xarVarPrepForDisplay($url);
        }

        return $url;

        /* Deprecated decode functions, left here in case we want to revisit
            case '{': // article link
            {
                $line['url'] = explode(':', substr($line['url'], 1,  - 1));
                // Get current pubtype type (if any)
                if (xarVarIsCached('Blocks.articles', 'ptid')) {
                    $ptid = xarVarGetCached('Blocks.articles', 'ptid');
                }
                if (empty($ptid)) {
                    // try to get ptid from input
                    xarVarFetch('ptid', 'isset', $ptid, NULL, XARVAR_DONT_SET);
                }
                // if the current pubtype is active, then we are here
                if ($line['url'][0] == $ptid) {
                    $here = 'true';
                }
                $line['url'] = xarModUrl('articles', 'user', 'view', array('ptid' => $line['url'][0]));
                break;
            }
            case '(': // category link
            {
                $line['url'] = explode(':', substr($line['url'], 1,  - 1));
                if (xarVarIsCached('Blocks.categories','catid')) {
                    $catid = xarVarGetCached('Blocks.categories','catid');
                }
                if (empty($catid)) {
                    // try to get catid from input
                    xarVarFetch('catid', 'isset', $catid, NULL, XARVAR_DONT_SET);
                }
                if (empty($catid) && xarVarIsCached('Blocks.categories','cids')) {
                    $cids = xarVarGetCached('Blocks.categories','cids');
                } else {
                    $cids = array();
                }
                $catid = str_replace('_', '', $catid);
                $ancestors = xarMod::apiFunc('categories','user','getancestors',
                                          array('cid' => $catid,
                                                'cids' => $cids,
                                                'return_itself' => true));
                if(!empty($ancestors)) {
                    $ancestorcids = array_keys($ancestors);
                    if (in_array($line['url'][0], $ancestorcids)) {
                        // if we are on or below this category, then we are here
                        $here = 'true';
                    }
                }
                $line['url'] = xarModUrl('articles', 'user', 'view', array('catid' => $line['url'][0]));
                break;
            }
        */

    }
}
?>
