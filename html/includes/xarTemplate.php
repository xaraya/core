<?php
/**
 * File: $Id: s.xarTemplate.php 1.119 03/06/30 16:10:26+01:00 miko@miko.homelinux.org $
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

/*

NOTE: <Dracos>  All the widget stuff in here is essentially dead code,
        but *DO NOT* remove it.  I still need to figure it out so I can
        make proper widgets out of it (for r1.1)

        Thanks
*/


/**
 * Initializes the BlockLayout Template Engine
 *
 * @author Paul Rosania, Marco Canini <m.canini@libero.it>
 * @access protected
 * @global xarTpl_cacheTemplates bool
 * @global xarTpl_themesBaseDir string
 * @global xarTpl_defaultThemeName string
 * @global xarTpl_additionalStyles string
 * @global xarTpl_headJavaScript string
 * @global xarTpl_bodyJavaScript string 
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
        xarCore_die("xarTpl_init: Nonexistent theme directory '$GLOBALS[xarTpl_themeDir]'.");
    }
    if (!xarTplSetPageTemplateName('default')) {
        xarCore_die("xarTpl_init: Nonexistent default.xt page in theme directory '$GLOBALS[xarTpl_themeDir]'.");
    }
    
    if ($GLOBALS['xarTpl_cacheTemplates']) {
        if (!is_writeable(xarCoreGetVarDirPath().'/cache/templates')) {
            xarCore_die("xarTpl_init: Cannot write in cache/templates directory '".
                       xarCoreGetVarDirPath().'/cache/templates'.
                       "'. Change directory permissions.");
        }
    }

    $GLOBALS['xarTpl_additionalStyles'] = '';
    
    // Bug 1109: xarTpl_JavaScript deprecates xarTpl_{head|body}JavaScript
    $GLOBALS['xarTpl_JavaScript'] = array('head'=>array(), 'body'=>array());
    $GLOBALS['xarTpl_headJavaScript'] = '';
    $GLOBALS['xarTpl_bodyJavaScript'] = '';
   
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
 * @global xarTpl_themeDir string
 * @param themeName string
 * @return bool
 */
function xarTplSetThemeName($themeName)
{
    assert('$themeName != "" && $themeName{0} != "/"');
    if (!file_exists($GLOBALS['xarTpl_themesBaseDir'].'/'.$themeName)) {
        return false;
    }
    $GLOBALS['xarTpl_themeName'] = $themeName;
    $GLOBALS['xarTpl_themeDir'] = $GLOBALS['xarTpl_themesBaseDir'].'/'.$GLOBALS['xarTpl_themeName'];
    return true;
}

/**
 * Set theme dir
 * 
 * @access public
 * @global xarTpl_themesBaseDir string
 * @global xarTpl_themeName string
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
    $GLOBALS['xarTpl_themeDir'] = $GLOBALS['xarTpl_themesBaseDir'].'/'.$themeDir;
    return true;
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
 * @global xarTpl_themeDir string
 * @param templateName string
 * @return bool
 */
function xarTplSetPageTemplateName($templateName)
{
    assert('$templateName != ""');
    if (!file_exists($GLOBALS['xarTpl_themeDir']."/pages/$templateName.xt")) {
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
        }
    }
    return true;
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
 * Add JavaScript code to template output
 * 
 * @access public
 * @global xarTpl_headJavaScript string
 * @global xarTpl_bodyJavaScript string
 * @param position string
 * @param owner string
 * @param code string
 * @deprec ?
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
 * @param position string
 * @param type string
 * @param data string
 * @param index string optional
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

    // Legacy support allows unconverted themes to work as before - remove this
    // with xarTplAddJavaScriptCode().
    if ($position == 'head' && $type == 'code') {
        $GLOBALS['xarTpl_headJavaScript'] .= $data . "\n";
    }

    if ($position == 'body' && $type == 'code') {
        $GLOBALS['xarTpl_bodyJavaScript'] .= $data . "\n";
    }

    return true;
}

