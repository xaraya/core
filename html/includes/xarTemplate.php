<?php
/**
 * File: $Id$
 *
 * BlockLayout Template Engine
 *
 * @package blocklayout
 * @copyright (C) 2002 by the Xaraya Development Team.
 * @license GPL <http://www.gnu.org/licenses/gpl.html>
 * @link http://www.xaraya.com
 * @author Paul Rosania <paul@xaraya.com>
 * @author Marco Canini <marco@xaraya.com>
 */

  /**
   *
   * NOTE: <Dracos>  All the widget stuff in here is essentially dead code,
   *       but *DO NOT* remove it.  I still need to figure it out so I can
   *       make proper widgets out of it (for r1.1)
   *
   *    Thanks
   */

  /**
   * Defines for template handling
   *
   */

  /// OLD STUFF //////////////////////////////////
define ('XAR_TPL_OPTIONAL', 2);
define ('XAR_TPL_REQUIRED', 0); // default for attributes

define ('XAR_TPL_STRING', 64);
define ('XAR_TPL_BOOLEAN', 128);
define ('XAR_TPL_INTEGER', 256);
define ('XAR_TPL_FLOAT', 512);
define ('XAR_TPL_ANY', XAR_TPL_STRING|XAR_TPL_BOOLEAN|XAR_TPL_INTEGER|XAR_TPL_FLOAT);
/// END OLD STUFF

/**
 * Define for reg expressions for attributes and tags
 *
 */
define ('XAR_TPL_ATTRIBUTE_REGEX','^[a-z][-_a-z0-9]*$');
define ('XAR_TPL_TAGNAME_REGEX',  '^[a-z][-_a-z0-9]*$');


/**
 * Initializes the BlockLayout Template Engine
 *
 * @author Paul Rosania, Marco Canini <marco@xaraya.com>
 * @access protected
 * @global xarTpl_cacheTemplates bool
 * @global xarTpl_themesBaseDir string
 * @global xarTpl_defaultThemeName string
 * @global xarTpl_additionalStyles string
 * @global xarTpl_JavaScript string
 * @param args['themesBaseDir'] string
 * @param args['defaultThemeName'] string
 * @param args['enableTemplateCaching'] bool
 * @param whatElseIsGoingLoaded int
 * @return bool true
 */
function xarTpl_init($args, $whatElseIsGoingLoaded)
{
    $GLOBALS['xarTpl_themesBaseDir'] = $args['themesBaseDirectory'];
    $GLOBALS['xarTpl_defaultThemeDir'] = $args['defaultThemeDir'];
    $GLOBALS['xarTpl_cacheTemplates'] = $args['enableTemplatesCaching'];

    if (!xarTplSetThemeDir($args['defaultThemeDir'])) {
        // If there is no theme, there is no page template, we dont know what to do now.
        xarCore_die("xarTpl_init: Nonexistent theme directory '" . $args['defaultThemeDir'] ."'");
    }
    if (!xarTplSetPageTemplateName('default')) {
        // If there is no page template, we can't show anything
        xarCore_die("xarTpl_init: Nonexistent default.xt page in theme directory '". xarTplGetThemeDir() ."'");
    }

    if ($GLOBALS['xarTpl_cacheTemplates']) {
        if (!is_writeable(xarCoreGetVarDirPath().'/cache/templates')) {
            $msg = "xarTpl_init: Cannot write in cache/templates directory '"
                . xarCoreGetVarDirPath()
                ."/cache/templates', but setting: 'cache templates' is set to On. Either change file/directory permissions or set caching to Off (not recommended).";
            $GLOBALS['xarTpl_cacheTemplates'] = false;
            // Set the exception, but do not return just yet, because we *can* continue.
            xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'CONFIG_ERROR', $msg);
        }
    }

    $GLOBALS['xarTpl_additionalStyles'] = '';

    // Initialise the JavaScript array. Start with placeholders for the head and body.
    $GLOBALS['xarTpl_JavaScript'] = array('head'=>array(), 'body'=>array());

    // This is wrong here as well, but it's better at least than in xarMod
    include "includes/xarTheme.php";

    return true;
}

/**
 * Get theme name
 *
 * @access public
 * @global xarTpl_themeName string
 * @return string themename
 */
function xarTplGetThemeName()
{
    if (function_exists('xarModGetVar')){
        $defaultTheme = xarModGetVar('themes', 'default');
        if (!empty($defaultTheme)){
            return $defaultTheme;
        } else {
            return $GLOBALS['xarTpl_themeName'];
        }
    } else {
        return $GLOBALS['xarTpl_themeName'];
    }
}

/**
 * Set theme name
 *
 * @access public
 * @global xarTpl_themesBaseDir string
 * @global xarTpl_themeName string
 * @param themeName string
 * @return bool
 */
function xarTplSetThemeName($themeName)
{
    assert('$themeName != "" && $themeName{0} != "/"');
    if (!file_exists($GLOBALS['xarTpl_themesBaseDir'].'/'.$themeName)) {
        return false;
    }

    __setThemeNameAndDir($themeName);
    return true;
}

/**
 * Set theme dir
 *
 * @access public
 * @global xarTpl_themesBaseDir string
 * @global xarTpl_themeDir string
 * @param themeDir string
 * @return bool
 */
function xarTplSetThemeDir($themeDir)
{
    assert('$themeDir != "" && $themeDir{0} != "/"');
    if (!file_exists($GLOBALS['xarTpl_themesBaseDir'].'/'.$themeDir)) {
        return false;
    }

    __setThemeNameAndDir($themeDir);
    return true;
}

function __setThemeNameAndDir($name)
{
    // dir and name are still required to be the same
    $GLOBALS['xarTpl_themeName'] = $name;
    $GLOBALS['xarTpl_themeDir']  = $GLOBALS['xarTpl_themesBaseDir'] . '/' . $name;
}
/**
 * Get theme directory
 *
 * @access public
 * @global xarTpl_themeDir string
 * @return sring theme directory
 */
function xarTplGetThemeDir()
{
    return $GLOBALS['xarTpl_themeDir'];
}

/**
 * Get page template name
 *
 * @access public
 * @global xarTpl_pageTemplateName string
 * @return string page template name
 */
function xarTplGetPageTemplateName()
{
    return $GLOBALS['xarTpl_pageTemplateName'];
}

/**
 * Set page template name
 *
 * @access public
 * @global xarTpl_pageTemplateName string
 * @param templateName string
 * @return bool
 */
function xarTplSetPageTemplateName($templateName)
{
    assert('$templateName != ""');
    if (!file_exists(xarTplGetThemeDir() . "/pages/$templateName.xt")) {
        return false;
    }
    $GLOBALS['xarTpl_pageTemplateName'] = $templateName;
    return true;
}

