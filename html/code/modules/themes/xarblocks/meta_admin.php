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
sys::import('modules.themes.xarblocks.meta');
sys::import('modules.themes.class.xarmeta');
class Themes_MetaBlockAdmin extends Themes_MetaBlock
{
/**
 * Modify Function to the Blocks Admin
 * @param $data array containing title,content
 */
    public function modify(Array $data=array())
    {
        $data = parent::modify($data);
        $data['blockid'] = $data['bid'];

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
    public function update(Array $data=array())
    {
        $data = parent::update($data);

        // FIXME: use better validation on these parameters.
        $vars = array();

        if (!xarVarFetch('copyrightpage',   'notempty', $vars['copyrightpage'],   $this->copyrightpage, XARVAR_NOT_REQUIRED)) return;
        if (!xarVarFetch('helppage',        'notempty', $vars['helppage'],        $this->helppage, XARVAR_NOT_REQUIRED)) return;
        if (!xarVarFetch('glossary',        'notempty', $vars['glossary'],        $this->glossary, XARVAR_NOT_REQUIRED)) return;

        // fetch the array of meta tags from input
        if (!xarVarFetch('metatags', 'array', $metatags, array(), XARVAR_NOT_REQUIRED)) return;
        //print_r($metatags); exit;
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
        // make sure we merge the rest of the content we haven't updated
        $vars += $data['content'];
        $data['content'] = $vars;

        return $data;
    }

    public function help()
    {
        return $this->getInfo();
    }

}
?>