/**
 * Get JavaScript code or links queued for template output
 * 
 * @access public
 * @global xarTpl_JavaScript array
 * @param position string optional
 * @param index string optional
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
 * @author Paul Rosania, Marco Canini <m.canini@libero.it>
 * @access public
 * @global xarTpl_themeDir string
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
    $sourceFileName = $GLOBALS['xarTpl_themeDir']."/modules/$modOsDir/$modType-$funcName" . (empty($templateName) ? '.xt' : "-$templateName.xt");
    if (!file_exists($sourceFileName)) {
        // Use internal template
        $tplName = "$modType-$funcName" . (empty($templateName) ? '' : "-$templateName");
        $sourceFileName = "modules/$modOsDir/xartemplates/$tplName.xd";
        // fall back to default template if necessary
        if (!empty($templateName) && !file_exists($sourceFileName)) {
            $tplName = "$modType-$funcName";
			
			//check if theme overides default template		
		    $sourceFileName = $GLOBALS['xarTpl_themeDir']."/modules/$modOsDir/$modType-$funcName" .'.xt';
			if(!file_exists($sourceFileName))
			{
	            $sourceFileName = "modules/$modOsDir/xartemplates/$modType-$funcName.xd";
			}
        }
        if (xarMLS_loadTranslations(XARMLS_DNTYPE_MODULE, $modName, XARMLS_CTXTYPE_TEMPLATE, $tplName) === NULL) return;
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
 * @author Paul Rosania, Marco Canini <m.canini@libero.it>
 * @access public
 * @global xarTpl_themeDir string
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
    $sourceFileName = $GLOBALS['xarTpl_themeDir']."/modules/$modOsDir/blocks/$blockName" . (empty($templateName) ? '.xt' : "-$templateName.xt");
    if (!file_exists($sourceFileName)) {
        // Use internal template
        $sourceFileName = "modules/$modOsDir/xartemplates/blocks/$blockName" . (empty($templateName) ? '.xd' : "-$templateName.xd");
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
 * Equivalent of pnHTML()'s Pager function (to get rid of pnHTML calls in modules while waiting for widgets)
 *
 * @author Greg 'Adam Baum'
 * @since 1.13 - 2002/01/23
 * @access public
 * @param integer $startnum start iteam
 * @param integer $total total number of items present
 * @param string $urltemplate template for url, will replace '%%' with item number
 * @param integer $perpage number of links to display (default=10)
 */
function xarTplGetPager($startnum, $total, $urltemplate, $perpage = 10)
{
    // Sanity check on perpage to prevent infinite loops
    if($perpage < 1) {
        $perpage = 10;
    }
    if($startnum < 1) {
        $startnum = 1;
    }

    // Quick check to ensure that we have work to do
    if ($total <= $perpage) {
        return '';
    }

    // Fix for the RSS theme.  We don't want to throw pager information
    // with the syndication.
    $themeName = xarVarGetCached('Themes.name','CurrentTheme');
    if ($themeName == 'rss') {
        return '';
    }

    // TODO - various fixes required
    // Make << and >> do paging properly
    // Display subset of pages if large number

    $data = array();

    // Show startnum link
    if ($startnum != 1) {
        $url = preg_replace('/%%/', 1, $urltemplate);
        xarVarSetCached('Pager.first','leftarrow',$url);
        $data['beginurl'] = $url;
    } else {
        $data['beginurl'] = '';
    }

    // Show following items
    $data['middleurls'] = array();
    $pagenum = 1;

    for ($curnum = 1; $curnum <= $total; $curnum += $perpage)
    {
        if (($startnum < $curnum) || ($startnum > ($curnum + $perpage - 1)))
        {
            // Not on this page - show link
            $url = preg_replace('/%%/', $curnum, $urltemplate);
            $data['middleurls'][$pagenum] = $url;
        } else {
            // On this page - show text
            $data['middleurls'][$pagenum] = '';
        }
        $pagenum++;
    }

    if (($curnum >= $perpage+1) && ($startnum < $curnum-$perpage)) {
        $url = preg_replace('/%%/', $curnum-$perpage, $urltemplate);
        xarVarSetCached('Pager.last','rightarrow',$url);
        $data['endurl'] = $url;
    } else {
        $data['endurl'] = '';
    }

    return xarTplModule('base','user', 'pager', $data);
}

