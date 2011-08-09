<?php
/**
 *  Initialise meta block
 * @package modules
 * @subpackage themes module
 * @category Xaraya Web Applications Framework
 * @version 2.3.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 * @link http://xaraya.com/index.php/release/70.html
 */
/**
 * initialise block
 * @author  John Cox
 * @author  Carl Corliss
 * @access  public
 * @return  void
*/

sys::import('xaraya.structures.containers.blocks.basicblock');

class Themes_MetaBlock extends BasicBlock
{
    protected $type                = 'meta';
    protected $module              = 'themes';
    protected $text_type           = 'Meta';
    protected $text_type_long      = 'Meta Keywords';
    protected $xarversion          = '2.2.1';
    protected $show_preview        = false;
    protected $show_help           = true;
        
    // meta data now stored as array as of 2.2.0
    public $metatags            = array();
    // link data now stored as array as of 2.2.1
    public $linktags            = array();
    
    public function init() 
    {
        parent::init();
        if (empty($this->metatags))
            $this->metatags = $this->default_metatags();
        if (empty($this->linktags))
            $this->linktags = $this->default_linktags();
    }
/**
 * Display func.
 * @param $data array containing title,content
 * @todo: add the same functionality for links we now use for metatags
 */
    function display(Array $data=array())
    {
        $data = parent::display($data);
        if (empty($data)) return;

        $meta = !empty($data['content']) ? $data['content'] : array();
        /** support for dynamic description and dynamic keywords is now
         *  supplied by the xar:meta tag, and not hardcoded here. It is no longer
         *  limited to use by the keywords and articles module, and can be utilised
         *  by content authors directly within templates.
         *
         *  To add a description, overwriting any existing one, use
         *  <xar:meta type="name" value="description" content="my description"/>
         *  To append a description, eg adding to the default set in the meta block...
         *  <xar:meta type="name" value="description" content="my description to append" append="true"/>
         *  To add keywords, overwriting any existing ones
         *  <xar:meta type="name" value="keywords" content="my, keywords, to, use"/>
         *  To append keywords, eg adding to those already set in the meta block...
         *  <xar:meta type="name" value="keywords" content="my, keywords, to, append" append="true"/>
        **/

        // By the time we get here, the stored metatags will already be queued
        // So we just need to add any tags with dynamic values, in this case
        // the equiv meta tag now sets text/html as content, but this is 
        // determined by the page template, in our current setup compiled too 
        // late to pull it in here, this is addressed in the tpl_order 
        // scenario, no choice but to leave or delete, leaving it for now 
        sys::import('modules.themes.class.xarmeta');
        $xarmeta = xarMeta::getInstance();
        $xarmeta->register(array(
            'type' => 'http-equiv',
            'value' => 'Content-Type',
            'content' => 'text/html; charset=' . xarMLSGetCharsetFromLocale(xarMLSGetCurrentLocale()),
            'lang' => '',
            'dir' => '',
            'scheme' => '',
        ));
        // while we're here, handle modules setting meta refresh via the cache
        // NOTE: this functionality is deprecated, instead use the xar:meta tag, eg...
        // <xar:meta type="http-equiv" value="refresh" content="3; URL=http://www.example.com"/>
        if (xarVarIsCached('Meta.refresh','url') && xarVarIsCached('Meta.refresh','time')) {
            $xarmeta->register(array(
                'type' => 'http-equiv',
                'value' => 'Refresh',
                'content' => xarVarGetCached('Meta.refresh','time').'; URL='.xarVarGetCached('Meta.refresh','url'),
                'lang' => '',
                'dir' => '',
                'scheme' => '',
            ));
        }

        if (!empty($this->linktags))
            $meta['linktags'] = $this->parseLinkTags();

         //Pager Buttons
        $meta['first']          = xarVarGetCached('Pager.first','leftarrow');
        $meta['last']           = xarVarGetCached('Pager.last','rightarrow');

        $data['content'] = $meta;
        return $data;

    }
    
