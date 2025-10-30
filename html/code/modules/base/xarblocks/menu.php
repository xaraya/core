<?php

/**
 * Menu Block
 *
 * @package modules\base
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://xaraya.info/index.php/release/68.html
 */

/**
 * Initialise block info
 *
 * @author  John Cox <admin@dinerminor.com>
 * @access  public
 * @return  void
*/
// Inherit properties and methods from MenuBlock class
sys::import('xaraya.structures.containers.blocks.menublock');

/**
 * Base Menu Block
 */
class Base_MenuBlock extends MenuBlock implements iBlock
{
    protected $type                = 'menu';
    protected $module              = 'base';
    protected $text_type           = 'Menu';
    protected $text_type_long      = 'Generic menu';
    protected $xarversion          = '2.4.0';
    protected $show_preview        = true;
    protected $show_help           = true;

    protected $menumodtype         = 'user';

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

    public $userlinks           = [];
    public $links_default       = [
        [
            'id' => 0,
            'name' => 'Documentation',
            'url' => '[base]&page=docs',
            'label' => 'Documentation',
            'title' => 'General Documentation',
            'visible' => 0,
            'menulinks' => [],
        ],
        [
            'id' => 1,
            'name' => 'eventsystem',
            'url' => '[base]page=events',
            'label' => 'Event System',
            'title' => 'Event Messaging System Overview',
            'visible' => 0,
            'menulinks' => [],
        ],
    ];


    /**
     * Initialize the block
     *
     * This method is called by the BasicBlock class constructor
     */
    public function init()
    {
        parent::init();
        // load the default link if userlinks are empty
        if (empty($this->userlinks)) {
            $this->userlinks = $this->links_default;
        }
    }

    /**
     * Upgrade the block code<br/>
     * This method is called by the BasicBlock class constructor
     *
     * @param string $oldversion Version to upgrade from (old version)
     * @return boolean Returns true on success, false/null on failure
     */
    public function upgrade($oldversion)
    {
        switch ($oldversion) {
            case '0.0.0': // upgrade menu blocks to version 2.2.0
                // fix for blocks coming from a 1x install
                // @todo: this shouldn't happen, need to figure out why it does
                if (!is_array($this->content)) {
                    $content = @unserialize((string) $this->content);
                    $this->content = !empty($content) && is_array($content) ? $content : [];
                }
                // convert the old modulelist string to an array
                if (!empty($this->modulelist) && !is_array($this->modulelist)) {
                    $oldlist = @explode(',', $this->modulelist);
                    $modulelist = [];
                    if (is_array($oldlist)) {
                        foreach ($oldlist as $modname) {
                            $modname = trim($modname);
                            $modulelist[$modname] = 1;
                        }
                    }
                    unset($oldlist);
                }
                if (isset($this->content['displaymodules'])) {
                    $this->modulelist = [];
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
                    $userlinks = [];
                    foreach ($this->content['lines'] as $id => $line) {
                        if (!isset($line['label'])) {
                            $line['label'] = $line['url'];
                        }
                        $userlinks[] = [
                            'id' => $id,
                            'name' => $line['name'],
                            'label' => $line['label'],
                            'title' => $line['description'],
                            'url' => $line['url'],
                            'visible' => $line['visible'],
                            'menulinks' => [],
                            'isactive' => 0,
                        ];
                    }
                    $this->userlinks = $this->content['userlinks'] = $userlinks;
                    unset($this->content['lines']);
                }
                // remove any other deprecated properties
                if (isset($this->content['user_content'])) {
                    unset($this->content['user_content']);
                }
                if (isset($this->content['rssurl'])) {
                    unset($this->content['rssurl']);
                }
                if (isset($this->content['printurl'])) {
                    unset($this->content['printurl']);
                }

                // Add new properties to the content array
                if (!isset($this->content['backlabel'])) {
                    $this->content['backlabel'] = $this->ml($this->backlabel);
                }
                if (!isset($this->content['backtitle'])) {
                    $this->content['backtitle'] = $this->ml($this->backtitle);
                }
                if (!isset($this->content['logoutlabel'])) {
                    $this->content['logoutlabel'] = $this->ml($this->logoutlabel);
                }
                if (!isset($this->content['logouttitle'])) {
                    $this->content['logouttitle'] = $this->ml($this->logouttitle);
                }
                if (!isset($this->content['rsslabel'])) {
                    $this->content['rsslabel'] = $this->ml($this->rsslabel);
                }
                if (!isset($this->content['rsstitle'])) {
                    $this->content['rsstitle'] = $this->ml($this->rsstitle);
                }
                if (!isset($this->content['printlabel'])) {
                    $this->content['printlabel'] = $this->ml($this->printlabel);
                }
                if (!isset($this->content['printtitle'])) {
                    $this->content['printtitle'] = $this->ml($this->printtitle);
                }

                // fall through to next upgrade...
                // no break
            case '2.2.0': // upgrade from 2.2.0 comes here

                break;
        }
        return true;
    }