/**
 * TODO: add this description
 *
 * @access public
 * @param templateCode string
 * @param tplData string
 * @return  string
 */
function xarTplString($templateCode, $tplData)
{
    return xarTpl__execute($templateCode, $tplData);
}

/**
 * TODO: add this description
 *
 * @access public
 * @param templateCode string
 * @param tplData string
 * @return string
 */
function xarTplFile($fileName, $tplData)
{
    return xarTpl__executeFromFile($fileName, $tplData);
}

/**
 * TODO: add this description
 *
 * @access public
 * @param templateSource string
 * @return string
 */
function xarTplCompileString($templateSource)
{
    $blCompiler = xarTpl__getCompilerInstance();
    return $blCompiler->compile($templateSource);
}

/**
 * Renders a page template.
 *
 * @author Paul Rosania, Marco Canini <m.canini@libero.it>
 * @access protected
 * @global xarTpl_themeDir string
 * @global xarTpl_pageTemplateName string
 * @global xarTpl_pageTitle string
 * @global xarTpl_additionalStyles string
 * @global xarTpl_headJavaScript string
 * @global xarTpl_bodyJavaScript string
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
        $templateName = $GLOBALS['xarTpl_pageTemplateName'];
    }

    $templateName = xarVarPrepForOS($templateName);
    $sourceFileName = $GLOBALS['xarTpl_themeDir']."/pages/$templateName.xt";

    if ($GLOBALS['xarTpl_headJavaScript'] !='') {
        $GLOBALS['xarTpl_headJavaScript'] = "\n<script type=\"text/javascript\">\n{$GLOBALS['xarTpl_headJavaScript']}\n</script>";
    }
    if ($GLOBALS['xarTpl_bodyJavaScript'] !='') {
        $GLOBALS['xarTpl_bodyJavaScript'] = "\n<script type=\"text/javascript\">\n{$GLOBALS['xarTpl_bodyJavaScript']}\n</script>";
    }

    $tplData = array(
        '_bl_mainModuleOutput'     => $mainModuleOutput,
        '_bl_page_title'           => $GLOBALS['xarTpl_pageTitle'],
        '_bl_additional_styles'    => $GLOBALS['xarTpl_additionalStyles'],
        // Bug 1109: _bl_javascript replaces _bl_{head|body}_javascript eventually.
        '_bl_javascript'           => $GLOBALS['xarTpl_JavaScript'],
        '_bl_head_javascript'      => $GLOBALS['xarTpl_headJavaScript'],
        '_bl_body_javascript'      => $GLOBALS['xarTpl_bodyJavaScript']
    );

    return xarTpl__executeFromFile($sourceFileName, $tplData);
}

/**
 * Render a block box
 * 
 * @access protected
 * @global xarTpl_themeDir string
 * @param blockInfo string
 * @param templateName string
 * @return bool xarTpl__executeFromFile($sourceFileName, $blockInfo)
 */
function xarTpl_renderBlockBox($blockInfo, $templateName = NULL)
{
    if (empty($templateName)) {
        $templateName = 'default';
    }

    $templateName = xarVarPrepForOS($templateName);

    $sourceFileName = $GLOBALS['xarTpl_themeDir']."/blocks/$templateName.xt";
    // FIXME: <marco> I'm removing the code to fall back to 'default' template since
    // I don't think it's what we need to do here.

    return xarTpl__executeFromFile($sourceFileName, $blockInfo);
}

/**
 * Render a widget
 * 
 * @access protected
 * @global xarTpl_themeDir string
 * @param widgetName string
 * @param tplData string
 * @return xarTpl__executeFromFile($sourceFileName, $tplData)
 */
function xarTpl_renderWidget($widgetName, $tplData)
{
    $sourceFileName = $GLOBALS['xarTpl_themeDir']."/widgets/$widgetName.xd";

    $sourceFileName = "$xarTpl_themeDir/widgets/$widgetName.xd";

    return xarTpl__executeFromFile($sourceFileName, $tplData);
}

