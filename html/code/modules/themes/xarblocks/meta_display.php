<?php
/**
 * Meta Block display interface
 *
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
 * Display block
 *
 * @author  John Cox
 * @author  Carl Corliss
 * @access  public
 * @return  void
*/
sys::import('modules.themes.xarblocks.meta');
sys::import('modules.themes.class.xarmeta');
class Themes_MetaBlockDisplay extends Themes_MetaBlock
{
    public function init() 
    {
        parent::init();
    }

/**
 * Display func.
 * @param $data array containing title,content
 * @todo: add the same functionality for links we now use for metatags
 */
    function display()
    {
        $meta = $this->getContent();
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

        return $meta;

    }

    public function help()
    {
        return $this->getInfo();
    }

}
?>