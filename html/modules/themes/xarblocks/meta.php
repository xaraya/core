<?php
/**
 *  Initialise meta block
 *
 * @package modules
 * @copyright (C) 2002-2006 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Themes module
 * @link http://xaraya.com/index.php/release/70.html
 */
/**
 * initialise block
 * @author  John Cox
 * @author  Carl Corliss
 * @access  public
 * @param   none
 * @return  nothing
 * @throws  no exceptions
 * @todo    nothing
*/
function themes_metablock_init()
{
    return array(
        'metakeywords' => '',
        'metadescription' => '',
        'usedk' => '',
        'usegeo' => '',
        'longitude' => '',
        'latitude' => '',
        'copyrightpage' => '',
        'helppage' => '',
        'glossary' => '',
        'nocache' => 1, // don't cache by default
        'pageshared' => 0, // if you do, don't share across pages
        'usershared' => 1, // but share for group members
        'cacheexpire' => null);
}

/**
 * get information on block
 *
 * @author  John Cox
 * @access  public
 * @param   none
 * @return  data array
 * @throws  no exceptions
 * @todo    nothing
*/
function themes_metablock_info()
{
    return array(
        'text_type' => 'Meta',
        'text_type_long' => 'Meta',
        'module' => 'themes',
        'func_update' => 'themes_metablock_update',
        'allow_multiple' => false,
        'form_content' => false,
        'form_refresh' => false,
        'show_preview' => true
    );
}

/**
 * display adminmenu block
 *
 * @author  Carl Corliss, John Cox
 * @access  public
 * @param   $blockinfo array containing usegeo, metakeywords, metadescription, longitude, latitude, usedk.
 * @return  data array on success or void on failure
 * @throws  no exceptions
 * @todo    complete
*/
function themes_metablock_display($blockinfo)
{
    // Security Check
    if (!xarSecurityCheck('ViewBaseBlocks', 0, 'Block', 'meta:'.$blockinfo['title'].':All')) return;

    // Get current content
    if (!is_array($blockinfo['content'])) {
        $vars = unserialize($blockinfo['content']);
    } else {
        $vars = $blockinfo['content'];
    }
    $meta = array();

    // Description
    $incomingdesc = xarVarGetCached('Blocks.articles', 'summary');

    if (!empty($incomingdesc) and $vars['usedk'] >= 1) {
        // Strip -all- html
        $htmlless = strip_tags($incomingdesc);
        $meta['description'] = $htmlless;
    } else {
        $meta['description'] = $vars['metadescription'];
    }

    // Dynamic Keywords
    $incomingkey = xarVarGetCached('Blocks.articles', 'body');
    $incomingkeys = xarVarGetCached('Blocks.keywords', 'keys');

    if (!empty($incomingkey) and $vars['usedk'] == 1) {
        // Keywords generated from articles module
        $meta['keywords'] = $incomingkey;
    } elseif ((!empty($incomingkeys)) and ($vars['usedk'] == 2)){
        // Keywords generated from keywords module
        $meta['keywords'] = $incomingkeys;
    } elseif ((!empty($incomingkeys)) and ($vars['usedk'] == 3)){
        $meta['keywords'] = $incomingkeys.','.$incomingkey;
    } else {
        $meta['keywords'] = $vars['metakeywords'];
    }

    // Character Set
    $meta['charset'] = xarMLSGetCharsetFromLocale(xarMLSGetCurrentLocale());
    $meta['generator'] = xarConfigGetVar('System.Core.VersionId');
    $meta['generator'] .= ' :: ';
    $meta['generator'] .= xarConfigGetVar('System.Core.VersionNum');

    // Geo Url
    $meta['longitude'] = $vars['longitude'];
    $meta['latitude'] = $vars['latitude'];

    // Active Page
    $meta['activepagerss'] = xarServerGetCurrentURL(array('theme' => 'rss'));
    $meta['activepageatom'] = xarServerGetCurrentURL(array('theme' => 'atom'));
    $meta['activepageprint'] = xarServerGetCurrentURL(array('theme' => 'print'));

    $meta['baseurl'] = xarServerGetBaseUrl();
    if (isset($vars['copyrightpage'])){
        $meta['copyrightpage'] = $vars['copyrightpage'];
    } else {
        $meta['copyrightpage'] = '';
    }

    if (isset($vars['helppage'])){
        $meta['helppage'] = $vars['helppage'];
    } else {
        $meta['helppage'] = '';
    }

    if (isset($vars['glossary'])){
        $meta['glossary'] = $vars['glossary'];
    } else {
        $meta['glossary'] = '';
    }

    //Pager Buttons
    $meta['refreshurl']     = xarVarGetCached('Meta.refresh','url');
    $meta['refreshtime']    = xarVarGetCached('Meta.refresh','time');
    $meta['first']          = xarVarGetCached('Pager.first','leftarrow');
    $meta['last']           = xarVarGetCached('Pager.last','rightarrow');

    $blockinfo['content'] = $meta;
    return $blockinfo;

}