/**
 * Set page title
 *
 * @access public
 * @global xarTpl_pageTitle string
 * @param title string
 * @param module string
 * @return bool
 */
function xarTplSetPageTitle($title = NULL, $module = NULL)
{
    if (!function_exists('xarModGetVar')){
        $GLOBALS['xarTpl_pageTitle'] = $title;
    } else {
        $order      = xarModGetVar('themes', 'SiteTitleOrder');
        $separator  = xarModGetVar('themes', 'SiteTitleSeparator');
        if (empty($module)) {
            $module = ucwords(xarModGetName());
        }
        switch(strtolower($order)) {
            case 'default':
            default:
                $GLOBALS['xarTpl_pageTitle'] = xarModGetVar('themes', 'SiteName') . $separator . $module . $separator . $title;
            break;
            case 'sp':
                $GLOBALS['xarTpl_pageTitle'] = xarModGetVar('themes', 'SiteName') . $separator . $title;
            break;
            case 'mps':
                $GLOBALS['xarTpl_pageTitle'] = $module . $separator . $title . $separator .  xarModGetVar('themes', 'SiteName');
            break;
            case 'pms':
                $GLOBALS['xarTpl_pageTitle'] = $title . $separator .  $module . $separator . xarModGetVar('themes', 'SiteName');
            break;
            case 'to':
                $GLOBALS['xarTpl_pageTitle'] = $title;
            break;
        }
    }
    return true;
}
/**
 * Get page title
 *
 * @access public
 * @return string
 */
function xarTplGetPageTitle()
{
    return $GLOBALS['xarTpl_pageTitle'];
}

/**
 * Add stylesheet link for a module
 *
 * @access public
 * @global xarTpl_additionalStyles string
 * @param modName string
 * @param styleName string
 * @param fileExt string
 * @return bool
 */
function xarTplAddStyleLink($modName, $styleName, $fileExt = 'css')
{
    $info = xarMod_getBaseInfo($modName);
    if (!isset($info)) return;
    $fileName = "modules/$info[directory]/xarstyles/$styleName.$fileExt";
    if (!file_exists($fileName)) {
        return false;
    }
    $url = xarServerGetBaseURL().$fileName;
    $GLOBALS['xarTpl_additionalStyles'] .= "<link rel=\"stylesheet\" type=\"text/css\" href=\"{$url}\" />\n";

    return true;
}

/**
 * Add JavaScript code to template output **deprecated**
 *
 * @access public
 * @param position string
 * @param owner string
 * @param code string
 * @deprec true
 * @return bool
 */
function xarTplAddJavaScriptCode($position, $owner, $code)
{
    assert('$position == "head" || $position == "body"');
    return xarTplAddJavaScript($position, 'code', "<!--- JavaScript code from {$owner} --->\n" . $code);
}

/**
 * Add JavaScript code or links to template output
 *
 * @access public
 * @global xarTpl_JavaScript array
 * @param position string ('head' or 'body')
 * @param type string ('src' or 'code')
 * @param data string (pathname or raw JavaScript)
 * @param index string optional (unique key and/or ordering)
 * @return bool
 */
function xarTplAddJavaScript($position, $type, $data, $index = '')
{
    if (empty($position) || empty($type) || empty($data)) {return;}
    if (empty($index)) {
        $GLOBALS['xarTpl_JavaScript'][$position][] = array('type'=>$type, 'data'=>$data);
    } else {
        $GLOBALS['xarTpl_JavaScript'][$position][$index] = array('type'=>$type, 'data'=>$data);
    }

    return true;
}

/**
 * Get JavaScript code or links cached for template output
 *
 * @access public
 * @global xarTpl_JavaScript array
 * @param position string optional
 * @param index string optional
 * @return array or NULL
 */
function xarTplGetJavaScript($position = '', $index = '')
{
    if (empty($position)) {return $GLOBALS['xarTpl_JavaScript'];}
    if (!isset($GLOBALS['xarTpl_JavaScript'][$position])) {return;}
    if (empty($index)) {return $GLOBALS['xarTpl_JavaScript'][$position];}
    if (!isset($GLOBALS['xarTpl_JavaScript'][$position][$index])) {return;}
    return $GLOBALS['xarTpl_JavaScript'][$position][$index];
}

/**
 * Turns module output into a template.
 *
 * @author Paul Rosania, Marco Canini <marco@xaraya.com>
 * @access public
 * @param modName string the module name
 * @param modType string user|admin
 * @param funcName string module function to template
 * @param tplData array arguments for the template
 * @param templateName string the specific template to call
 * @return string xarTpl__executeFromFile($sourceFileName, $tplData)
 */
function xarTplModule($modName, $modType, $funcName, $tplData = array(), $templateName = NULL)
{

    if (!empty($templateName)) {
        $templateName = xarVarPrepForOS($templateName);
    }

    if (!($modBaseInfo = xarMod_getBaseInfo($modName))) return;
    $modOsDir = $modBaseInfo['osdirectory'];

    // Try theme template
    $sourceFileName = xarTplGetThemeDir() . "/modules/$modOsDir/$modType-$funcName" . (empty($templateName) ? '.xt' : "-$templateName.xt");
    if (!file_exists($sourceFileName)) {
        // Use internal template
        $tplName = "$modType-$funcName" . (empty($templateName) ? '' : "-$templateName");
        $sourceFileName = "modules/$modOsDir/xartemplates/$tplName.xd";
        // fall back to default template if necessary
        if (!empty($templateName) && !file_exists($sourceFileName)) {
            $tplName = "$modType-$funcName";

            //check if theme overides default template
            $sourceFileName = xarTplGetThemeDir() . "/modules/$modOsDir/$modType-$funcName" .'.xt';
            if(!file_exists($sourceFileName))
            {
                $sourceFileName = "modules/$modOsDir/xartemplates/$modType-$funcName.xd";
            }
        }
        if (xarMLS_loadTranslations(XARMLS_DNTYPE_MODULE, $modName, 'modules:templates', $tplName) === NULL) return;
    } /*else {
        TODO: <marco> Handle i18n for this case
    }*/

    $tplData['_bl_module_name'] = $modName;
    $tplData['_bl_module_type'] = $modType;
    $tplData['_bl_module_func'] = $funcName;

    return xarTpl__executeFromFile($sourceFileName, $tplData);
}

/**
 * Turns block output into a template.
 *
 * @author Paul Rosania, Marco Canini <marco@xaraya.com>
 * @access public
 * @param modName string the module name
 * @param blockName string the block name
 * @param tplData array arguments for the template
 * @param templateName string the specific template to call
 * @return string xarTpl__executeFromFile($sourceFileName, $tplData)
 */