    /**
     * Method to decode urls
     *
     * @param string $url Url string to decode
     * @param boolean $infoarray Boolean value to determine wether or not to return
     *                           the decoded url as an array
     * @return string[]|string Returns either decoded url as a string or parts array
     */
    protected function _decodeURL($url, $infoarray = false)
    {
        $url = preg_replace('/&amp;/', '&', $url);
        $args = [];

        if (strpos($url, '[') === 0) {
            // Generic module url shortcut syntax [module:type:func]&param=val
            // Credit to Elek M?ton for further expansion

            $sections = explode(']', substr($url, 1));
            $modinfo = explode(':', $sections[0]);
            $modname = $modinfo[0];
            $modtype = !empty($modinfo[1]) ? $modinfo[1] : 'user';
            $funcname = !empty($modinfo[2]) ? $modinfo[2] : 'main';

            // urls specified as [module] or [module:type] with no params
            $ismodlink = (empty($modinfo[2]) && empty($sections[1]));
            // url has params or was specified as [module:type:func]
            if (!$ismodlink && !empty($sections[1])) {
                $pairs = $sections[1];
                if (preg_match('/^(&|\?)/', $pairs)) {
                    $pairs = substr($pairs, 1);
                }
                $pairs = explode('&', $pairs);
                foreach ($pairs as $pair) {
                    $params = explode('=', $pair);
                    $key = $params[0];
                    $val = $params[1] ?? null;
                    $args[$key] = $val;
                }
            }
            $decoded_url = $this->ctl()->getModuleURL($modname, $modtype, $funcname, $args);

        } elseif (xarMod::$genXmlUrls) {
            // regular url, prepped for xml display if necessary
            $decoded_url = \xarVarPrep::forDisplay($url);
        }

        // pass details of decode to calling function,
        // used by Base_MenuBlockAdmin::update() method
        if ($infoarray) {
            return [
                'modname' => $modinfo[0] ?? (!empty($modname) ? $modname : ''),
                'modtype' => $modinfo[1] ?? (!empty($modtype) ? $modtype : ''),
                'funcname' => $modinfo[2] ?? (!empty($funcname) ? $funcname : ''),
                'modparams' => $args,
                'encodedurl' => $url,
                'url' => $decoded_url,
                'ismodlink' => !empty($ismodlink),
            ];
        }

        // pass the decoded_url to the calling function
        return $decoded_url;

        /* Deprecated decode functions, left here in case we want to revisit
            case '{': // article link
            {
                $line['url'] = explode(':', substr($line['url'], 1,  - 1));
                // Get current pubtype type (if any)
                if ($this->mem()->has('Blocks.articles', 'ptid')) {
                    $ptid = $this->mem()->get('Blocks.articles', 'ptid');
                }
                if (empty($ptid)) {
                    // try to get ptid from input
                    $this->var()->check('ptid', $ptid);
                }
                // if the current pubtype is active, then we are here
                if ($line['url'][0] == $ptid) {
                    $here = 'true';
                }
                $line['url'] = $this->ctl()->getModuleURL('articles', 'user', 'view', array('ptid' => $line['url'][0]));
                break;
            }
            case '(': // category link
            {
                $line['url'] = explode(':', substr($line['url'], 1,  - 1));
                if ($this->mem()->has('Blocks.categories','catid')) {
                    $catid = $this->mem()->get('Blocks.categories','catid');
                }
                if (empty($catid)) {
                    // try to get catid from input
                    $this->var()->check('catid', $catid);
                }
                if (empty($catid) && $this->mem()->has('Blocks.categories','cids')) {
                    $cids = $this->mem()->get('Blocks.categories','cids');
                } else {
                    $cids = array();
                }
                $catid = str_replace('_', '', $catid);
                $ancestors = $this->mod()->apiFunc('categories','user','getancestors',
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
                $line['url'] = $this->ctl()->getModuleURL('articles', 'user', 'view', array('catid' => $line['url'][0]));
                break;
            }
        */

    }
}
