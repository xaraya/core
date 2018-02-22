<?php
/**
 * Meta Block
 *
 * @package modules\themes
 * @subpackage themes
 * @copyright see the html/credits.html file in this release
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://xaraya.info/index.php/release/70.html
 */
/**
 * Initialise block info
 *
 * @author  John Cox
 * @author  Carl Corliss
 * @access  public
 * @return  void
*/
sys::import('xaraya.structures.containers.blocks.basicblock');

/**
 * Themes Meta Block
 */
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
    
	/**
     * Initialize the block
     *
     * This method is called by the BasicBlock class constructor
     * @param void N/A
     */
    public function init() 
    {
        parent::init();
        if (empty($this->metatags))
            $this->metatags = $this->default_metatags();
        if (empty($this->linktags))
            $this->linktags = $this->default_linktags();
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
                $metatags[] = array(
                    'type' => 'name',
                    'value' => 'viewport',
                    'content' => 'width=device-width, initial-scale=1.0',
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

	/**
	 * Method to parse the linktags
	 *
	 * @return array Returns linktags array
	 */
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
    
	/**
     * Method to decode urls
     * 
     * @param string $url Url string to decode
     * @return string[]|string Returns either decoded url as a string or parts array
     */
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
    
	/**
	 * Method to display the default metatags
	 * 
     * @return array Returns array of metatags
	 */
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
        $metatags[] = array(
            'type' => 'name',
            'value' => 'viewport',
            'content' => 'width=device-width, initial-scale=1.0',
            'lang' => '',
            'dir' => '',
            'scheme' => '',
        );
        return $metatags;
    }
    
	/**
	 * Method to display the default linktags
	 * 
     * @return array Returns array of linktags
	 */
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

}
?>