function xarTplBlock($modName, $blockName, $tplData = array(), $templateName = NULL)
{

    if (!empty($templateName)) {
        $templateName = xarVarPrepForOS($templateName);
    }

    $modBaseInfo = xarMod_getBaseInfo($modName);
    if (!isset($modBaseInfo)) return; // throw back
    $modOsDir = $modBaseInfo['osdirectory'];

    // Try theme template
    $sourceFileName = xarTplGetThemeDir() . "/modules/$modOsDir/blocks/$blockName" . (empty($templateName) ? '.xt' : "-$templateName.xt");
    if (!file_exists($sourceFileName)) {
        // Use internal template
        $blockFileName = $blockName . (empty($templateName) ? '' : "-$templateName");
        $sourceFileName = "modules/$modOsDir/xartemplates/blocks/$blockFileName" . '.xd';
        if (xarMLS_loadTranslations(XARMLS_DNTYPE_MODULE, $modName, 'modules:templates/blocks', $blockFileName) === NULL) return;
    }

    return xarTpl__executeFromFile($sourceFileName, $tplData);
}

/**
 * Get theme template image replacement for a module's image
 *
 * Example:
 * $my_module_image = xarTplGetImage('button1.png');
 * $other_module_image = xarTplGetImage('set1/info.png','modules');
 *
 * Correct practices:
 *
 * 1. module developers should never rely on theme's images, but instead
 * provide their own artwork inside modules/<module>/xarimages/ directory
 * and use this function to reference their images in the module's functions.
 * Such reference can then be safely passed to the module template.
 *
 * 2. theme developers should always check for the modules images
 * (at least for all core modules) and provide replacements images
 * inside the corresponding themes/<theme>/modules/<module>/images/
 * directories as necessary
 *
 * Note : your module is still responsible for taking care that "images"
 *        don't contain nasty stuff. Filter as appropriate when using
 *        this function to generate image URLs...
 *
 * @author  Andy Varganov <andyv@xaraya.com>
 * @access  public
 * @param   modImage string, the module image url relative to xarimages/
 * @param   modName string, the module to check for the image <optional>
 * @return string theme image url if it exists or module image url if not, or NULL if neither found
 * @todo    provide examples, improve description, add functionality
*/
function xarTplGetImage($modImage, $modName = NULL)
{
    // return absolute URIs and URLs "as is"
    if (empty($modImage) || substr($modImage,0,1) == '/' || preg_match('/^https?\:\/\//',$modImage)) {
        return $modImage;
    }

    // obtain current module name if not specified
    if(!isset($modName)){
        list($modName) = xarRequestGetInfo();
    }

    // get module directory (could be different from module name)
    $modBaseInfo = xarMod_getBaseInfo($modName);

    if (!isset($modBaseInfo)) return; // throw back

    $modOsDir = $modBaseInfo['osdirectory'];

    // relative url to the current module's image
    $moduleImage = 'modules/'.$modOsDir.'/xarimages/'.$modImage;

    // obtain current theme directory
    $themedir = xarTplGetThemeDir();

    // relative url to the replacement image in current theme
    $themeImage = $themedir . '/modules/'.$modOsDir.'/images/'.$modImage;

    // check if replacement image exists in the theme
    if (file_exists($themeImage)) {
        // image found, return its path in the theme
        return $themeImage;

    } elseif (file_exists($moduleImage)) {
        // image found, return it's path in the module
        return $moduleImage;
    }

    // all efforts failed, return NULL
    return;
}

/**
 * Creates pager information with no assumptions to output format.
 *
 * @author Jason Judge
 * @since 2003/10/09
 * @access public
 * @param integer $startNum start item
 * @param integer $total total number of items present
 * @param integer $itemsPerPage number of links to display (default=10)
 * @param integer $blockOptions number of pages to display at once (default=10) or array of advanced options
 */
