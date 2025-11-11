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
        $vars = [];

        // fetch the array of meta tags from input
        $this->var()->find('metatags', $metatags, 'array', []);
        $newtags = [];
        foreach ($metatags as $metatag) {
            // empty value = delete
            if (empty($metatag['value'])) {
                continue;
            }
            // @todo: validation on other params
            $newtags[] = $metatag;
        }
        // fetch the value of the new tag (if any)
        $this->var()->find('metatypeval', $metatypeval, 'pre:trim:lower:str:1:', '');
        // only fetch the other params if we have a value
        if (!empty($metatypeval)) {
            $this->var()->find('metatype', $metatype, 'pre:trim:lower:enum:name:http-equiv', '');
            $this->var()->find('metalang', $metalang, 'pre:trim:lower:str:1:', '');
            $this->var()->find('metadir', $metadir, 'pre:trim:lower:enum:ltr:rtl', '');
            $this->var()->find('metascheme', $metascheme, 'pre:trim:str:1:', '');
            $this->var()->find('metacontent', $metacontent, 'pre:trim:str:1:', '');
            if (!empty($metatype)) {
                $newtags[] = [
                    'type' => $metatype,
                    'value' => $metatypeval,
                    'content' => $metacontent,
                    'lang' => $metalang,
                    'dir' => $metadir,
                    'scheme' => $metascheme,
                ];
            }
        }
        $vars['metatags'] = $newtags;
        // store the tags for use by the xarMeta class
        $this->mod('themes')->setVar('meta.tags', serialize($newtags));

        // fetch the array of link tags from input
        $this->var()->find('linktags', $linktags, 'array', []);
        $newlinks = [];
        foreach ($linktags as $linktag) {
            // delete if flag is set not empty
            if (isset($linktag['delete']) && !empty($linktag['delete'])) {
                continue;
            }
            $newlinks[] = $linktag;
        }
        // fetch the value of the new link rel
        $this->var()->find('linkrel', $linkrel, 'pre:trim:str:1:', '');
        // only fetch other params if rel isn't empty
        if (!empty($linkrel)) {
            $this->var()->find('linkhref', $linkhref, 'pre:trim:str:1:', '');
            $this->var()->find('linktitle', $linktitle, 'pre:trim:str:1:', '');
            $this->var()->find('linktype', $linktype, 'pre:trim:str:1:', '');
            $newlinks[] = [
                'rel' => $linkrel,
                'href' => $linkhref,
                'title' => $linktitle,
                'type' => $linktype,
            ];
        }
        $vars['linktags'] = $newlinks;

        $this->setContent($vars);
        return true;
    }

}