    public function upgrade($oldversion)
    {
        // grab existing content
        $data = $this->content;
        switch ($oldversion) {
            case '0.0.0':

                // build metatags array from current settings
                $metatags = array();
                $metatags[] = array(
                    'type' => 'name', 
                    'value' => 'author', 
                    'content' => xarModVars::get('themes', 'SiteName', XARVAR_PREP_FOR_DISPLAY),
                    'lang' => '',
                    'dir' => '',
                    'scheme' => '',
                );                
                $metatags[] = array(
                    'type' => 'name', 
                    'value' => 'description', 
                    'content' => !empty($data['metadescription']) ? $data['metadescription'] : '',
                    'lang' => '',
                    'dir' => '',
                    'scheme' => '',
                );
                $metatags[] = array(
                    'type' => 'name',
                    'value' => 'keywords',
                    'content' => !empty($data['metakeywords']) ? $data['metakeywords'] : '',
                    'lang' => '',
                    'dir' => '',
                    'scheme' => '',
                );
                $metatags[] = array(
                    'type' => 'name',
                    'value' => 'generator',
                    'content' => xarConfigVars::get(null, 'System.Core.VersionId') . ' :: ' . xarConfigVars::get(null, 'System.Core.VersionNum'),
                    'lang' => '',
                    'dir' => '',
                    'scheme' => '',
                );
                $metatags[] = array(
                    'type' => 'name',
                    'value' => 'rating',
                    'content' => xarML('General'),
                    'lang' => '',
                    'dir' => '',
                    'scheme' => '',
                );
                if (!empty($data['usegeo'])) {
                    if (!empty($data['latitude']) && !empty($data['longitude']))
                        $content = $data['latitude'] . ', ' . $data['longitude'];
                    $metatags[] = array(
                        'type' => 'name',
                        'value' => 'ICBM',
                        'content' => !empty($content) ? $content : '',
                        'lang' => '',
                        'dir' => '',
                        'scheme' => '',
                    );
                    $metatags[] = array(
                        'type' => 'name',
                        'value' => 'DC.title',
                        'content' => xarModVars::get('themes', 'SiteName', XARVAR_PREP_FOR_DISPLAY),
                        'lang' => '',
                        'dir' => '',
                        'scheme' => '',
                    );
                }
                // unset deprecated property values
                if (isset($data['metadescription'])) unset($data['metadescription']);
                if (isset($data['metakeywords'])) unset($data['metakeywords']);
                if (isset($data['usegeo'])) unset($data['usegeo']);
                if (isset($data['latitude'])) unset($data['latitude']);
                if (isset($data['longitude'])) unset($data['longitude']);
                if (isset($data['usedk'])) unset($data['usedk']);
                
                $data['metatags'] = $this->metatags = $metatags;
                // set the modvar to make tags available to xarMeta class early
                xarModVars::set('themes','meta.tags', serialize($metatags));

            case '2.2.0':
                // upgrades from 2.2.0 go here...
                $linktags = array(
                    array('rel' => 'author', 'href' => !empty($data['authorpage']) ? $data['authorpage'] : '[baseurl]', 'title' => xarModVars::get('themes', 'SiteName', XARVAR_PREP_FOR_DISPLAY), 'type' => 'text/html'),
                    array('rel' => 'copyright', 'href' => !empty($data['copyrightpage']) ? $data['copyrightpage'] : '', 'title' => '', 'type' => 'text/html'),
                    array('rel' => 'help', 'href' => !empty($data['helppage']) ? $data['helppage'] : '' , 'title' => '', 'type' => 'text/html'),
                    array('rel' => 'glossary', 'href' => !empty($data['glossary']) ? $data['glossary'] : '', 'title' => '', 'type' => 'text/html'),                                                    
                    array('rel' => 'pingback', 'href' => '[baseurl]ws.php', 'title' => '', 'type' => ''),
                    array('rel' => 'Top', 'href' => '[baseurl]', 'title' => '', 'type' => 'text/html'),
                    array('rel' => 'parent', 'href' => '[baseurl]', 'title' => '', 'type' => 'text/html'),            
                    array('rel' => 'contents', 'href' => '[articles:user:viewmap]', 'title' => '', 'type' => 'text/html'),
                    array('rel' => 'search', 'href' => '[search:user:main]', 'title' => '', 'type' => 'text/html'),
                    array('rel' => 'alternate', 'href' => '[currenturl]theme=rss', 'title' => 'RSS-feed', 'type' => 'application/rss+xml'),
                    array('rel' => 'service.feed', 'href' => '[currenturl]theme=atom', 'title' => 'Atom-feed', 'type' => 'application/atom+xml'),
                    array('rel' => 'alternate', 'href' => '[currenturl]theme=print', 'title' => 'Print', 'type' => 'text/html'),
                );
                // remove deprecated property values
                if (isset($data['authorpage'])) unset($data['authorpage']);
                if (isset($data['copyrightpage'])) unset($data['copyrightpage']);
                if (isset($data['helppage'])) unset($data['helppage']);
                if (isset($data['glossary'])) unset($data['glossary']);

                $data['linktags'] = $this->linktags = $linktags;
            case '2.2.1':
                // upgrades from 2.2.1 go here...
                
            break;
        }
        // replace content with updated array 
        $this->content = $data;
        
        return true;
    }

    public function parseLinkTags()
    {
        $linktags = array();
        if (!empty($this->linktags)) {
            foreach ($this->linktags as $tag) {
                // skip tags with empty rel or href attributes
                if (empty($tag['rel']) || empty($tag['href'])) continue;
                if (!$tag['url'] = $this->_decodeURL($tag['href'])) continue;
                $linktags[] = $tag;
            }
        }
        return $linktags;
    }    
    