function xarTplPagerInfo($currentItem, $total, $itemsPerPage = 10, $blockOptions = 10)
{
    // Default block options.
    if (is_numeric($blockOptions)) {
        $pageBlockSize = $blockOptions;
    }

    if (is_array($blockOptions)) {
        if (!empty($blockOptions['blocksize'])) {$blockSize = $blockOptions['blocksize'];}
        if (!empty($blockOptions['firstitem'])) {$firstItem = $blockOptions['firstitem'];}
        if (!empty($blockOptions['firstpage'])) {$firstPage = $blockOptions['firstpage'];}
        if (!empty($blockOptions['urltemplate'])) {$urltemplate = $blockOptions['urltemplate'];}
        if (!empty($blockOptions['urlitemmatch'])) {
            $urlItemMatch = $blockOptions['urlitemmatch'];
        } else {
            $urlItemMatch = '%%';
        }
    }

    // Default values.
    if (empty($blockSize) || $blockSize < 1) {$blockSize = 10;}
    if (empty($firstItem)) {$firstItem = 1;}
    if (empty($firstPage)) {$firstPage = 1;}

    // The last item may be offset if the first item is not 1.
    $lastItem = ($total + $firstItem - 1);

    // Sanity check on arguments.
    if ($itemsPerPage < 1) {$itemsPerPage = 10;}
    if ($currentItem < $firstItem) {$currentItem = $firstItem;}
    if ($currentItem > $lastItem) {$currentItem = $lastItem;}

    // If this request was the same as the last one, then return the cached pager details.
    // TODO: is there a better way of caching for each unique request?
    $request = md5($currentItem . ':' . $lastItem . ':' . $itemsPerPage . ':' . serialize($blockOptions));
    if (xarVarGetCached('Pager.core', 'request') == $request) {
        return xarVarGetCached('Pager.core', 'details');
    }

    // Record the values in this request.
    xarVarSetCached('Pager.core', 'request', $request);

    // Max number of items in a block of pages.
    $itemsPerBlock = ($blockSize * $itemsPerPage);

    // Get the start and end items of the page block containing the current item.
    $blockFirstItem = $currentItem - (($currentItem - $firstItem) % $itemsPerBlock);
    $blockLastItem = $blockFirstItem + $itemsPerBlock - 1;
    if ($blockLastItem > $lastItem) {$blockLastItem = $lastItem;}

    // Current/Last page numbers.
    $currentPage = (int)ceil(($currentItem-$firstItem+1) / $itemsPerPage) + $firstPage - 1;
    $totalPages = (int)ceil($total / $itemsPerPage);

    // First/Current/Last block numbers
    $firstBlock = 1;
    $currentBlock = (int)ceil(($currentItem-$firstItem+1) / $itemsPerBlock);
    $totalBlocks = (int)ceil($total / $itemsPerBlock);

    // Get start and end items of the current page.
    $pageFirstItem = $currentItem - (($currentItem-$firstItem) % $itemsPerPage);
    $pageLastItem = $pageFirstItem + $itemsPerPage - 1;
    if ($pageLastItem > $lastItem) {$pageLastItem = $lastItem;}

    // Initialise data array.
    $data = array();

    $data['middleitems'] = array();
    $pageNum = (int)ceil(($blockFirstItem - $firstItem + 1) / $itemsPerPage) + $firstPage - 1;
    for ($i = $blockFirstItem; $i <= $blockLastItem; $i += $itemsPerPage) {
        if (!empty($urltemplate)) {
            $data['middleurls'][$pageNum] = str_replace($urlItemMatch, $i, $urltemplate);
        }
        $data['middleitems'][$pageNum] = $i;
        $data['middleitemsfrom'][$pageNum] = $i;
        $data['middleitemsto'][$pageNum] = $i + $itemsPerPage - 1;
        if ($data['middleitemsto'][$pageNum] > $total) {$data['middleitemsto'][$pageNum] = $total;}
        $pageNum += 1;
    }

    $data['currentitem'] = $currentItem;
    $data['totalitems'] = $total;
    $data['lastitem'] = $lastItem;
    $data['firstitem'] = $firstItem;
    $data['itemsperpage'] = $itemsPerPage;
    $data['itemsperblock'] = $itemsPerBlock;
    $data['pagesperblock'] = $blockSize;

    $data['currentblock'] = $currentBlock;
    $data['totalblocks'] = $totalBlocks;
    $data['firstblock'] = $firstBlock;
    $data['lastblock'] = $totalBlocks;
    $data['blockfirstitem'] = $blockFirstItem;
    $data['blocklastitem'] = $blockLastItem;

    $data['currentpage'] = $currentPage;
    $data['currentpagenum'] = $currentPage;
    $data['totalpages'] = $totalPages;
    $data['pagefirstitem'] = $pageFirstItem;
    $data['pagelastitem'] = $pageLastItem;

    // These two are item numbers. The naming is historical.
    $data['firstpage'] = $firstItem;
    $data['lastpage'] = $lastItem - (($lastItem-$firstItem) % $itemsPerPage);

    if (!empty($urltemplate)) {
        // These two links are for first and last pages.
        $data['firsturl'] = str_replace($urlItemMatch, $data['firstpage'], $urltemplate);
        $data['lasturl'] = str_replace($urlItemMatch, $data['lastpage'], $urltemplate);
    }

    $data['firstpagenum'] = $firstPage;
    $data['lastpagenum'] = ($totalPages + $firstPage - 1);

    // Data for previous page of items.
    if ($currentPage > $firstPage) {
        $data['prevpageitems'] = $itemsPerPage;
        $data['prevpage'] = ($pageFirstItem - $itemsPerPage);
        if (!empty($urltemplate)) {
            $data['prevpageurl'] = str_replace($urlItemMatch, $data['prevpage'], $urltemplate);
        }
    } else {
        $data['prevpageitems'] = 0;
    }

    // Data for next page of items.
    if ($pageLastItem < $lastItem) {
        $nextPageLastItem = ($pageLastItem + $itemsPerPage);
        if ($nextPageLastItem > $lastItem) {$nextPageLastItem = $lastItem;}
        $data['nextpageitems'] = ($nextPageLastItem - $pageLastItem);
        $data['nextpage'] = ($pageLastItem + 1);
        if (!empty($urltemplate)) {
            $data['nextpageurl'] = str_replace($urlItemMatch, $data['nextpage'], $urltemplate);
        }
    } else {
        $data['nextpageitems'] = 0;
    }

    // Data for previous block of pages.
    if ($currentBlock > $firstBlock) {
        $data['prevblockpages'] = $blockSize;
        $data['prevblock'] = ($blockFirstItem - $itemsPerBlock);
        if (!empty($urltemplate)) {
            $data['prevblockurl'] = str_replace($urlItemMatch, $data['prevblock'], $urltemplate);
        }
    } else {
        $data['prevblockpages'] = 0;
    }

    // Data for next block of pages.
    if ($currentBlock < $totalBlocks) {
        $nextBlockLastItem = ($blockLastItem + $itemsPerBlock);
        if ($nextBlockLastItem > $lastItem) {$nextBlockLastItem = $lastItem;}
        $data['nextblockpages'] = ceil(($nextBlockLastItem - $blockLastItem) / $itemsPerPage);
        $data['nextblock'] = ($blockLastItem + 1);
        if (!empty($urltemplate)) {
            $data['nextblockurl'] = str_replace($urlItemMatch, $data['nextblock'], $urltemplate);
        }
    } else {
        $data['nextblockpages'] = 0;
    }

    // Cache all the pager details.
    xarVarSetCached('Pager.core', 'details', $data);

    return $data;
}

/**
 * Equivalent of pnHTML()'s Pager function (to get rid of pnHTML calls in modules while waiting for widgets)
 *
 * @author Jason Judge
 * @since 1.13 - 2003/10/09
 * @access public
 * @param integer $startnum start item
 * @param integer $total total number of items present
 * @param string $urltemplate template for url, will replace '%%' with item number
 * @param integer $perpage number of links to display (default=10)
 * @param integer $blockOptions number of pages to display at once (default=10) or array of advanced options
 * @param integer $template alternative template name within base/user (default 'pager')
 */
function xarTplGetPager($startNum, $total, $urltemplate, $itemsPerPage = 10, $blockOptions = array(), $template = 'default')
{
    // Quick check to ensure that we have work to do
    if ($total <= $itemsPerPage) {return '';}

    // Number of pages in a page block - support older numeric 'pages per block'.
    if (is_numeric($blockOptions)) {
        $blockOptions = array('blocksize' => $blockOptions);
    }

    // Pass the url template into the pager calculator.
    $blockOptions['urltemplate'] = $urltemplate;

    // Get the pager information.
    $data = xarTplPagerInfo($startNum, $total, $itemsPerPage, $blockOptions);

    // Nothing to do: perhaps there is an error in the parameters?
    if (empty($data)) {return '';}

    // Couple of cached values used in various pages.
    // It is unclear what these values are supposed to be used for.
    if ($data['prevblockpages'] > 0) {
        xarVarSetCached('Pager.first', 'leftarrow', $data['firsturl']);
    }

    // Links for next block of pages.
    if ($data['nextblockpages'] > 0) {
        xarVarSetCached('Pager.last', 'rightarrow', $data['lasturl']);
    }

    return trim(xarTplModule('base', 'pager', $template, $data));
}

/**
 * Execute a pre-compiled template string with the supplied template variables
 *
 * @access public
 * @param templateCode string pre-compiled template code (see xarTplCompileString)
 * @param tplData array template variables
 * @return  string filled-in template
 */
function xarTplString($templateCode, $tplData)
{
    return xarTpl__execute($templateCode, $tplData);
}

