<?php
/**
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
 * Defines for tag properties
 *
 */
define('XAR_TPL_TAG_HASCHILDREN'               ,1);
define('XAR_TPL_TAG_HASTEXT'                   ,2);
define('XAR_TPL_TAG_ISASSIGNABLE'              ,4);
define('XAR_TPL_TAG_ISPHPCODE'                 ,8);
define('XAR_TPL_TAG_NEEDASSIGNMENT'            ,16);
define('XAR_TPL_TAG_NEEDPARAMETER'             ,32);
define('XAR_TPL_TAG_NEEDEXCEPTIONSCONTROL'     ,64);

/**
 * Miscelaneous defines
 *
 */
// Let's do this once here, not scattered all over the place
define('XAR_TPL_CACHE_DIR',xarCoreGetVarDirPath() . XARCORE_TPL_CACHEDIR);

/**
 * Initializes the BlockLayout Template Engine
 *
 * @author Paul Rosania <paul@xaraya.com>
 * @author Marco Canini <marco@xaraya.com>
 * @access protected
 * @global bool   xarTpl_cacheTemplates
 * @global string xarTpl_themesBaseDir
 * @global string xarTpl_defaultThemeName
 * @global string xarTpl_additionalStyles
 * @global string xarTpl_doctype
 * @global string xarTpl_JavaScript
 * @param  array  $args                  Elements: themesBaseDir, defaultThemeName, enableTemplateCaching
 * @param  int    $whatElseIsGoingLoaded Bitfield to specify which subsystem will be loaded.
 * @return bool true
 */
function xarTpl_init($args, $whatElseIsGoingLoaded)
{
    $GLOBALS['xarTpl_themesBaseDir']   = $args['themesBaseDirectory'];
    $GLOBALS['xarTpl_defaultThemeDir'] = $args['defaultThemeDir'];
    $GLOBALS['xarTpl_cacheTemplates']  = $args['enableTemplatesCaching'];
    $GLOBALS['xarTpl_generateXMLURLs'] = $args['generateXMLURLs'];
    // set when page template root tag is compiled (dtd attribute value)
    $GLOBALS['xarTpl_doctype'] = '';

    if (!xarTplSetThemeDir($args['defaultThemeDir'])) {
        // If there is no theme, there is no page template, we dont know what to do now.
        xarCore_die("xarTpl_init: Nonexistent theme directory '" . $args['defaultThemeDir'] ."'");
    }
    if (!xarTplSetPageTemplateName('default')) {
        // If there is no page template, we can't show anything
        xarCore_die("xarTpl_init: Nonexistent default.xt page in theme directory '". xarTplGetThemeDir() ."'");
    }

    if ($GLOBALS['xarTpl_cacheTemplates']) {
        if (!is_writeable(XAR_TPL_CACHE_DIR)) {
            $msg = "xarTpl_init: Cannot write in '". XAR_TPL_CACHEDIR ."' directory '"
                . XAR_TPL_CACHE_DIR . "', but the setting: 'cache templates' is set to 'On'.\n"
                ."Either change the permissions on the mentioned file/directory or set template caching to 'Off' (not recommended).";
            $GLOBALS['xarTpl_cacheTemplates'] = false;
            // Set the exception, but do not return just yet, because we *can* continue.
            xarErrorSet(XAR_SYSTEM_EXCEPTION, 'CONFIG_ERROR', $msg);
        }
    }

    $GLOBALS['xarTpl_additionalStyles'] = '';

    // This is wrong here as well, but it's better at least than in xarMod
    include "includes/xarTheme.php";

    // NOTE: starting from 0.9.11 we attempt to link core css to any css-aware xhtml theme
    // immediate goal is elimination of inline styles, consistency and other core UI related issues
    // no need to init anything, the css tags api is handling everything css related now.. 
    // DONE: removed all but legacy css handling from core to themes module
    
    // Subsystem initialized, register a handler to run when the request is over
    //register_shutdown_function ('xarTemplate__shutdown_handler');
    return true;
}

/**
 * Shutdown handler for the template subsystem
 *
 * @access private
 */
function xarTemplate__shutdown_handler()
{
    //xarLogMessage("xarTemplate shutdown handler");
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
    if (function_exists('xarModGetVar')) {
        $defaultTheme = xarModGetVar('themes', 'default');
        if (!empty($defaultTheme)) return $defaultTheme;
    }
    return $GLOBALS['xarTpl_themeName'];
}

/**
 * Set theme name
 *
 * @access public
 * @global string xarTpl_themesBaseDir
 * @global string xarTpl_themeName
 * @param  string $themeName Themename to set
 * @return bool
 */
function xarTplSetThemeName($themeName)
{
    assert('$themeName != "" && $themeName{0} != "/"');
    if (!file_exists($GLOBALS['xarTpl_themesBaseDir'].'/'.$themeName)) {
        return false;
    }

    xarTpl__SetThemeNameAndDir($themeName);
    return true;
}

/**
 * Set theme dir
 *
 * @access public
 * @global string xarTpl_themesBaseDir
 * @global string xarTpl_themeDir
 * @param  string themeDir
 * @return bool
 */
function xarTplSetThemeDir($themeDir)
{
    assert('$themeDir != "" && $themeDir{0} != "/"');
    if (!file_exists($GLOBALS['xarTpl_themesBaseDir'].'/'.$themeDir)) {
        return false;
    }

    xarTpl__SetThemeNameAndDir($themeDir);
    return true;
}

/**
 * Private helper function for the xarTplSetThemeName and xarTplSetThemeDir
 *
 * @access private
 * @param  string $name Name of the theme 
 * @todo theme name and dir are not required to be identical
 * @return void
 */
function xarTpl__SetThemeNameAndDir($name)
{
    // dir and name are still required to be the same
    $GLOBALS['xarTpl_themeName'] = $name;
    $GLOBALS['xarTpl_themeDir']  = $GLOBALS['xarTpl_themesBaseDir'] . '/' . $name;
}

/**
 * Get theme directory
 *
 * @access public
 * @global string xarTpl_themeDir
 * @return sring  Theme directory
 */
function xarTplGetThemeDir()
{
    return $GLOBALS['xarTpl_themeDir'];
}

