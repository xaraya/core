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
    public $xarversion          = '2.2.0';
    public $allow_multiple      = true;
    public $show_preview        = true;
    public $nocache             = 1;
    public $pageshared          = 0;
    public $usershared          = 1;

    public $marker              = '[x]';
    public $showlogout          = true;
    public $logoutlabel         = 'Logout';
    public $logouttitle         = 'Logout from the site';
    public $showback            = true;
    public $backlabel           = 'View Back End';
    public $backtitle           = 'View the site back end interface';
    public $displayrss          = false;
    public $rsslabel            = 'Syndication';
    public $rsstitle            = 'Syndicate this content';
    public $displayprint        = false;
    public $printlabel          = 'Print View';
    public $printtitle           = 'Printer friendly view of this page';

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
        // upgrades are now in the upgrade() method below (called in parent constructor :) )
        parent::__construct($data);
        // make sure we keep the content array in sync
        $this->content['modulelist'] = $this->modulelist;
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
        $vars['modlinks'] = $modlinks;

        // no links, nothing to display
        if (
            empty($vars['modlinks']) &&
            empty($vars['userlinks']) &&
            empty($vars['showlogout']) &&
            empty($vars['showback']) &&
            empty($vars['displayprint']) &&
            empty($vars['displayrss'])
        ) return;

        // pass through the current request info
        $vars['thismodname'] = self::$thismodname;
        $vars['thismodtype'] = self::$thismodtype;
        $vars['thisfuncname'] = self::$thisfuncname;

        if (!empty($vars['displayrss']) && !xarThemeIsAvailable('rss')) $vars['displayrss'] = 0;
        if (!empty($vars['displayprint']) && !xarThemeIsAvailable('print')) $vars['displayprint'] = 0;

        $data['content'] = $vars;

        return $data;
    }
/**
 * This method is called by the BasicBlock class constructor
**/
    public function upgrade($oldversion) 
    {
        switch ($oldversion) {
            case '0.0.0': // upgrade menu blocks to version 2.2.0
                // convert the old modulelist string to an array
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
                }
                if (isset($this->content['displaymodules'])) {
                    $this->modulelist = array();
                    foreach ($this->xarmodules as $key => $mod) {
                        $modname = $mod['name'];
                        if ($this->content['displaymodules'] == 'All' || isset($modulelist[$modname])) {
                            $this->modulelist[$modname]['visible'] = 1;
                        } else {
                            $this->modulelist[$modname]['visible'] = 0;
                        }
                    }
                    unset($this->content['displaymodules']);
                }

                // convert the old user_content/lines to userlinks array
                if (!empty($this->content['lines'])) {
                    $userlinks = array();
                    foreach ($this->content['lines'] as $id => $line) {
                        $userlinks[] = array(
                            'id' => $id,
                            'name' => $line['name'],
//                            'label' => $line['label'],
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
                // remove any other deprecated properties
                if (isset($this->content['user_content'])) unset($this->content['user_content']);
                if (isset($this->content['rssurl'])) unset($this->content['rssurl']);
                if (isset($this->content['printurl'])) unset($this->content['printurl']);

                // Add new properties to the content array
                if (!isset($this->content['backlabel'])) $this->content['backlabel'] = xarML($this->backlabel);
                if (!isset($this->content['backtitle'])) $this->content['backtitle'] = xarML($this->backtitle);
                if (!isset($this->content['logoutlabel'])) $this->content['logoutlabel'] = xarML($this->logoutlabel);
                if (!isset($this->content['logouttitle'])) $this->content['logouttitle'] = xarML($this->logouttitle);
                if (!isset($this->content['rsslabel'])) $this->content['rsslabel'] = xarML($this->rsslabel);
                if (!isset($this->content['rsstitle'])) $this->content['rsstitle'] = xarML($this->rsstitle);
                if (!isset($this->content['printlabel'])) $this->content['printlabel'] = xarML($this->printlabel);
                if (!isset($this->content['printtitle'])) $this->content['printtitle'] = xarML($this->printtitle);

            // fall through to next upgrade...
            case '2.2.0': // upgrade from 2.2.0 comes here

            break;
        }
        return true;
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
/**
 * Decode urls
**/
    protected function _decodeURL($url, $infoarray=false)
    {
        $url = preg_replace('/&amp;/','&', $url);
        $args = array();

        if (strpos($url, '[') === 0) {
            // Generic module url shortcut syntax [module:type:func]&param=val
            // Credit to Elek M?ton for further expansion

            $sections = explode(']',substr($url,1));
            $modinfo = explode(':', $sections[0]);
            $modname = $modinfo[0];
            $modtype = !empty($modinfo[1]) ? $modinfo[1] : 'user';
            $funcname = !empty($modinfo[2]) ? $modinfo[2] : 'main';

            // urls specified as [module] or [module:type] with no params
            $ismodlink = (empty($modinfo[2]) && empty($sections[1]));
            // url has params or was specified as [module:type:func]
            if (!$ismodlink && !empty($sections[1])) {
                $pairs = $sections[1];
                if (preg_match('/^(&|\?)/',$pairs)) {
                    $pairs = substr($pairs, 1);
                }
                $pairs = explode('&', $pairs);
                foreach ($pairs as $pair) {
                    $params = explode('=', $pair);
                    $key = $params[0];
                    $val = isset($params[1]) ? $params[1] : null;
                    $args[$key] = $val;
                }
            }
            $decoded_url = xarModURL($modname, $modtype, $funcname, $args);

        } elseif (xarMod::$genXmlUrls) {
            // regular url, prepped for xml display if necessary
            $decoded_url = xarVarPrepForDisplay($url);
        }

        // pass details of decode to calling function,
        // used by Base_MenuBlockAdmin::update() method
        if ($infoarray) {
            return array(
                'modname' => isset($modinfo[0]) ? $modinfo[0] : !empty($modname) ? $modname : '',
                'modtype' => isset($modinfo[1]) ? $modinfo[1] : !empty($modtype) ? $modtype : '',
                'funcname' => isset($modinfo[2]) ? $modinfo[2] : !empty($funcname) ? $funcname : '',
                'modparams' => $args,
                'encodedurl' => $url,
                'url' => $decoded_url,
                'ismodlink' => !empty($ismodlink),
            );
        }

        // pass the decoded_url to the calling function
        return $decoded_url;

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