/**
 * Execute a specific template file with the supplied template variables
 *
 * @access public
 * @param fileName string location of the template file
 * @param tplData array template variables
 * @return string filled-in template
 */
function xarTplFile($fileName, $tplData)
{
    return xarTpl__executeFromFile($fileName, $tplData);
}

/**
 * Compile a template string for storage and/or later use in xarTplString()
 * Note : your module should always support the possibility of re-compiling
 *        template strings e.g. after an upgrade, so you should store both
 *        the original template and the compiled version if necessary
 *
 * @access public
 * @param templateSource string template source
 * @return string compiled template
 */
function xarTplCompileString($templateSource)
{
    $blCompiler = xarTpl__getCompilerInstance();
    return $blCompiler->compile($templateSource);
}

/**
 * Renders a page template.
 *
 * @author Paul Rosania, Marco Canini <marco@xaraya.com>
 * @access protected
 * @global xarTpl_additionalStyles string
 * @param mainModuleOutput stringthe module output
 * @param otherModulesOutput string
 * @param templateName string the template page to use
 * @return string
 *
 * @todo finish otherModulesOuptput
 */
function xarTpl_renderPage($mainModuleOutput, $otherModulesOutput = NULL, $templateName = NULL)
{
    if (empty($templateName)) {
        $templateName = xarTplGetPageTemplateName();
    }

    $templateName = xarVarPrepForOS($templateName);
    $sourceFileName = xarTplGetThemeDir() . "/pages/$templateName.xt";
    xarLogMessage("Using $sourceFileName");

    $tplData = array(
        '_bl_mainModuleOutput'     => $mainModuleOutput,
        '_bl_page_title'           => xarTplGetPageTitle(),
        '_bl_additional_styles'    => $GLOBALS['xarTpl_additionalStyles'],
        '_bl_javascript'           => $GLOBALS['xarTpl_JavaScript']
    );

    return xarTpl__executeFromFile($sourceFileName, $tplData);
}

/**
 * Render a block box
 *
 * @access protected
 * @param blockInfo string
 * @param templateName string
 * @return bool xarTpl__executeFromFile($sourceFileName, $blockInfo)
 */
function xarTpl_renderBlockBox($blockInfo, $templateName = NULL)
{
    // FIXME: <mrb> should we revert to default here?
    if (empty($templateName)) {
        $templateName = 'default';
    }

    $templateName = xarVarPrepForOS($templateName);

    $sourceFileName = xarTplGetThemeDir() . "/blocks/$templateName.xt";

    return xarTpl__executeFromFile($sourceFileName, $blockInfo);
}

/**
 * Render a widget
 *
 * @access protected
 * @param widgetName string
 * @param tplData string
 * @return xarTpl__executeFromFile($sourceFileName, $tplData)
 */
function xarTpl_renderWidget($widgetName, $tplData)
{
    $sourceFileName = xarTplGetThemeDir() . "/widgets/$widgetName.xd";
    return xarTpl__executeFromFile($sourceFileName, $tplData);
}

function xarTpl_includeThemeTemplate($templateName, $tplData)
{
    $templateName = xarVarPrepForOS($templateName);
    $sourceFileName = xarTplGetThemeDir() ."/includes/$templateName.xt";
    return xarTpl__executeFromFile($sourceFileName, $tplData);
}

function xarTpl_includeModuleTemplate($modName, $templateName, $tplData)
{
    $templateName = xarVarPrepForOS($templateName);
    $sourceFileName = xarTplGetThemeDir() . "/modules/$modName/includes/$templateName.xt";
    if (!file_exists($sourceFileName)) {
        $sourceFileName = "modules/$modName/xartemplates/includes/$templateName.xd";
        if (xarMLS_loadTranslations(XARMLS_DNTYPE_MODULE, $modName, 'modules:templates/includes', $templateName) === NULL) return;
    }
    return xarTpl__executeFromFile($sourceFileName, $tplData);
}

// PRIVATE FUNCTIONS

/**
 * Get BL compiler instance
 *
 * @access private
 * @return object xarTpl__Compiler()
 */
function xarTpl__getCompilerInstance()
{
    include_once 'includes/xarBLCompiler.php';
    return new xarTpl__Compiler();
}

// Now featuring *eval()* for your anti-caching pleasure :-)
/**
 * Execute Template ?
 *
 * @access private
 * @param templateCode string
 * @param tplData array
 * @param sourceFileName string
 * @return string output
 */
function xarTpl__execute($templateCode, $tplData, $sourceFileName = '')
{
    // $tplData should be an array (-even-if- it only has one value in it)
    assert('is_array($tplData)');

    //POINT of ENTRY for cleaning variables
    // We need to be able to figure what is the template output type: RSS, XHTML, XML or whatever

    $tplData['_bl_data'] = $tplData;

    // $__tplData should be an array (-even-if- it only has one value in it),
    // if it's not throw an exception.
    if (is_array($tplData)) {
        extract($tplData, EXTR_OVERWRITE);
    } else {
        $msg = 'Incorrect format for tplData, it must be an associative array of arguments';
        xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM',
                       new SystemException($msg));
        return;
    }

    // Start output buffering
    ob_start();
    // Kick it
    eval('?>' . $templateCode);

    if($sourceFileName != '') {
        $tplOutput = ob_get_contents();
        ob_end_clean();
        ob_start();
        // this outputs the template and deals with start comments accordingly.
        echo xarTpl_outputTemplate($sourceFileName, $tplOutput);
    }

    // Fetch output and clean buffer
    $output = ob_get_contents();
    ob_end_clean();

    // Return output
    return $output;
}
/**
 * Execute template from  file?
 *
 * @access private
 * @global xarTpl_cacheTemplates bool
 * @param sourceFileName string
 * @param tplData array
 * @return mixed
 */