/**
 * Get page template name
 *
 * @access public
 * @global string xarTpl_pageTemplateName
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
 * @param  string $templateName Name of the page template
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
 * Get doctype declared by page template
 *
 * @access public
 * @global string xarTpl_doctype
 * @return string doctype identifier
 */
function xarTplGetDoctype()
{
    return $GLOBALS['xarTpl_doctype'];
}

/**
 * Set doctype declared by page template
 *
 * @access public
 * @global string xarTpl_doctype
 * @param  string $doctypeName Identifier string of the doctype
 * @return bool
 */
function xarTplSetDoctype($doctypeName)
{
    assert('is_string($doctypeName); /* doctype should always be a string */');
    $GLOBALS['xarTpl_doctype'] = $doctypeName;
    return true;
}

/**
 * Set page title
 *
 * @access public
 * @global string xarTpl_pageTitle
 * @param  string $title
 * @param  string $module
 * @todo   this needs to be moved into the templating domain somehow
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
            // FIXME: the ucwords is layout stuff which doesn't belong here
            $module = ucwords(xarModGetDisplayableName());
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
    if(isset($GLOBALS['xarTpl_pageTitle'])) {
        return $GLOBALS['xarTpl_pageTitle'];
    }
    return '';
}

/**
 * Add stylesheet link for a module (after rc3 this function is a legacy)
 *
 * @access public (deprecated - all CSS issues are normally handled by the css classlib via bl tags)
 * @param  string $module
 * @param  string $file
 * @param  string $fileext
 * @param  string $themefolder ('' or path no leading or trailing /, )
 * @media  string $media (multiple values supported as a comma separated list "screen, print")
 * @todo   can deprecate after adoption of template css tags
 * @return bool
 */
function xarTplAddStyleLink($module = null, $file = null, $fileext = null, $themefolder = null, $media = null, $scope = 'module')
{     
    $method = 'link';
    $args = compact('module', 'file', 'fileext', 'themefolder', 'media', 'scope', 'method');
    
    // make sure we can use css object
    require_once "modules/themes/xarclass/xarcss.class.php";
    $obj = new xarCSS($args);
    return $obj->run_output();
}

/**
 * Add JavaScript code to template output **deprecated**
 *
 * @access public
 * @param  string $position Either 'head' or 'body'
 * @param  string $owner    Who produced this snippet?
 * @param  string $code     The JavaScript Code itself
 * @deprec 2004-03-20       This is now handled by a custom tag of the base module
 * @return bool
 */
function xarTplAddJavaScriptCode($position, $owner, $code)
{
    assert('$position == "head" || $position == "body"');
    return xarTplAddJavaScript($position, 'code', "<!-- JavaScript code from {$owner} -->\n" . $code);
}

/**
 * Add JavaScript code or links to template output
 *
 * @access public
 * @global array  xarTpl_JavaScript
 * @param  string $position         Either 'head' or 'body'
 * @param  string $type             Either 'src' or 'code'
 * @param  string $data             pathname or raw JavaScript
 * @param  string $index            optional (unique key and/or ordering)
 * @return bool
 */