function xarTpl_includeThemeTemplate($templateName, $tplData)
{
    $templateName = xarVarPrepForOS($templateName);
    $sourceFileName = "$GLOBALS[xarTpl_themeDir]/includes/$templateName.xt";
    return xarTpl__executeFromFile($sourceFileName, $tplData);
}

function xarTpl_includeModuleTemplate($modName, $templateName, $tplData)
{
    $templateName = xarVarPrepForOS($templateName);
    $sourceFileName = "$GLOBALS[xarTpl_themeDir]/modules/$modName/includes/$templateName.xt";
    if (!file_exists($sourceFileName)) {
        $sourceFileName = "modules/$modName/xartemplates/includes/$templateName.xd";
        if (xarMLS_loadTranslations(XARMLS_DNTYPE_MODULE, $modName, XARMLS_CTXTYPE_INCLTEMPL, $templateName) === NULL) return;
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
 * @return string output
 */
function xarTpl__execute($templateCode, $tplData)
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
    eval("?>".$templateCode);
    
    // Grab output and clean buffer
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
        $templateCode = $blCompiler->compileFile($sourceFileName);
        if (!isset($templateCode)) {
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
        } else {
            return xarTpl__execute($templateCode, $tplData);
        }
    }


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

    // Start output buffering
    ob_start();

// TODO: check for <?xml> header stuff
    // optionally show template filenames
    if ($GLOBALS['xarTpl_showTemplateFilenames']) {
        echo "<!-- start $sourceFileName -->";
    }

    // Load cached template file
    $res = include $cachedFileName;

    // optionally show template filenames
    if ($GLOBALS['xarTpl_showTemplateFilenames']) {
        echo "<!-- end $sourceFileName -->";
    }

    // Fetch output and clean buffer
    $output = ob_get_contents();
    ob_end_clean();

    // Return output
    return $output;
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
        if (!isset($templateCode)) {
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
        }
        return $templateCode;
    }

    // Load cached template file
    $output = implode('', file($cachedFileName));

    // Return output
    return $output;
}

/// OLD STUFF //////////////////////////////////

define ('XAR_TPL_OPTIONAL', 2);
define ('XAR_TPL_REQUIRED', 0); // default for attributes

define ('XAR_TPL_STRING', 64);
define ('XAR_TPL_BOOLEAN', 128);
define ('XAR_TPL_INTEGER', 256);
define ('XAR_TPL_FLOAT', 512);
define ('XAR_TPL_ANY', XAR_TPL_STRING|XAR_TPL_BOOLEAN|XAR_TPL_INTEGER|XAR_TPL_FLOAT);

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
        // FIXME: It seems that the expr ^[a-z][a-z0-9\-_]*$ doesn *NOT* match the string 'bid'
        // and the expr ^[a-z][-_a-z0-9]*$ *DOES* 
        // this was on the server on xaraya
        // FIXME: Move this expression out of the class and define() it.
        if (!eregi('^[a-z][-_a-z0-9]*$', $name)) {
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
        // FIXME: See note at attribute class
        if (!eregi('^[a-z][-_a-z0-9]*$', $name)) {
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
        //xarModAPILoad($this->_module, $this->_type);
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

    $tag = new xarTemplateTag($tag_module, $tag_name, $tag_attrs, $tag_handler);
    
    list($tag_name,
         $tag_module,
         $tag_func,
         $tag_data) = xarVarPrepForStore($tag->getName(),
                                         $tag->getModule(),
                                         $tag->getHandler(),
                                         serialize($tag));

    list($dbconn) = xarDBGetConn();
    $xartable = xarDBGetTables();
    
    // FIXME: temp fix, installer doesn't know about it
    //$tag_table = $xartable['template_tags'];
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
    if (!eregi('^[a-z][-_a-z0-9]*$', $tag_name)) {
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
    
    // FIXME: during installer the template_tag table wasn't there, didn't investigate
    //$tag_table = $xartable['template_tags'];
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