function xarTpl__executeFromFile($sourceFileName, $tplData)
{
    // $tplData should be an array (-even-if- it only has one value in it)
    assert('is_array($tplData)');

    $needCompilation = true;

    if ($GLOBALS['xarTpl_cacheTemplates']) {
        $varDir = xarCoreGetVarDirPath();
        $cacheKey = md5($sourceFileName);
        $cachedFileName = $varDir . '/cache/templates/' . $cacheKey . '.php';
        if (file_exists($cachedFileName)
            && (!file_exists($sourceFileName) || (filemtime($sourceFileName) < filemtime($cachedFileName)))) {
            $needCompilation = false;
        }
    }

    if (!file_exists($sourceFileName) && $needCompilation == true) {
        xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'TEMPLATE_NOT_EXIST', $sourceFileName);
        return;
    }

    //xarLogVariable('needCompilation', $needCompilation, XARLOG_LEVEL_ERROR);
    if ($needCompilation) {
        $blCompiler = xarTpl__getCompilerInstance();
        $lasterror = xarCurrentError();
        $templateCode = $blCompiler->compileFile($sourceFileName);
        // TODO (random) make this more robust
        // we check the error stack here to make sure no new errors happened during compile
        // but we do not check the core stack
        if (!isset($templateCode) || xarCurrentError() != $lasterror) {
            return; // exception! throw back
        }
        if ($GLOBALS['xarTpl_cacheTemplates']) {
            $fd = fopen($cachedFileName, 'w');
            if(xarTpl_outputPHPCommentBlockInTemplates()) {
                $commentBlock = "<?php\n/*"
                              . "\n * Source:     " . $sourceFileName
                              . "\n * Theme:      " . xarTplGetThemeName()
                              . "\n * Compiled: ~ " . date('Y-m-d H:i:s T', filemtime($cachedFileName))
                              . "\n */\n?>\n";
                fwrite($fd, $commentBlock);
            }
            fwrite($fd, $templateCode);
            fclose($fd);
            // Add an entry into CACHEKEYS
            $varDir = xarCoreGetVarDirPath();
            $fd = fopen($varDir . '/cache/templates/CACHEKEYS', 'a');
            fwrite($fd, $cacheKey. ': '.$sourceFileName . "\n");
            fclose($fd);

        // Commented this out for now, a double entry should not occur anyway, eventually this could even be an assert.
            //if (!in_array($entry, $file)) {
            //   $fd = fopen($varDir . '/cache/templates/CACHEKEYS', 'a');
            //   fwrite($fd, $entry);
            //   fclose($fd);
            //}
        } else {
            return xarTpl__execute($templateCode, $tplData, $sourceFileName);
        }
    }

    // $cachedFileName should have a value from this point on
    // see the return statement couple of lines back.

    //POINT of ENTRY for cleaning variables
    // We need to be able to figure what is the template output type: RSS, XHTML, XML or whatever
    $tplData['_bl_data'] = $tplData;
    // $__tplData should be an array (-even-if- it only has one value in it),
    // if it's not throw an exception.
    if (is_array($tplData)) {
        extract($tplData, EXTR_OVERWRITE);
    } else {
        // This should actually never be reached.
        $msg = 'Incorrect format for tplData, it must be an associative array of arguments';
        xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM',
                       new SystemException($msg));
        return;
    }

    // Load cached template file
    ob_start();
    $res = include $cachedFileName;
    $tplOutput = ob_get_contents();
    ob_end_clean();

    // Start output buffering
    ob_start();
    // this outputs the template and deals with start comments accordingly.
    echo xarTpl_outputTemplate($sourceFileName, $tplOutput);

    // Fetch output and clean buffer
    $output = ob_get_contents();
    ob_end_clean();

    // Return output
    return $output;
}
/**
 * Output template
 *
 * @access private
 * @param sourceFileName string
 * @param tplOutput string
 * @return void
 */
function xarTpl_outputTemplate($sourceFileName, &$tplOutput)
{
    // flag used to determine if the header content has been found.
    static $isHeaderContent;
    if(!isset($isHeaderContent))
        $isHeaderContent = false;

    $finalTemplate ='';
    if(xarTpl_outputTemplateFilenames()) {
        $outputStartComment = true;
        if($isHeaderContent === false) {
            if($isHeaderContent = xarTpl_modifyHeaderContent($sourceFileName, $tplOutput))
                $outputStartComment = false;
        }
        // optionally show template filenames if start comment has not already
        // been added as part of a header determination.
        if($outputStartComment === true)
            $finalTemplate .= "\n<!-- start: " . $sourceFileName . "-->\n";
        $finalTemplate .= $tplOutput;
        $finalTemplate .= "\n<!-- end: " . $sourceFileName . '-->';
    } else {
        $finalTemplate .= $tplOutput;
    }
    return $finalTemplate;
}
/**
 * Output php comment block in templates
 *
 * @global xarTpl_showPHPCommentBlockInTemplates int
 * @access private
 * @return value of xarTpl_showPHPCommentBlockInTemplates (0 or 1) int
 */
function xarTpl_outputPHPCommentBlockInTemplates()
{
    if (!isset($GLOBALS['xarTpl_showPHPCommentBlockInTemplates'])) {
        // CHECKME: not sure if this is needed, e.g. during installation
        if (function_exists('xarModGetVar')){
            $showphpcbit = xarModGetVar('themes', 'ShowPHPCommentBlockInTemplates');
            if (!empty($showphpcbit)) {
                $GLOBALS['xarTpl_showPHPCommentBlockInTemplates'] = 1;
            } else {
                $GLOBALS['xarTpl_showPHPCommentBlockInTemplates'] = 0;
            }
        } else {
            $GLOBALS['xarTpl_showPHPCommentBlockInTemplates'] = 0;
        }
    }
    return $GLOBALS['xarTpl_showPHPCommentBlockInTemplates'];
}
/**
 * Output template filenames
 *
 * @global xarTpl_showTemplateFilenames int
 * @access private
 * @return value of xarTpl_showTemplateFilenames (0 or 1) int
 */
function xarTpl_outputTemplateFilenames()
{
    if (!isset($GLOBALS['xarTpl_showTemplateFilenames'])) {
        // CHECKME: not sure if this is needed, e.g. during installation
        if (function_exists('xarModGetVar')){
            $showtemplates = xarModGetVar('themes', 'ShowTemplates');
            if (!empty($showtemplates)) {
                $GLOBALS['xarTpl_showTemplateFilenames'] = 1;
            } else {
                $GLOBALS['xarTpl_showTemplateFilenames'] = 0;
            }
        } else {
            $GLOBALS['xarTpl_showTemplateFilenames'] = 0;
        }
    }
    return $GLOBALS['xarTpl_showTemplateFilenames'];
}
/**
 * Modify header content
 *
 * Attempt to determine if $tplOutput contains header content and if
 * so append a start comment after the first matched header tag
 * found.
 *
 * @todo it is possible that the first regex <!DOCTYPE[^>].*]> is too
 *       greedy in more complex xml documents and others.
 * @access private
 * @param sourceFileName string
 * @param tplOutput string
 * @return bool found header content
 */