function xarTplAddJavaScript($position, $type, $data, $index = '')
{
    if (empty($position) || empty($type) || empty($data)) {return;}

    //Do lazy initialization of the array. There are instances of the logging system
    //where we need to use this function before the Template System was initialized
    //Maybe this can be used with a new shutdown event (not based on the 
    // php's register_shutdown_function) as at that time it's already too late to be able
    // to log anything
    if (!isset($GLOBALS['xarTpl_JavaScript'])) {
        // Initialise the JavaScript array. Start with placeholders for the head and body.
        $GLOBALS['xarTpl_JavaScript'] = array('head'=>array(), 'body'=>array());
    }

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
 * @global array  xarTpl_JavaScript 
 * @param  string $position
 * @param  string $index   
 * @return array
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
 * @author Paul Rosania <paul@xaraya.com>
 * @author Marco Canini <marco@xaraya.com>
 * @access public
 * @param  string $modName      the module name
 * @param  string $modType      user|admin
 * @param  string $funcName     module function to template
 * @param  array  $tplData      arguments for the template
 * @param  string $templateName string the specific template to call
 * @return string xarTpl__executeFromFile($sourceFileName, $tplData)
 */
function xarTplModule($modName, $modType, $funcName, $tplData = array(), $templateName = NULL)
{
    if (!empty($templateName)) {
        $templateName = xarVarPrepForOS($templateName);
    }

    // Basename of module template is apitype-functioname
    $tplBase        = "$modType-$funcName";

    // Get the right source filename
    $sourceFileName = xarTpl__GetSourceFileName($modName, $tplBase, $templateName);

    // Common data for BL
    $tplData['_bl_module_name'] = $modName;
    $tplData['_bl_module_type'] = $modType;
    $tplData['_bl_module_func'] = $funcName;
    $tpl = (object) null;
    $tpl->pageTitle = xarTplGetPageTitle();
    $tplData['tpl'] = $tpl;

    // TODO: make this work different, for example:
    // 1. Only create a link somewhere on the page, when clicked opens a page with the variables on that page
    // 2. Create a page in the themes module with an interface
    // 3. Use 1. to link to 2.
    if (function_exists('xarModGetVar')){
        $var_dump = xarModGetVar('themes', 'var_dump');
        if ($var_dump == true){
            if (function_exists('var_export')) {
                $pre = var_export($tplData, true); 
                echo "<pre>$pre</pre>";
            } else {
                echo '<pre>',var_dump($tplData),'</pre>';
            }
        }
    }

    return xarTpl__executeFromFile($sourceFileName, $tplData);
}

/**
 * Renders a block content through a block template.
 *
 * @author Paul Rosania <paul@xaraya.com>
 * @author Marco Canini <marco@xaraya.com>
 * @access public
 * @param  string $modName   the module name
 * @param  string $blockType the block type (xar_block_types.xar_type)
 * @param  array  $tplData   arguments for the template
 * @param  string $tplName   the specific template to call
 * @param  string $tplBase   the base name of the template (defaults to $blockType)
 * @return string xarTpl__executeFromFile($sourceFileName, $tplData)
 */
function xarTplBlock($modName, $blockType, $tplData = array(), $tplName = NULL, $tplBase = NULL)
{
    if (!empty($tplName)) {
        $tplName = xarVarPrepForOS($tplName);
    }

    // Basename of block can be overridden
    $templateBase   = xarVarPrepForOS(empty($tplBase) ? $blockType : $tplBase);

    // Get the right source filename
    $sourceFileName = xarTpl__GetSourceFileName($modName, $templateBase, $tplName, 'blocks');

    return xarTpl__executeFromFile($sourceFileName, $tplData);
}
/**
 * Renders a property through a property template.
 *
 * @author Marcel van der Boom <marcel@xaraya.com>
 * @access public
 * @param  string $modName      the module name owning the property, with fall-back to dynamicdata
 * @param  string $propertyName the name of the property type, or some other name specified in BL tag or API call
 * @param  string $tplType      the template type to render ( showoutput(default)|showinput|showhidden|validation|label )
 * @param  array  $tplData      arguments for the template
 * @param  string $tplBase      the template type can be overridden too ( unused )
 * @return string xarTpl__executeFromFile($sourceFileName, $tplData)
 */
function xarTplProperty($modName, $propertyName, $tplType = 'showoutput', $tplData = array(), $tplBase = NULL)
{
    $tplType = xarVarPrepForOS($tplType);

    // Template type for the property can be overridden too (currently unused)
    $templateBase   = xarVarPrepForOS(empty($tplBase) ? $tplType : $tplBase);

    // Get the right source filename
    $sourceFileName = xarTpl__GetSourceFileName($modName, $templateBase, $propertyName, 'properties');

    // Final fall-back to default template in dynamicdata
    if ((empty($sourceFileName) || !file_exists($sourceFileName)) &&
        $modName != 'dynamicdata') {
        $sourceFileName = xarTpl__GetSourceFileName('dynamicdata', $templateBase, $propertyName, 'properties');
    }

    return xarTpl__executeFromFile($sourceFileName, $tplData);
}

/**
 * Renders an object through an object template (TODO)
 *
 * @author Marcel van der Boom <marcel@xaraya.com>
 * @access public
 * @param  string $modName      the module name owning the object, with fall-back to dynamicdata
 * @param  string $objectName   the name of the object, or some other name specified in BL tag or API call
 * @param  string $tplType      the template type to render ( showdisplay(default)|showview|showform|showlist )
 * @param  array  $tplData      arguments for the template
 * @param  string $tplBase      the template type can be overridden too ( unused )
 * @return string xarTpl__executeFromFile($sourceFileName, $tplData)
 */
function xarTplObject($modName, $objectName, $tplType = 'showdisplay', $tplData = array(), $tplBase = NULL)
{
    $tplType = xarVarPrepForOS($tplType);

    // Template type for the object can be overridden too (currently unused)
    $templateBase   = xarVarPrepForOS(empty($tplBase) ? $tplType : $tplBase);

    // Get the right source filename
    $sourceFileName = xarTpl__GetSourceFileName($modName, $templateBase, $objectName, 'objects');

    // Final fall-back to default template in dynamicdata
    if ((empty($sourceFileName) || !file_exists($sourceFileName)) &&
        $modName != 'dynamicdata') {
        $sourceFileName = xarTpl__GetSourceFileName('dynamicdata', $templateBase, $objectName, 'objects');
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
 * @param   string $modImage the module image url relative to xarimages/
 * @param   string $modName  the module to check for the image <optional>
 * @return  string $theme    image url if it exists or module image url if not, or NULL if neither found
 *
 * @todo    provide examples, improve description, add functionality
 * @todo    provide XML URL override flag
 * @todo    XML encode absolute URIs too?
*/
function xarTplGetImage($modImage, $modName = NULL)
{
    // return absolute URIs and URLs "as is"
    if (empty($modImage) || substr($modImage,0,1) == '/' || preg_match('/^https?\:\/\//',$modImage)) {
        return $modImage;
    }

    // obtain current module name if not specified
    // FIXME: make a fallback for weird requests
    if(!isset($modName)){
        list($modName) = xarRequestGetInfo();
    }

    // get module directory (could be different from module name)
    if(function_exists('xarMod_getBaseInfo')) {
        $modBaseInfo = xarMod_getBaseInfo($modName);
        if (!isset($modBaseInfo)) return; // throw back
        $modOsDir = $modBaseInfo['osdirectory'];
    } else {
        // Assume dir = modname
        $modOsDir = $modName;
    }

    // relative url to the current module's image
    $moduleImage = 'modules/'.$modOsDir.'/xarimages/'.$modImage;

    // obtain current theme directory
    $themedir = xarTplGetThemeDir();

    // relative url to the replacement image in current theme
    $themeImage = $themedir . '/modules/'.$modOsDir.'/images/'.$modImage;

    $return = NULL;

    // check if replacement image exists in the theme
    if (file_exists($themeImage)) {
        // image found, return its path in the theme
        $return = $themeImage;
    } elseif (file_exists($moduleImage)) {
        // image found, return it's path in the module
        $return = $moduleImage;
    }

    // Return as an XML URL if required.
    // This will generally have little effect, but is here for
    // completeness to support alternative types of URL.
    if (isset($return) && $GLOBALS['xarTpl_generateXMLURLs']) {
        $return = htmlspecialchars($return);
    }

    return $return;
}

/**
 * Creates pager information with no assumptions to output format.
 *
 * @author Jason Judge
 * @since 2003/10/09
 * @access public
 * @param integer $startNum     start item
 * @param integer $total        total number of items present
 * @param integer $itemsPerPage number of links to display (default=10)
 * @param integer $blockOptions number of pages to display at once (default=10) or array of advanced options
 *
 * @todo  Move this somewhere else, preferably transparent and a widget (which might be mutually exclusive)
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
        $urlItemMatchEnc = rawurlencode($urlItemMatch);
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
    if (xarCore_GetCached('Pager.core', 'request') == $request) {
        return xarCore_GetCached('Pager.core', 'details');
    }

    // Record the values in this request.
    xarCore_SetCached('Pager.core', 'request', $request);

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
    $data['middleurls'] = array();
    $pageNum = (int)ceil(($blockFirstItem - $firstItem + 1) / $itemsPerPage) + $firstPage - 1;
    for ($i = $blockFirstItem; $i <= $blockLastItem; $i += $itemsPerPage) {
        if (!empty($urltemplate)) {
            $data['middleurls'][$pageNum] = str_replace(array($urlItemMatch,$urlItemMatchEnc), $i, $urltemplate);
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
        $data['firsturl'] = str_replace(array($urlItemMatch,$urlItemMatchEnc), $data['firstpage'], $urltemplate);
        $data['lasturl'] = str_replace(array($urlItemMatch,$urlItemMatchEnc), $data['lastpage'], $urltemplate);
    }

    $data['firstpagenum'] = $firstPage;
    $data['lastpagenum'] = ($totalPages + $firstPage - 1);

    // Data for previous page of items.
    if ($currentPage > $firstPage) {
        $data['prevpageitems'] = $itemsPerPage;
        $data['prevpage'] = ($pageFirstItem - $itemsPerPage);
        if (!empty($urltemplate)) {
            $data['prevpageurl'] = str_replace(array($urlItemMatch,$urlItemMatchEnc), $data['prevpage'], $urltemplate);
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
            $data['nextpageurl'] = str_replace(array($urlItemMatch,$urlItemMatchEnc), $data['nextpage'], $urltemplate);
        }
    } else {
        $data['nextpageitems'] = 0;
    }

    // Data for previous block of pages.
    if ($currentBlock > $firstBlock) {
        $data['prevblockpages'] = $blockSize;
        $data['prevblock'] = ($blockFirstItem - $itemsPerBlock);
        if (!empty($urltemplate)) {
            $data['prevblockurl'] = str_replace(array($urlItemMatch,$urlItemMatchEnc), $data['prevblock'], $urltemplate);
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
            $data['nextblockurl'] = str_replace(array($urlItemMatch,$urlItemMatchEnc), $data['nextblock'], $urltemplate);
        }
    } else {
        $data['nextblockpages'] = 0;
    }

    // Cache all the pager details.
    xarCore_SetCached('Pager.core', 'details', $data);

    return $data;
}

/**
 * Equivalent of pnHTML()'s Pager function (to get rid of pnHTML calls in modules while waiting for widgets)
 *
 * @author Jason Judge
 * @since 1.13 - 2003/10/09
 * @access public
 * @param integer $startnum     start item
 * @param integer $total        total number of items present
 * @param string  $urltemplate  template for url, will replace '%%' with item number
 * @param integer $perpage      number of links to display (default=10)
 * @param integer $blockOptions number of pages to display at once (default=10) or array of advanced options
 * @param integer $template     alternative template name within base/user (default 'pager')
 *
 * @todo Move this somewhere else
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
        xarCore_SetCached('Pager.first', 'leftarrow', $data['firsturl']);
    }

    // Links for next block of pages.
    if ($data['nextblockpages'] > 0) {
        xarCore_SetCached('Pager.last', 'rightarrow', $data['lasturl']);
    }

    return trim(xarTplModule('base', 'pager', $template, $data));
}

/**
 * Execute a pre-compiled template string with the supplied template variables
 *
 * @access public
 * @param  string $templateCode pre-compiled template code (see xarTplCompileString)
 * @param  array  $tplData      template variables
 * @return string filled-in template
 */
function xarTplString($templateCode, $tplData)
{
    return xarTpl__execute($templateCode, $tplData);
}

/**
 * Execute a specific template file with the supplied template variables
 *
 * @access public
 * @param  string $fileName location of the template file
 * @param  array  $tplData  template variables
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
 * @param  string $templateSource template source
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
 * @author Paul Rosania <paul@xaraya.com>
 * @author Marco Canini <marco@xaraya.com>
 * @access protected
 * @global string xarTpl_additionalStyles
 * @param  string $mainModuleOutput       the module output
 * @param  string $otherModulesOutput 
 * @param  string $templateName           the template page to use
 * @return string
 *
 * @todo Needs a rewrite, i.e. finalisation of tplOrder scenario
 */
function xarTpl_renderPage($mainModuleOutput, $otherModulesOutput = NULL, $templateName = NULL)
{
    if (empty($templateName)) {
        $templateName = xarTplGetPageTemplateName();
    }

    // FIXME: can we trust templatename here? and eliminate the dependency with xarVar?
    $templateName = xarVarPrepForOS($templateName);
    $sourceFileName = xarTplGetThemeDir() . "/pages/$templateName.xt";

    $tpl = (object) null; // Create an object to hold the 'specials'
    $tpl->pageTitle = xarTplGetPageTitle();
    // leaving it ON here for pure legacy support, css classlib in themes mod must have legacy enabled to support it
    // TODO: remove whenever the legacy can be dropped <andy>
    
    // NOTE: This MUST be a reference, since we havent filled the global yet at this point
    $tpl->additionalStyles =& $GLOBALS['xarTpl_additionalStyles'];
    
    $tplData = array(
        'tpl'                      => $tpl,
        '_bl_mainModuleOutput'     => $mainModuleOutput,
    );

    if (xarMLS_loadTranslations(XARMLS_DNTYPE_THEME, xarTplGetThemeName(), 'themes:pages', $templateName) === NULL) return;

    return xarTpl__executeFromFile($sourceFileName, $tplData);
}

/**
 * Render a block box
 *
 * @access protected
 * @param  array  $blockInfo  Information on the block
 * @param  string $templateName string
 * @return bool xarTpl__executeFromFile($sourceFileName, $blockInfo)
 *
 * @todo the search logic for the templates can perhaps use the private function?
 * @todo fallback to some internal block box template?
 */
function xarTpl_renderBlockBox($blockInfo, $templateName = NULL)
{
    // FIXME: can we trust templatename here? and eliminate the dependency with xarVar?
    $templateName = xarVarPrepForOS($templateName);
    $themeDir = xarTplGetThemeDir();

    if (!empty($templateName) && file_exists("$themeDir/blocks/$templateName.xt")) {
        $sourceFileName = "$themeDir/blocks/$templateName.xt";
    } else {
        // We must fall back to the default, as the template passed in could be the group
        // name, allowing an optional template to be utilised.
        $templateName = 'default';
        $sourceFileName = "$themeDir/blocks/default.xt";
    }
    if (xarMLS_loadTranslations(XARMLS_DNTYPE_THEME, xarTplGetThemeName(), 'themes:blocks', $templateName) === NULL) return;
    return xarTpl__executeFromFile($sourceFileName, $blockInfo);
}

/**
 * Include a subtemplate from the theme space
 *
 * @access protected
 * @param  string $templateName Basically handler function for <xar:template type="theme".../>
 * @param  array  $tplData      template variables
 * @return string
 */
function xarTpl_includeThemeTemplate($templateName, $tplData)
{
    // FIXME: can we trust templatename here? and eliminate the dependency with xarVar?
    $templateName = xarVarPrepForOS($templateName);
    $sourceFileName = xarTplGetThemeDir() ."/includes/$templateName.xt";
    if (xarMLS_loadTranslations(XARMLS_DNTYPE_THEME, xarTplGetThemeName(), 'themes:includes', $templateName) === NULL) return;
    return xarTpl__executeFromFile($sourceFileName, $tplData);
}

/**
 * Include a subtemplate from the module space
 *
 * @access protected
 * @param  string $modName      name of the module from which to include the template
 * @param  string $templateName Basically handler function for <xar:template type="module".../>
 * @param  array  $tplData      template variables
 * @return string
 */
function xarTpl_includeModuleTemplate($modName, $templateName, $tplData)
{
    // FIXME: can we trust templatename here? and eliminate the dependency with xarVar?
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
    return xarTpl__Compiler::instance();
}

/**
 * Execute Template, i.e. run the compiled php code of a cached template
 *
 * @access private
 * @param  string $templateCode   Templatecode to execute
 * @param  array  $tplData        Template variables
 * @param  string $sourceFileName 
 * @return string output
 *
 * @todo Can we migrate the eval() out, as that is hard to cache?
 * @todo $sourceFileName looks wrong here
 */
function xarTpl__execute($templateCode, $tplData, $sourceFileName = '')
{
    assert('is_array($tplData); /* Template data should always be passed in an array */');

    //POINT of ENTRY for cleaning variables
    // We need to be able to figure what is the template output type: RSS, XHTML, XML or whatever

    $tplData['_bl_data'] = $tplData;
    extract($tplData, EXTR_OVERWRITE);

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
 * Execute template from file
 *
 * @access private
 * @global bool   xarTpl_cacheTemplates
 * @param  string $sourceFileName       From which file do we want to execute?
 * @param  array  $tplData              Template variables
 * @return mixed
 *
 * @todo  inserting the header part like this is not output agnostic
 * @todo  insert log warning when double entry in cachekeys occurs? (race condition)
 * @todo  make the checking whethet templatecode is set more robst (related to templated exception handling)
 */
function xarTpl__executeFromFile($sourceFileName, $tplData)
{
    assert('is_array($tplData); /* Template data should always be passed in an array */');

    $needCompilation = true;

    if ($GLOBALS['xarTpl_cacheTemplates']) {
        $cacheKey = xarTpl__GetCacheKey($sourceFileName);
        $cachedFileName = XAR_TPL_CACHE_DIR . '/' . $cacheKey . '.php';
        if (file_exists($cachedFileName)
            && (!file_exists($sourceFileName) || (filemtime($sourceFileName) < filemtime($cachedFileName)))) {
            $needCompilation = false;
        }
    }

    if (!file_exists($sourceFileName) && $needCompilation == true) {
        xarErrorSet(XAR_SYSTEM_EXCEPTION, 'TEMPLATE_NOT_EXIST', $sourceFileName);
        return;
    }
    xarLogMessage("Using template : $sourceFileName");
    //xarLogVariable('needCompilation', $needCompilation, XARLOG_LEVEL_ERROR);
    if ($needCompilation) {
        $blCompiler = xarTpl__getCompilerInstance();
        $lasterror = xarCurrentError();
        $templateCode = $blCompiler->compileFile($sourceFileName);
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
            xarTpl__SetCacheKey($sourceFileName);
        } else {
            return xarTpl__execute($templateCode, $tplData, $sourceFileName);
        }
    }

    // $cachedFileName should have a value from this point on
    // see the return statement couple of lines back.

    //POINT of ENTRY for cleaning variables
    // We need to be able to figure what is the template output type: RSS, XHTML, XML or whatever
    $tplData['_bl_data'] = $tplData;
    extract($tplData, EXTR_OVERWRITE);

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
 * Determine the template sourcefile to use
 *
 * Based on the module, the basename for the template
 * a possible overribe and a subpart and the active
 * theme, determine the template source we should use and loads
 * the appropriate translations based on the outcome.
 *
 * @param  string $modName      Module name doing the request
 * @param  string $tplBase      The base name for the template
 * @param  string $templateName The name for the template to use if any
 * @param  string $tplSubPart   A subpart ('' or 'blocks' or 'properties')
 * @return string
 *
 * @todo do we need to load the translations here or a bit later? (here:easy, later: better abstraction)
 */
function xarTpl__getSourceFileName($modName,$tplBase, $templateName = NULL, $tplSubPart = '') 
{
    if(function_exists('xarMod_getBaseInfo')) {
        if(!($modBaseInfo = xarMod_getBaseInfo($modName))) return;
        $modOsDir = $modBaseInfo['osdirectory'];
    } elseif(!empty($modName)) {
        $modOsDir = $modName;
    }

    // For modules: {tplBase} = {modType}-{funcName}
    // For blocks : {tplBase} = {blockType} or overridden value
    // For props  : {tplBase} = {propertyName} or overridden value

    // Template search order:
    // 1. {theme}/modules/{module}/{tplBase}-{templateName}.xt
    // 2. modules/{module}/xartemplates/{tplBase}-{templateName}.xd
    // 3. {theme}/modules/{module}/{tplBase}.xt
    // 4. modules/{module}/xartemplates/{tplBase}.xd
    // 5. complain (later on)

    $tplThemesDir = xarTplGetThemeDir();
    $tplBaseDir   = "modules/$modOsDir";
    $use_internal = false;
    unset($sourceFileName);

    // xarLogMessage("TPL: 1. $tplThemesDir/$tplBaseDir/$tplSubPart/$tplBase-$templateName.xt")
    // xarLogMessage("TPL: 2. $tplBaseDir/xartemplates/$tplSubPart/$tplBase-$templateName.xd")
    // xarLogMessage("TPL: 3. $tplThemesDir/$tplBaseDir/$tplSubPart/$tplBase.xt")
    // xarLogMessage("TPL: 4. $tplBaseDir/xartemplates/$tplSubPart/$tplBase.xd")

    if(!empty($templateName) && 
        file_exists($sourceFileName = "$tplThemesDir/$tplBaseDir/$tplSubPart/$tplBase-$templateName.xt")) {
        $tplBase .= "-$templateName";
    } elseif(!empty($templateName) && 
        file_exists($sourceFileName = "$tplBaseDir/xartemplates/$tplSubPart/$tplBase-$templateName.xd")) {
        $use_internal = true;
        $tplBase .= "-$templateName";
    } elseif(
        file_exists($sourceFileName = "$tplThemesDir/$tplBaseDir/$tplSubPart/$tplBase.xt")) {
        ;
    } elseif(
        file_exists($sourceFileName = "$tplBaseDir/xartemplates/$tplSubPart/$tplBase.xd")) {
        $use_internal = true;
    } else {
        // CHECKME: should we do something here ? At the moment, translations still get loaded,
        //          the (invalid) $sourceFileName gets passed back to xarTpl*, and we'll get
        //          an exception when it's passed to xarTpl__executeFromFile().
        //          We probably don't want to throw an exception here, but we might return
        //          now, or have some final fall-back template in base (resp. DD for properties)
    }
    // Subpart may have been empty, 
    $sourceFileName = str_replace('//','/',$sourceFileName);
    // assert('isset($sourceFileName); /* The source file for the template has no value in xarTplModule */');

    // Load the appropriate translations
    if($use_internal) {
        $domain  = XARMLS_DNTYPE_MODULE; $instance= $modName;
        $context = rtrim("modules:templates/$tplSubPart",'/');
    } else {
        $domain = XARMLS_DNTYPE_THEME; $instance = $GLOBALS['xarTpl_themeName'];
        $context = rtrim("themes:modules/$modName/$tplSubPart",'/');
    }
    if (xarMLS_loadTranslations($domain, $instance, $context, $tplBase) === NULL) return;

    return $sourceFileName;
}


/**
 * Output template
 *
 * @access private
 * @param  string $sourceFileName
 * @param  string $tplOutput 
 * @return void
 *
 * @todo Rethink this function, it contains hardcoded xhtml
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
            $finalTemplate .= "<!-- start: " . $sourceFileName . " -->\n";
        $finalTemplate .= $tplOutput;
        $finalTemplate .= "<!-- end: " . $sourceFileName . " -->\n";
    } else {
        $finalTemplate .= $tplOutput;
    }
    return $finalTemplate;
}

/**
 * Output php comment block in templates
 *
 * @access private
 * @global int xarTpl_showPHPCommentBlockInTemplates int
 * @return int value of xarTpl_showPHPCommentBlockInTemplates (0 or 1)
 */
function xarTpl_outputPHPCommentBlockInTemplates()
{
    if (!isset($GLOBALS['xarTpl_showPHPCommentBlockInTemplates'])) {
        // Default to not show the comments
        $GLOBALS['xarTpl_showPHPCommentBlockInTemplates'] = 0;
        // CHECKME: not sure if this is needed, e.g. during installation
        if (function_exists('xarModGetVar')){
            $showphpcbit = xarModGetVar('themes', 'ShowPHPCommentBlockInTemplates');
            if (!empty($showphpcbit)) {
                $GLOBALS['xarTpl_showPHPCommentBlockInTemplates'] = 1;
            }
        }
    }
    return $GLOBALS['xarTpl_showPHPCommentBlockInTemplates'];
}

/**
 * Output template filenames
 *
 * @access private
 * @global int xarTpl_showTemplateFilenames
 * @return int value of xarTpl_showTemplateFilenames (0 or 1)
 * 
 * @todo Check whether the check for xarModGetVar is needed
 * @todo Rethink this function
 */
function xarTpl_outputTemplateFilenames()
{
    if (!isset($GLOBALS['xarTpl_showTemplateFilenames'])) {
        // Default to not showing it
        $GLOBALS['xarTpl_showTemplateFilenames'] = 0;
        // CHECKME: not sure if this is needed, e.g. during installation
        if (function_exists('xarModGetVar')){
            $showtemplates = xarModGetVar('themes', 'ShowTemplates');
            if (!empty($showtemplates)) {
                $GLOBALS['xarTpl_showTemplateFilenames'] = 1;
            }
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
 * @access private
 * @param  string $sourceFileName
 * @param  string $tplOutput 
 * @return bool found header content
 *
 * @todo it is possible that the first regex <!DOCTYPE[^>].*]> is too
 *       greedy in more complex xml documents and others.
 * @todo The doctype of the output belongs in a template somewhere (probably the xar:blocklayout tag, as an attribute
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
            // FIXME: the next line assumes that we are not in a comment already, no way of knowing that, 
            // keep the functionality for now, but dont change more than necessary (see bug #3559)
            // $startComment = '<!-- start(output actually commenced before header(s)): ' . $sourceFileName . ' -->';
            $startComment ='';
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
 * @global bool xarTpl_cacheTemplates Do we cache templates?
 * @param  string $sourceFileName     From which file do we want to load?
 * @return mixed
 */
function xarTpl__loadFromFile($sourceFileName)
{
    $needCompilation = true;

    if ($GLOBALS['xarTpl_cacheTemplates']) {
        $cacheKey = xarTpl__SetCacheKey($sourceFileName);
        $cachedFileName = XAR_TPL_CACHE_DIR . '/' . $cacheKey . '.php';
        if (file_exists($cachedFileName)
            && (!file_exists($sourceFileName) || (filemtime($sourceFileName) < filemtime($cachedFileName)))) {
            $needCompilation = false;
        }
    }

    if (!file_exists($sourceFileName) && $needCompilation == true) {
        xarErrorSet(XAR_SYSTEM_EXCEPTION, 'TEMPLATE_NOT_EXIST', $sourceFileName);
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
            xarTpl__SetCacheKey($sourceFileName);
        }
        return $templateCode;
    }

    // Load cached template file
    $output = implode('', file($cachedFileName));

    // Return output
    return $output;
}

/**
 * Set the cache key for a sourcefile
 *
 * @access private
 * @param  string $cacheKey        The key to add
 * @param  string $sourceFileName  For which file are we entering the key?
 * @return boolean
 */
function xarTpl__SetCacheKey($sourceFileName) 
{
    $cacheKey = xarTpl__getCacheKey($sourceFileName);
    $fd = fopen(XAR_TPL_CACHE_DIR . '/CACHEKEYS', 'a');
    fwrite($fd, $cacheKey. ': '.$sourceFileName . "\n");
    fclose($fd);
    return true;
}

/** Get the cache key for a sourcefile
 *
 * @access private
 * @param  string $sourceFileName  For which file do we need the key?
 * @return string                  The cache key for this sourcefilename
 *
 * @todo  consider using a static array
 */
function xarTpl__getCacheKey($sourceFileName)
{
    return md5($sourceFileName);
}

/**
 * Model of a tag attribute
 *
 * Mainly uses fro custom tags
 *
 * @package blocklayout
 * @access protected
 * 
 * @todo see FIXME
 */
class xarTemplateAttribute 
{
    var $_name;     // Attribute name
    var $_flags;    // Attribute flags (datatype, required/optional, etc.)

    function xarTemplateAttribute($name, $flags = NULL)
    {
        // See defines at top of file
        if (!eregi(XAR_TPL_ATTRIBUTE_REGEX, $name)) {
            $msg = xarML("Illegal attribute name ('#(1)'): Attribute name may contain letters, numbers, _ and -, and must start with a letter.", $name);
            xarErrorSet(XAR_SYSTEM_EXCEPTION, 'UNKNOWN',
                           new SystemException($msg));
            return;
        }

        if (!is_integer($flags) && $flags != NULL) {
            $msg = xarML("Illegal attribute flags ('#(1)'): flags must be of integer type.", $flags);
            xarErrorSet(XAR_SYSTEM_EXCEPTION, 'UNKNOWN',
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
 * Model of a template tag
 *
 * Only used for custom tags atm
 * @package blocklayout
 * @access  protected
 * 
 * @todo Make this more general
 * @todo _module, _type and _func and _handler introduce unneeded redundancy
 * @todo pass handler check at template registration someday (<mrb>what does this mean?)
 */
class xarTemplateTag 
{
    var $_name = NULL;          // Name of the tag
    var $_attributes = array(); // Array with the supported attributes
    var $_handler = NULL;       // Name of the handler function
    var $_module;               // Modulename
    var $_type;                 // Type of the handler (user/admin etc.)
    var $_func;                 // Function name
    // properties for registering what kind of tag we have here
    var $_hasChildren = false;
    var $_hasText = false;
    var $_isAssignable = false;
    var $_isPHPCode = true;
    var $_needAssignment = false;
    var $_needParameter = false;
    var $_needExceptionsControl = false;


    function xarTemplateTag($module, $name, $attributes = array(), $handler = NULL, $flags = XAR_TPL_TAG_ISPHPCODE)
    {
        // See defines at top of file
        if (!eregi(XAR_TPL_TAGNAME_REGEX, $name)) {
            $msg = xarML("Illegal tag definition: '#(1)' is an invalid tag name.", $name);
            xarErrorSet(XAR_SYSTEM_EXCEPTION, 'UNKNOWN', new SystemException($msg));
            return;
        }

        if (preg_match("/($module)_(\w+)api_(.*)/",$handler,$matches)) {
            $this->_type = $matches[2];
            $this->_func = $matches[3];
        } else {
            $msg = xarML("Illegal tag definition: '#(1)' is an invalid handler.", $handler);
            xarErrorSet(XAR_SYSTEM_EXCEPTION, 'UNKNOWN', new SystemException($msg));
            return;
        }

        if (!is_integer($flags)) {
            $msg = xarML("Illegal tag registration flags ('#(1)'): flags must be of integer type.", $flags);
            xarErrorSet(XAR_SYSTEM_EXCEPTION, 'UNKNOWN', new SystemException($msg));
            return;
        }
        
        // Everything seems to be in order, set the properties
        $this->_name = $name;
        $this->_handler = $handler;
        $this->_module = $module;

        if (is_array($attributes)) {
            $this->_attributes = $attributes;
        }
        $this->_setflags($flags);
    }

    function _setflags($flags)
    {
        $this->_hasChildren    = ($flags & XAR_TPL_TAG_HASCHILDREN)    == XAR_TPL_TAG_HASCHILDREN;
        $this->_hasText        = ($flags & XAR_TPL_TAG_HASTEXT)        == XAR_TPL_TAG_HASTEXT;
        $this->_isAssignable   = ($flags & XAR_TPL_TAG_ISASSIGNABLE)   == XAR_TPL_TAG_ISASSIGNABLE;
        $this->_isPHPCode      = ($flags & XAR_TPL_TAG_ISPHPCODE)      == XAR_TPL_TAG_ISPHPCODE;
        $this->_needAssignment = ($flags & XAR_TPL_TAG_NEEDASSIGNMENT) == XAR_TPL_TAG_NEEDASSIGNMENT;
        $this->_needParameter  = ($flags & XAR_TPL_TAG_NEEDPARAMETER)  == XAR_TPL_TAG_NEEDPARAMETER;
        $this->_needExceptionsControl = ($flags & XAR_TPL_TAG_NEEDEXCEPTIONSCONTROL)   == XAR_TPL_TAG_NEEDEXCEPTIONSCONTROL;
    }

    function hasChildren()
    {
        return $this->_hasChildren;
    }

    function hasText()
    {
        return $this->_hasText;
    }

    function isAssignable()
    {
        return $this->_isAssignable;
    }

    function isPHPCode()
    {
        return $this->_isPHPCode;
    }

    function needAssignement()
    {
        return $this->_needAssignment;
    }

    function needParameter()
    {
        return $this->_needParameter;
    }

    function needExceptionsControl()
    {
        return $this->_needExceptionsControl;
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

    function callHandler($args, $handler_type='render')
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
                xarErrorSet(XAR_SYSTEM_EXCEPTION, 'UNKNOWN',
                               new SystemException($msg));
                // FIXME: why is this needed?
                $this->_name = NULL;
                return;
            }
        }
        // Add the type to the args
        $args['handler_type'] = $handler_type;
        $code = xarModAPIFunc($this->_module, $this->_type, $this->_func, $args);
        assert('is_string($code); /* A custom tag should return a string with the code to put into the compiled template */');
        // Make sure the code has UNIX line endings too
        $code = str_replace(array("\r\n","\r"),"\n",$code);
        return $code;
    }
}

/**
 * Registers a tag to the theme system
 *
 * @access public
 * @param string  $tag_module  parent module of tag to register
 * @param string  $tag_name    tag to register with the system
 * @param array   $tag_attrs   array of attributes associated with tag (xarTemplateAttribute objects)
 * @param string  $tag_handler Which function is the handler?
 * @param integer $flags       Bitfield which contains the flags to turn on for the tag registration.
 * @return bool
 *
 * @todo Make this more generic, now only 'childless' tags are supported (only one handler)
 * @todo Consider using handler-array (define 'events' like in SAX)
 * @todo wrap the registration into constructor, either it succeeds creating the object or not, not having an object without succeeding sql.
 **/
function xarTplRegisterTag($tag_module, $tag_name, $tag_attrs = array(), $tag_handler = NULL, $flags = XAR_TPL_TAG_ISPHPCODE)
{
    // Check to make sure tag does not exist first
    if (xarTplGetTagObjectFromName($tag_name) != NULL) {
        // Already registered
        $msg = xarML('<xar:#(1)> tag is already defined.', $tag_name);
        xarErrorSet(XAR_SYSTEM_EXCEPTION, 'UNKNOWN',
                       new SystemException($msg));
        return false;
    }

    // Validity of tagname is checked in class.
    $tag = new xarTemplateTag($tag_module, $tag_name, $tag_attrs, $tag_handler, $flags);
    if(!$tag->getName()) return; // tagname was not set, exception pending

    $dbconn =& xarDBGetConn();
    $xartable =& xarDBGetTables();

    $systemPrefix = xarDBGetSystemTablePrefix();
    $tag_table = $systemPrefix . '_template_tags';

    // Get next ID in table
    $tag_id = $dbconn->GenId($tag_table);

    $query = "INSERT INTO $tag_table
                (xar_id, xar_name, xar_module, xar_handler, xar_data)
              VALUES
                (?,?,?,?,?)";

    $bindvars = array($tag_id,
                      $tag->getName(), 
                      $tag->getModule(),
                      $tag->getHandler(),
                      serialize($tag));

    $stmt =& $dbconn->prepareStatement($query);
    $result =& $stmt->executeUpdate($bindvars);
    if (!$result) return;

    return true;
}

/**
 * Unregisters a tag to the theme system
 *
 * @access public
 * @param  string $tag      tag to remove
 * @return bool
 * @todo   wrap in unregister method of tag class? (kinda compicates things, as now no object is needed)
 **/
function xarTplUnregisterTag($tag_name)
{
    if (!eregi(XAR_TPL_TAGNAME_REGEX, $tag_name)) {
        // throw exception
        return false;
    }

    $dbconn =& xarDBGetConn();
    $xartable =& xarDBGetTables();

    $tag_table = $xartable['template_tags'];

    $query = "DELETE FROM $tag_table WHERE xar_name = ?";

    $stmt =& $dbconn->prepareStatement($query);
    $result =& $stmt->executeUpdate(array($tag_name));
    if (!$result) return;

    return true;
}


/**
 * Check the attributes of a tag
 *
 * @access  protected
 * @param   string    $name Name of the tag
 * @param   array     $args Attribute array
 * @return  bool
 *
 * @todo Rename the function to reflect that it is a protected function
 * @todo wrap in method of tag or attribute class (or both)
*/
function xarTplCheckTagAttributes($name, $args)
{
    $tag_ref = xarTplGetTagObjectFromName($name);
    if ($tag_ref == NULL) {
        $msg = xarML('<xar:#(1)> tag is not defined.', $name);
        xarErrorSet(XAR_SYSTEM_EXCEPTION, 'UNKNOWN',
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
            xarErrorSet(XAR_SYSTEM_EXCEPTION, 'UNKNOWN',
                            new SystemException($msg));
            return false;
        } elseif ($attr->isRequired()) {
            // required attribute is missing!
            $msg = xarML("Required '#(1)' attribute is missing from <xar:#(2)> tag. See tag documentation.", $attr_name, $name);
            xarErrorSet(XAR_SYSTEM_EXCEPTION, 'UNKNOWN',
                            new SystemException($msg));
            return false;
        }
    }

    return true;
}

/**
 * Get the object belonging to the tag
 *
 * @access protected
 * @param  string $tag_name
 *
 * @return mixed  The object
 *
 */
function xarTplGetTagObjectFromName($tag_name)
{
    // cache tags for compile performance
    static $tag_objects = array();
    if (isset($tag_objects[$tag_name])) {
        return $tag_objects[$tag_name];
    }

    $dbconn =& xarDBGetConn();
    $xartable =& xarDBGetTables();

    $systemPrefix = xarDBGetSystemTablePrefix();
    $tag_table = $systemPrefix . '_template_tags';
    $query = "SELECT xar_data, xar_module FROM $tag_table WHERE xar_name=?";

    $result =& $dbconn->SelectLimit($query, 1,-1,array($tag_name),ResultSet::FETCHMODE_NUM);
    if (!$result) return;

    if ($result->EOF) {
        $result->Close();
        return NULL; // tag does not exist
    }

    list($obj,$module) = $result->getRow();
    $result->Close();

    // Module must be active for the tag to be active
    if(!xarModIsAvailable($module)) return; //throw back
    
    $obj = unserialize($obj);

    $tag_objects[$tag_name] = $obj;

    return $obj;
}

?>