    public function _decodeURL($url)
    {
        $url = preg_replace('/&amp;/','&', $url);
        $args = array();
        $qstring = '';

        if (strpos($url, '[') === 0) {
            // Generic module url shortcut syntax [module:type:func]&param=val
            // Credit to Elek M?ton for further expansion

            $sections = explode(']',substr($url,1));
            $modinfo = explode(':', $sections[0]);
            $modname = $modinfo[0];
            if ($modname != 'baseurl' && $modname != 'currenturl') {
                if (!xarMod::isAvailable($modname)) return;
                $modtype = !empty($modinfo[1]) ? $modinfo[1] : 'user';
                $funcname = !empty($modinfo[2]) ? $modinfo[2] : 'main';
            }
            
            // parse optional args/query string
            if (!empty($sections[1])) {
                if ($modname == 'baseurl') {
                    if (preg_match('!^(&|\?|/)!',$sections[1])) {
                        $qstring = substr($sections[1], 1);
                    } else {
                        $qstring = $sections[1];
                    }
                } else {
                    $pairs = $sections[1];
                    if (preg_match('/^(&|\?)/',$pairs)) {
                        $pairs = substr($pairs, 1);
                    }
                    $pairs = explode('&', $pairs);
                    foreach ($pairs as $pair) {
                        $params = explode('=', $pair);
                        $key = $params[0];
                        $val = isset($params[1]) ? $params[1] : null;
                        if ($key == 'theme' && !empty($val) && !xarThemeIsAvailable($val)) return;
                        $args[$key] = $val;
                    }
                }
            }
            
            switch ($modname) {
                case 'baseurl':
                    $decoded_url = xarServer::getBaseURL() . $qstring;
                break;
                case 'currenturl':
                    $decoded_url = xarServer::getCurrentURL($args);
                break;
                default:
                    $decoded_url = xarModURL($modname, $modtype, $funcname, $args);
                break;
            }            
        } else {
            // regular url, prepped for xml display if necessary
            $decoded_url = xarMod::$genXmlUrls ? xarVarPrepForDisplay($url) : $url;
        } 

        return $decoded_url;
    }
    
    public function default_metatags()
    {
        // metatags array
        $metatags = array();
        $metatags[] = array(
            'type' => 'name', 
            'value' => 'author', 
            'content' => xarModVars::get('themes', 'SiteName', XARVAR_PREP_FOR_DISPLAY),
            'lang' => '',
            'dir' => '',
            'scheme' => '',
        );        
        $metatags[] = array(
            'type' => 'name', 
            'value' => 'description', 
            'content' => '',
            'lang' => '',
            'dir' => '',
            'scheme' => '',
        );
        $metatags[] = array(
            'type' => 'name',
            'value' => 'keywords',
            'content' => '',
            'lang' => '',
            'dir' => '',
            'scheme' => '',
        );
        $metatags[] = array(
            'type' => 'name',
            'value' => 'generator',
            'content' => xarConfigVars::get(null, 'System.Core.VersionId') . ' :: ' . xarConfigVars::get(null, 'System.Core.VersionNum'),
            'lang' => '',
            'dir' => '',
            'scheme' => '',
        );
        $metatags[] = array(
            'type' => 'name',
            'value' => 'rating',
            'content' => xarML('General'),
            'lang' => '',
            'dir' => '',
            'scheme' => '',
        );
        return $metatags;
    }
    
    public function default_linktags()
    {
        $linktags = array(
            array('rel' => 'author', 'href' => '[baseurl]', 'title' => xarModVars::get('themes', 'SiteName', XARVAR_PREP_FOR_DISPLAY), 'type' => 'text/html'),
            array('rel' => 'copyright', 'href' => '', 'title' => '', 'type' => 'text/html'),
            array('rel' => 'help', 'href' => '' , 'title' => '', 'type' => 'text/html'),
            array('rel' => 'glossary', 'href' => '', 'title' => '', 'type' => 'text/html'),                            
            array('rel' => 'pingback', 'href' => '[baseurl]ws.php', 'title' => '', 'type' => ''),
            array('rel' => 'Top', 'href' => '[baseurl]', 'title' => '', 'type' => 'text/html'),
            array('rel' => 'parent', 'href' => '[baseurl]', 'title' => '', 'type' => 'text/html'),            
            array('rel' => 'contents', 'href' => '[articles:user:viewmap]', 'title' => '', 'type' => 'text/html'),
            array('rel' => 'search', 'href' => '[search:user:main]', 'title' => '', 'type' => 'text/html'),
            array('rel' => 'alternate', 'href' => '[currenturl]theme=rss', 'title' => 'RSS-feed', 'type' => 'application/rss+xml'),
            array('rel' => 'service.feed', 'href' => '[currenturl]theme=atom', 'title' => 'Atom-feed', 'type' => 'application/atom+xml'),
            array('rel' => 'alternate', 'href' => '[currenturl]theme=print', 'title' => 'Print', 'type' => 'text/html'),
        );
        return $linktags;   
    }

    public function help()
    {
        return $this->getInfo();
    }
}
?>