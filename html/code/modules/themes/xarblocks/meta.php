<?php
/**
 *  Initialise meta block
 * @package modules
 * @subpackage themes module
 * @category Xaraya Web Applications Framework
 * @version 2.2.0
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
    public $name                = 'MetaBlock';
    public $module              = 'themes';
    public $text_type           = 'Meta';
    public $text_type_long      = 'Meta Keywords';
    public $xarversion          = '2.2.0';
    public $show_preview        = true;
    public $usershared          = true;
    public $pageshared          = false;
    
    // meta data now stored as array as of 2.2.0
    public $metatags            = array();

    public $copyrightpage       = '';
    public $helppage            = '';
    public $glossary            = '';
    public $authorpage          = '';

/**
 * Display func.
 * @param $data array containing title,content
 * @todo: add the same functionality for links we now use for metatags
 */
    function display(Array $data=array())
    {
        $data = parent::display($data);
        if (empty($data)) return;

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
       
        $meta = array();

        // Active Page
        $meta['activepagerss'] = xarServer::getCurrentURL(array('theme' => 'rss'));
        $meta['activepageatom'] = xarServer::getCurrentURL(array('theme' => 'atom'));
        $meta['activepageprint'] = xarServer::getCurrentURL(array('theme' => 'print'));

        $meta['baseurl'] = xarServer::getBaseURL();
        if (isset($data['copyrightpage'])){
            $meta['copyrightpage'] = $data['copyrightpage'];
        } else {
            $meta['copyrightpage'] = '';
        }

        if (isset($data['helppage'])){
            $meta['helppage'] = $data['helppage'];
        } else {
            $meta['helppage'] = '';
        }

        if (isset($data['glossary'])){
            $meta['glossary'] = $data['glossary'];
        } else {
            $meta['glossary'] = '';
        }
        if (!empty($data['authorpage'])) {
            $meta['authorpage'] = $data['authorpage'];
        } else {
            $meta['authorpage'] = $meta['baseurl'];
        }

         //Pager Buttons
        $meta['first']          = xarVarGetCached('Pager.first','leftarrow');
        $meta['last']           = xarVarGetCached('Pager.last','rightarrow');

        $data['content'] = $meta;
        return $data;

    }
    
    public function upgrade($oldversion)
    {
        switch ($oldversion) {
            case '0.0.0':
                // grab existing content
                $data = $this->content;
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
                // replace content with updated array 
                $this->content = $data;

            case '2.2.0':
                // upgrades from 2.2.0 go here...            
            break;
        }
        return true;
    }
}
?>