/**
 * modify block settings
 *
 * @author  John Cox
 * @access  public
 * @param   $blockinfo
 * @return  $blockinfo data array
 * @throws  no exceptions
 * @todo    nothing
*/
function themes_metablock_modify($blockinfo)
{
    // Get current content
    $vars = @unserialize($blockinfo['content']);

    // Defaults
    if (empty($vars['metakeywords'])) {
        $vars['metakeywords'] = '';
    }
    // Defaults
    if (empty($vars['metadescription'])) {
        $vars['metadescription'] = '';
    }
    // Defaults
    if (empty($vars['usegeo'])) {
        $vars['usegeo'] = '';
    }
    // Defaults
    if (empty($vars['usedk'])) {
        $vars['usedk'] = '';
    }
    // Defaults
    if (empty($vars['longitude'])) {
        $vars['longitude'] = '';
    }
    // Defaults
    if (empty($vars['latitude'])) {
        $vars['latitude'] = '';
    }
    // Defaults
    if (empty($vars['copyrightpage'])) {
        $vars['copyrightpage'] = '';
    }
    // Defaults
    if (empty($vars['helppage'])) {
        $vars['helppage'] = '';
    }
    // Defaults
    if (empty($vars['glossary'])) {
        $vars['glossary'] = '';
    }

    $vars['blockid'] = $blockinfo['bid'];
    $content = xarTplBlock('themes', 'metaAdmin', $vars);

    return $content;
}

/**
 * update block settings
 *
 * @author  John Cox
 * @access  public
 * @param   $blockinfo
 * @return  $blockinfo data array
 * @throws  no exceptions
 * @todo    nothing
*/
function themes_metablock_update($blockinfo)
{
    // TODO: remove this once all blocks can accept content arrays.
    if (!is_array($blockinfo['content'])) {
        $blockinfo['content'] = unserialize($blockinfo['content']);
    }

    // FIXME: use better validation on these parameters.
    $vars = array();
    if (!xarVarFetch('metakeywords',    'notempty', $vars['metakeywords'],    '', XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('metadescription', 'notempty', $vars['metadescription'], '', XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('usegeo',          'int:0:1',  $vars['usegeo'],          0,  XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('longitude',       'notempty', $vars['longitude'],       '', XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('latitude',        'notempty', $vars['latitude'],        '', XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('usedk',           'notempty', $vars['usedk'],           '', XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('copyrightpage',   'notempty', $vars['copyrightpage'],   '', XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('helppage',        'notempty', $vars['helppage'],        '', XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('glossary',        'notempty', $vars['glossary'],        '', XARVAR_NOT_REQUIRED)) return;

    // Merge the submitted block info content into the existing block info.
    $blockinfo['content'] = $vars; //array_merge($blockinfo['content'], $vars);

    return $blockinfo;
}

?>