function xarTpl_modifyHeaderContent($sourceFileName, &$tplOutput)
{
    $foundHeaderContent = false;

    // $headerTagsRegexes is an array of string regexes to match tags that could
    // be sent as part of a header. Important: the order here should be inside out
    // as the first regex that matches will have a start comment appended.
    // fixes bugs: #1427, #1190, #603
    // - Comments that precede <!doctype... cause ie6 not to sniff the doctype
    //   correctly.
    // - xml parsers dont like comments that precede xml output.
    // At this time attempting to match <!doctype... and <?xml version... tags.
    // This is about the best we can do now, until we process xar documents with an xml parser and actually 'parse'
    // the document.
    $headerTagRegexes = array('<!DOCTYPE[^>].*]>',// eg. <!DOCTYPE doc [<!ATTLIST e9 attr CDATA "default">]>
                              '<!DOCTYPE[^>]*>',// eg. <!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
                              '<\?xml\s+version[^>]*\?>');// eg. <?xml version="1.0"? > // remove space between qmark and gt

    foreach($headerTagRegexes as $headerTagRegex) {
        if(preg_match("/$headerTagRegex/smix", $tplOutput, $matchedHeaderTag)) {
            $startComment = '<!-- start(output actually commenced before header(s)): ' . $sourceFileName . '-->';
            // replace matched tag with an appended start comment tag in the first match
            // in the template output $tplOutput
            $tplOutput = preg_replace("/$headerTagRegex/smix", $matchedHeaderTag[0] . $startComment, $tplOutput, 1);
            // dont want start comment to be sent below as it has already been added.
            $foundHeaderContent = true;
            break;
        }
    }

    return $foundHeaderContent;
}
/**
 * Load template from file (e.g. for use with recurring template snippets someday,
 * using xarTplString() to "fill in" the template afterwards)
 *
 * @access private
 * @global xarTpl_cacheTemplates bool
 * @param sourceFileName string
 * @return mixed
 */
function xarTpl__loadFromFile($sourceFileName)
{
    $needCompilation = true;

    if ($GLOBALS['xarTpl_cacheTemplates']) {
        $varDir = xarCoreGetVarDirPath();
        $cacheKey = md5($sourceFileName);
        $cachedFileName = $varDir . '/cache/templates/' . $cacheKey . '.php';
        if (file_exists($cachedFileName)
            && (!file_exists($sourceFileName) || (filemtime($sourceFileName) < filemtime($cachedFileName)))) {
            $needCompilation = false;
        }
    }

    if (!file_exists($sourceFileName) && $needCompilation == true) {
        xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'TEMPLATE_NOT_EXIST', $sourceFileName);
        return;
    }

    //xarLogVariable('needCompilation', $needCompilation, XARLOG_LEVEL_ERROR);
    if ($needCompilation) {
        $blCompiler = xarTpl__getCompilerInstance();
        $templateCode = $blCompiler->compileFile($sourceFileName);
        if (!isset($templateCode) || xarCurrentErrorType() != XAR_NO_EXCEPTION) {
            return; // exception! throw back
        }
        if ($GLOBALS['xarTpl_cacheTemplates']) {
            $fd = fopen($cachedFileName, 'w');
            fwrite($fd, $templateCode);
            fclose($fd);
            // Add an entry into CACHEKEYS
            $varDir = xarCoreGetVarDirPath();
            $fd = fopen($varDir . '/cache/templates/CACHEKEYS', 'a');
            fwrite($fd, $cacheKey. ': '.$sourceFileName . "\n");
            fclose($fd);

            // commented this out for now, a double entry should never occure, eventuall this mayb even become an assert
            // for the details see bug #1600
            //if (!in_array($entry, $file)) {
            //    $fd = fopen($varDir . '/cache/templates/CACHEKEYS', 'a');
            //    fwrite($fd, $entry);
            //    fclose($fd);
            //}
        }
        return $templateCode;
    }

    // Load cached template file
    $output = implode('', file($cachedFileName));

    // Return output
    return $output;
}


/**
 *
 *
 * @package blocklayout
 */
class xarTemplateAttribute {
    var $_name;     // Attribute name
    var $_flags;    // Attribute flags (datatype, required/optional, etc.)

    function xarTemplateAttribute($name, $flags = NULL)
    {
        // See define at top of file
        if (!eregi(XAR_TPL_ATTRIBUTE_REGEX, $name)) {
            $msg = xarML("Illegal attribute name ('#(1)'): Attribute name may contain letters, numbers, _ and -, and must start with a letter.", $name);
            xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'UNKNOWN',
                           new SystemException($msg));
            return;
        }

        if (!is_integer($flags) && $flags != NULL) {
            $msg = xarML("Illegal attribute flags ('#(1)'): flags must be of integer type.", $flags);
            xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'UNKNOWN',
                           new SystemException($msg));
            return;
        }

        $this->_name  = $name;
        $this->_flags = $flags;

        // FIXME: <marco> Why do you need both XAR_TPL_REQUIRED and XAR_TPL_OPTIONAL when XAR_TPL_REQUIRED = ~XAR_TPL_OPTIONAL?
        if ($this->_flags == NULL) {
            $this->_flags = XAR_TPL_ANY|XAR_TPL_REQUIRED;
        } elseif ($this->_flags == XAR_TPL_OPTIONAL) {
            $this->_flags = XAR_TPL_ANY|XAR_TPL_OPTIONAL;
        }
    }

    function getFlags()
    {
        return $this->_flags;
    }

    function getAllowedTypes()
    {
        return ($this->getFlags() & (~ XAR_TPL_OPTIONAL));
    }

    function getName()
    {
        return $this->_name;
    }

    function isRequired()
    {
        return !$this->isOptional();
    }

    function isOptional()
    {
        if ($this->_flags & XAR_TPL_OPTIONAL) {
            return true;
        }
        return false;
    }
}

/**
 *
 *
 * @package blocklayout
 */
class xarTemplateTag {
    var $_name;
    var $_attributes;
    var $_handler;
    var $_module;

    function xarTemplateTag($module, $name, $attributes = array(), $handler = NULL)
    {
        // See defines at top of file
        if (!eregi(XAR_TPL_TAGNAME_REGEX, $name)) {
            $msg = xarML("Illegal tag definition: '#(1)' is an invalid tag name.", $name);
            xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'UNKNOWN',
                           new SystemException($msg));
            $this->_name = NULL;
            return;
        }

        $this->_name = $name;
        $this->_handler = $handler;
        $this->_module = $module;

        // FIXME: pass this along at template registration someday
        if (preg_match("/($module)_(\w+)api_(.*)/",$handler,$matches)) {
            $this->_type = $matches[2];
            $this->_func = $matches[3];
        } else {
            $msg = xarML("Illegal tag definition: '#(1)' is an invalid handler.", $handler);
            xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'UNKNOWN',
                           new SystemException($msg));
            $this->_name = NULL;
            return;
        }

        $this->_attributes = array();

        if (is_array($attributes)) {
            $this->_attributes = $attributes;
        }
    }

    function getAttributes()
    {
        return $this->_attributes;
    }

    function getName()
    {
        return $this->_name;
    }

    function getModule()
    {
    return $this->_module;
    }

    function getHandler()
    {
    return $this->_handler;
    }

    function callHandler($args)
    {
        // FIXME: get rid of this once installation includes the right serialized info
        if (empty($this->_type) || empty($this->_func)) {
            $handler = $this->_handler;
            $module = $this->_module;
            if (preg_match("/($module)_(\w+)api_(.*)/",$handler,$matches)) {
                $this->_type = $matches[2];
                $this->_func = $matches[3];
            } else {
                $msg = xarML("Illegal tag definition: '#(1)' is an invalid handler.", $handler);
                xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'UNKNOWN',
                               new SystemException($msg));
                $this->_name = NULL;
                return;
            }
        }
        return xarModAPIFunc($this->_module, $this->_type, $this->_func, $args);
    }
}

