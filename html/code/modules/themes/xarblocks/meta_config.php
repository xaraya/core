<?php
/**
 * Meta Block configuration interface
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
 * Manage block config
 *
 * @author  John Cox
 * @author  Carl Corliss
 * @access  public
 * @return  void
*/
sys::import('modules.themes.xarblocks.meta');
sys::import('modules.themes.class.xarmeta');
class Themes_MetaBlockConfig extends Themes_MetaBlock
{
	/**
     * Initialize the configuration
     *
     * This method is called by the BasicBlock class constructor
     */
    public function init() 
    {
        parent::init();
    }
	
	/**
	 * Modify Function to the Blocks Admin
	 * @param $data array containing title,content
	 */
    public function configmodify()
    {
        $data = $this->getContent();

        // populate meta tag dropdowns (new format)
        $data['metatypes'] = xarMeta::getTypes();
        $data['metadirs'] = xarMeta::getDirs();
        $data['metalangs'] = xarMeta::getLanguages();
        return $data;
    }

	/**
	 * Updates the Block config from the Blocks Admin
	 * @param $data array containing title,content
	 */
    public function configupdate()
    {
        // FIXME: use better validation on these parameters.
        $vars = array();

        // fetch the array of meta tags from input
        if (!xarVar::fetch('metatags', 'array', $metatags, array(), xarVar::NOT_REQUIRED)) return;
        $newtags = array();     
        foreach ($metatags as $metatag) {
            // empty value = delete
            if (empty($metatag['value'])) continue;
            // @todo: validation on other params
            $newtags[] = $metatag;
        }
        // fetch the value of the new tag (if any)
        if (!xarVar::fetch('metatypeval', 'pre:trim:lower:str:1:', $metatypeval, '', xarVar::NOT_REQUIRED)) return;
        // only fetch the other params if we have a value        
        if (!empty($metatypeval)) {
            if (!xarVar::fetch('metatype', 'pre:trim:lower:enum:name:http-equiv', $metatype, '', xarVar::NOT_REQUIRED)) return;
            if (!xarVar::fetch('metalang', 'pre:trim:lower:str:1:', $metalang, '', xarVar::NOT_REQUIRED)) return;
            if (!xarVar::fetch('metadir', 'pre:trim:lower:enum:ltr:rtl', $metadir, '', xarVar::NOT_REQUIRED)) return;
            if (!xarVar::fetch('metascheme', 'pre:trim:str:1:', $metascheme, '', xarVar::NOT_REQUIRED)) return;
            if (!xarVar::fetch('metacontent', 'pre:trim:str:1:', $metacontent, '', xarVar::NOT_REQUIRED)) return;
            if (!empty($metatype)) {
                $newtags[] = array(
                    'type' => $metatype,
                    'value' => $metatypeval,
                    'content' => $metacontent,
                    'lang' => $metalang,
                    'dir' => $metadir,
                    'scheme' => $metascheme,
                );
            }
        } 
        $vars['metatags'] = $newtags;
        // store the tags for use by the xarMeta class 
        xarModVars::set('themes','meta.tags', serialize($newtags));
        
        // fetch the array of link tags from input
        if (!xarVar::fetch('linktags', 'array', $linktags, array(), xarVar::NOT_REQUIRED)) return;
        $newlinks = array();
        foreach ($linktags as $linktag) {
            // delete if flag is set not empty
            if (isset($linktag['delete']) && !empty($linktag['delete'])) continue;
            $newlinks[] = $linktag;
        }
        // fetch the value of the new link rel
        if (!xarVar::fetch('linkrel', 'pre:trim:str:1:', $linkrel, '', xarVar::NOT_REQUIRED)) return;
        // only fetch other params if rel isn't empty
        if (!empty($linkrel)) {
            if (!xarVar::fetch('linkhref', 'pre:trim:str:1:', $linkhref, '', xarVar::NOT_REQUIRED)) return;
            if (!xarVar::fetch('linktitle', 'pre:trim:str:1:', $linktitle, '', xarVar::NOT_REQUIRED)) return;
            if (!xarVar::fetch('linktype', 'pre:trim:str:1:', $linktype, '', xarVar::NOT_REQUIRED)) return;
            $newlinks[] = array(
                'rel' => $linkrel,
                'href' => $linkhref,
                'title' => $linktitle,
                'type' => $linktype,
            );
        }
        $vars['linktags'] = $newlinks;
        
        $this->setContent($vars);
        return true;
    }

}
