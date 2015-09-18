<?php
/**
 * Meta Block configuration interface
 *
 * @package modules
 * @subpackage themes module
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.info
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
        if (!xarVarFetch('metatags', 'array', $metatags, array(), XARVAR_NOT_REQUIRED)) return;
        $newtags = array();     
        foreach ($metatags as $metatag) {
            // empty value = delete
            if (empty($metatag['value'])) continue;
            // @todo: validation on other params
            $newtags[] = $metatag;
        }
        // fetch the value of the new tag (if any)
        if (!xarVarFetch('metatypeval', 'pre:trim:lower:str:1:', $metatypeval, '', XARVAR_NOT_REQUIRED)) return;
        // only fetch the other params if we have a value        
        if (!empty($metatypeval)) {
            if (!xarVarFetch('metatype', 'pre:trim:lower:enum:name:http-equiv', $metatype, '', XARVAR_NOT_REQUIRED)) return;
            if (!xarVarFetch('metalang', 'pre:trim:lower:str:1:', $metalang, '', XARVAR_NOT_REQUIRED)) return;
            if (!xarVarFetch('metadir', 'pre:trim:lower:enum:ltr:rtl', $metadir, '', XARVAR_NOT_REQUIRED)) return;
            if (!xarVarFetch('metascheme', 'pre:trim:str:1:', $metascheme, '', XARVAR_NOT_REQUIRED)) return;
            if (!xarVarFetch('metacontent', 'pre:trim:str:1:', $metacontent, '', XARVAR_NOT_REQUIRED)) return;
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
        if (!xarVarFetch('linktags', 'array', $linktags, array(), XARVAR_NOT_REQUIRED)) return;
        $newlinks = array();
        foreach ($linktags as $linktag) {
            // delete if flag is set not empty
            if (isset($linktag['delete']) && !empty($linktag['delete'])) continue;
            $newlinks[] = $linktag;
        }
        // fetch the value of the new link rel
        if (!xarVarFetch('linkrel', 'pre:trim:str:1:', $linkrel, '', XARVAR_NOT_REQUIRED)) return;
        // only fetch other params if rel isn't empty
        if (!empty($linkrel)) {
            if (!xarVarFetch('linkhref', 'pre:trim:str:1:', $linkhref, '', XARVAR_NOT_REQUIRED)) return;
            if (!xarVarFetch('linktitle', 'pre:trim:str:1:', $linktitle, '', XARVAR_NOT_REQUIRED)) return;
            if (!xarVarFetch('linktype', 'pre:trim:str:1:', $linktype, '', XARVAR_NOT_REQUIRED)) return;
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
?>