/**
 * Registers a tag to the theme system
 *
 * @access public
 * @param tag_module parent module of tag to register
 * @param tag_name tag to register with the system
 * @param tag_attrs array of attributes associated with tag (xarTemplateAttribute objects)
 * @param tag_handler function of the tag
 * @return bool
 **/
function xarTplRegisterTag($tag_module, $tag_name, $tag_attrs = array(), $tag_handler = NULL)
{
    // Check to make sure tag does not exist first
    if (xarTplGetTagObjectFromName($tag_name) != NULL) {
        // Already registered
        $msg = xarML('<xar:#(1)> tag is already defined.', $tag_name);
        xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'UNKNOWN',
                       new SystemException($msg));
        return false;
    }

    // Validity of tagname is checked in class.
    $tag = new xarTemplateTag($tag_module, $tag_name, $tag_attrs, $tag_handler);
    if(!$tag->getName()) return; // tagname was not set, exception pending

    list($tag_name,
         $tag_module,
         $tag_func,
         $tag_data) = xarVarPrepForStore($tag->getName(),
                                         $tag->getModule(),
                                         $tag->getHandler(),
                                         serialize($tag));

    list($dbconn) = xarDBGetConn();
    $xartable = xarDBGetTables();

    $systemPrefix = xarDBGetSystemTablePrefix();
    $tag_table = $systemPrefix . '_template_tags';

    // Get next ID in table
    $tag_id = $dbconn->GenId($tag_table);

    $query = "INSERT INTO $tag_table
                (xar_id,
                 xar_name,
                 xar_module,
                 xar_handler,
                 xar_data)
              VALUES
                ('$tag_id',
                 '$tag_name',
                 '$tag_module',
                 '$tag_func',
                 '$tag_data');";

    $result = $dbconn->Execute($query);
    if (!$result) return;

    return true;
}

/**
 * Unregisters a tag to the theme system
 *
 * @access public
 * @param tag tag to remove
 * @param tag_func function of the tag to remove
 * @return bool
 **/
function xarTplUnregisterTag($tag_name)
{
    if (!eregi(XAR_TPL_TAGNAME_REGEX, $tag_name)) {
        // throw exception
        return false;
    }

    list($dbconn) = xarDBGetConn();
    $xartable = xarDBGetTables();

    $tag_table = $xartable['template_tags'];

    $query = "DELETE FROM $tag_table WHERE xar_name = '$tag_name';";

    $result = $dbconn->Execute($query);
    if (!$result) return;

    return true;
}

function xarTplCheckTagAttributes($name, $args)
{
    $tag_ref = xarTplGetTagObjectFromName($name);

    if ($tag_ref == NULL) {
        $msg = xarML('<xar:#(1)> tag is not defined.', $name);
        xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'UNKNOWN',
                       new SystemException($msg));
        return;
    }

    $tag_attrs = $tag_ref->getAttributes();

    foreach ($tag_attrs as $attr) {
        $attr_name = $attr->getName();
        if (isset($args[$attr_name])) {
            // check that type matches
            $attr_types = $attr->getAllowedTypes();

            if ($attr_types & XAR_TPL_STRING) {
                continue;
            } elseif (($attr_types & XAR_TPL_BOOLEAN)
                      && eregi ('^(true|false|1|0)$', $args[$attr_name])) {
                continue;
            } elseif (($attr_types & XAR_TPL_INTEGER)
                      && eregi('^\-?[0-9]+$', $args[$attr_name])) {
                continue;
            } elseif (($attr_types & XAR_TPL_FLOAT)
                      && eregi('^\-?[0-9]*.[0-9]+$', $args[$attr_name])) {
                continue;
            }

            // bad type for attribute
            $msg = xarML("'#(1)' attribute in <xar:#(2)> tag does not have correct type. See tag documentation.", $attr_name, $name);
            xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'UNKNOWN',
                            new SystemException($msg));
            return false;
        } elseif ($attr->isRequired()) {
            // required attribute is missing!
            $msg = xarML("Required '#(1)' attribute is missing from <xar:#(2)> tag. See tag documentation.", $attr_name, $name);
            xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'UNKNOWN',
                            new SystemException($msg));
            return false;
        }
    }

    return true;
}

function xarTplGetTagObjectFromName($tag_name)
{
    // cache tags for compile performance
    static $tag_objects = array();
    if (isset($tag_objects[$tag_name])) {
        return $tag_objects[$tag_name];
    }

    list($dbconn) = xarDBGetConn();
    $xartable = xarDBGetTables();

    $systemPrefix = xarDBGetSystemTablePrefix();
    $tag_table = $systemPrefix . '_template_tags';
    $query = "SELECT xar_data FROM $tag_table WHERE xar_name='$tag_name'";

    $result =& $dbconn->SelectLimit($query, 1);
    if (!$result) return;

    if ($result->EOF) {
        $result->Close();
        return NULL; // tag does not exist
    }

    list($obj) = $result->fields;
    $result->Close();

    $obj = unserialize($obj);

    $tag_objects[$tag_name] = $obj;

    return $obj;
}



/**
 * print a template to the screen, compile if necessary
 *
 * @param template_sourcefile The template file to use
 * @param args Variables to pass to the template
 * @param regenerate Forces compilation (optional)
 * @access private
 * @return bool
 **/
function xarTplPrint($template_sourcefile, $args = array())
{
    $template_file = 'cache/templates/' . md5($template_sourcefile) . '.php';

    if (!file_exists($template_sourcefile)) {
        $msg = xarML('Template source not found: #(1).', $template_sourcefile);
        xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'UNKNOWN',
                       new SystemException($msg));
        return;
    }

   if (!xarVarFetch('regenerate','bool', $regenerate)) return;

   if (!file_exists($template_file) ||
        filemtime($template_sourcefile) > filemtime($template_file) ||
        $regenerate) {

        if (!xarTplCompile($template_sourcefile)) {
            return; // Throw back
        }
    }

    extract($args);

    include $template_file;

    return true;
}

function xarTplPrintWidget($module, $widget_sourcefile, $args = array())
{
    $widget_sourcefile = "modules/$module/xarwidgets/$widget_sourcefile";
    return xarTplPrint($widget_sourcefile, $args);
}

?>