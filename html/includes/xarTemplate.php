<?php
/**
 * File: $Id$
 *
 * BlockLayout Template Engine
 *
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2002 by the Xaraya Development Team.
 * @link http://www.xaraya.com
 *
 * @subpackage MLS
 * @link xarMLS.php
 * @author Paul Rosania, Marco Canini <m.canini@libero.it>
 */

/**
 * Initializes the BlockLayout Template Engine
 *
 * @author Paul Rosania, Marco Canini <m.canini@libero.it>
 * @return bool true
 */
function xarTpl_init($args, $whatElseIsGoingLoaded)
{
    $GLOBALS['xarTpl_themesBaseDir'] = $args['themesBaseDirectory'];
    $GLOBALS['xarTpl_defaultThemeName'] = $args['defaultThemeName'];

    if (!xarTplSetThemeName($args['defaultThemeName'])) {
        xarCore_die("xarTpl_init: Unexistent theme directory '$GLOBALS[xarTpl_themeDir]'.");
    }
    if (!xarTplSetPageTemplateName('default')) {
        xarCore_die("xarTpl_init: Unexistent default.xt page in theme directory '$GLOBALS[xarTpl_themeDir]'.");
    }
    if (!is_writeable(xarCoreGetVarDirPath().'/cache/templates')) {
        xarCore_die("xarTpl_init: Cannot write in cache/templates directory '".
                   xarCoreGetVarDirPath().'/cache/templates'.
                   "'. Control directory permissions.");
    }

    $GLOBALS['xarTpl_cacheTemplates'] = $args['enableTemplatesCaching'];

    $GLOBALS['xarTpl_additionalStyles'] = '';
    $GLOBALS['xarTpl_headJavaScript'] = '';
    $GLOBALS['xarTpl_bodyJavaScript'] = '';

    return true;
}

function xarTplGetThemeName()
{
    return $GLOBALS['xarTpl_themeName'];
}

function xarTplSetThemeName($themeName)
{
    global $xarTpl_themesBaseDir;

    if (empty($themeName) || $themeName{0} == '/') return false;

    if (!file_exists($xarTpl_themesBaseDir.'/'.$themeName)) {
        return false;
    }
    $GLOBALS['xarTpl_themeName'] = $themeName;
    $GLOBALS['xarTpl_themeDir'] = $xarTpl_themesBaseDir.'/'.$themeName;
    return true;
}

function xarTplGetPageTemplateName()
{
	return $GLOBALS['xarTpl_pageTemplateName'];
}

function xarTplSetPageTemplateName($templateName)
{
    if (empty($templateName)) return false;

    if (!file_exists("$GLOBALS[xarTpl_themeDir]/pages/$templateName.xt")) {
        return false;
    }
    $GLOBALS['xarTpl_pageTemplateName'] = $templateName;
    return true;
}

function xarTplSetPageTitle($title)
{
    $GLOBALS['xarTpl_pageTitle'] = $title;
    return true;
}

function xarTplAddStyleLink($modName, $styleName)
{
    $info = xarMod_getBaseInfo($modName);
    if (!isset($info)) return;

    $fileName = "modules/$info[directory]/styles/$styleName.css";
    if (!file_exists($fileName)) return false;

    $url = xarServerGetBaseURL().$fileName;
    $GLOBALS['xarTpl_additionalStyles'] .= "<link rel=\"stylesheet\" type=\"text/css\" href=\"{$url}\" />\n";
    return true;
}

function xarTplAddJavaScriptCode($position, $owner, $code)
{
    switch ($position) {
        case 'head':
        $GLOBALS['xarTpl_headJavaScript'] .= "\n// JavaScript code from {$owner}\n{$code}\n";
        break;
        case 'body':
        $GLOBALS['xarTpl_bodyJavaScript']  .= "\n// JavaScript code from {$owner}\n{$code}\n";
        break;
        default:
        return false;
    }
    return true;
}

/**
 * Turns module output into a template.
 *
 * @author Paul Rosania, Marco Canini <m.canini@libero.it>
 * @access public
 * @param modName the module name
 * @param modType user|admin
 * @param funcName module function to template
 * @param tplData arguments for the template
 * @param templateName the specific template to call
 * @returns string
 * @return output of the template
 **/
function xarTplModule($modName, $modType, $funcName, $tplData = array(), $templateName = NULL)
{
    if (!empty($templateName)) {
        $templateName = xarVarPrepForOS($templateName);
    }

    if (!($modBaseInfo = xarMod_getBaseInfo($modName))) return;
    $modOsDir = $modBaseInfo['osdirectory'];

    // Try theme template
    $sourceFileName = "$GLOBALS[xarTpl_themeDir]/modules/$modOsDir/$modType-$funcName" . (empty($templateName) ? '.xt' : "-$templateName.xt");
    if (!file_exists($sourceFileName)) {
        // Use internal template
        $tplName = "$modType-$funcName" . (empty($templateName) ? '' : "-$templateName");
        if (xarMLS_loadTranslations(XARMLS_DNTYPE_MODULE, $modName, XARMLS_CTXTYPE_TEMPLATE, $tplName) === NULL) return;
        $sourceFileName = "modules/$modOsDir/xartemplates/$tplName.xd";
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
 * @param modName the module name
 * @param blockName the block name
 * @param tplData arguments for the template
 * @param templateName the specific template to call
 * @returns string
 * @return output of the template
 **/
function xarTplBlock($modName, $blockName, $tplData = array(), $templateName = NULL)
{
    if (!empty($templateName)) {
        $templateName = xarVarPrepForOS($templateName);
    }

    $modBaseInfo = xarMod_getBaseInfo($modName);
    if (!isset($modBaseInfo)) return; // throw back
    $modOsDir = $modBaseInfo['osdirectory'];

    // Try theme template
    $sourceFileName = "$GLOBALS[xarTpl_themeDir]/modules/$modOsDir/blocks/$blockName" . (empty($templateName) ? '.xt' : "-$templateName.xt");
    if (!file_exists($sourceFileName)) {
        // Use internal template
        $sourceFileName = "modules/$modOsDir/xartemplates/blocks/$blockName" . (empty($templateName) ? '.xd' : "-$templateName.xd");
    }

    return xarTpl__executeFromFile($sourceFileName, $tplData);
}

function xarTplString($templateCode, $tplData)
{
    return xarTpl__execute($templateCode, $tplData);
}

function xarTplFile($fileName, $tplData)
{
    return xarTpl__executeFromFile($fileName, $tplData);
}

function xarTplCompileString($templateSource)
{
    $blCompiler = xarTpl__getCompilerInstance();
    return $blCompiler->compile($templateSource);
}


// PROTECTED FUNCTIONS

/**
 * Rendes a page template.
 *
 * @author Paul Rosania, Marco Canini <m.canini@libero.it>
 * @access protected
 * @param mainModuleOutput the module output
 * @param otherModulesOutput TODO
 * @param page the theme's page to use
 * @returns string
 * @return page output
 **/
function xarTpl_renderPage($mainModuleOutput, $otherModulesOutput = NULL, $templateName = NULL)
{
    global $xarTpl_headJavaScript, $xarTpl_bodyJavaScript;

    if (empty($templateName)) {
        $templateName = $GLOBALS['xarTpl_pageTemplateName'];
    }

    $templateName = xarVarPrepForOS($templateName);
    $sourceFileName = "$GLOBALS[xarTpl_themeDir]/pages/$templateName.xt";

    if ($xarTpl_headJavaScript != '') {
        $xarTpl_headJavaScript = "<script type=\"text/javascript\">\n<!--\n{$xarTpl_headJavaScript}\n// -->\n</script>";
    }
    if ($xarTpl_bodyJavaScript != '') {
        $xarTpl_bodyJavaScript = "<script type=\"text/javascript\">\n<!--\n{$xarTpl_bodyJavaScript}\n// -->\n</script>";
    }
    
    $tplData = array('_bl_mainModuleOutput' => $mainModuleOutput,
                     '_bl_page_title' => $GLOBALS['xarTpl_pageTitle'],
                     '_bl_additional_styles' => $GLOBALS['xarTpl_additionalStyles'],
                     '_bl_head_javascript' => $xarTpl_headJavaScript,
                     '_bl_body_javascript' => $xarTpl_bodyJavaScript);

    return xarTpl__executeFromFile($sourceFileName, $tplData);
}

function xarTpl_renderBlockBox($blockInfo, $templateName = NULL)
{
    global $xarTpl_themeDir;

    if (empty($templateName)) {
        $templateName = 'default';
    }

    $templateName = xarVarPrepForOS($templateName);

    $sourceFileName = "$xarTpl_themeDir/blocks/$templateName.xt";
    // FIXME: <marco> I'm removing the code to fall back to 'default' template since
    // I don't think it's what we need to do here.

    return xarTpl__executeFromFile($sourceFileName, $blockInfo);
}

function xarTpl_renderWidget($widgetName, $tplData)
{
    global $xarTpl_themeDir;

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
    }
    return xarTpl__executeFromFile($sourceFileName, $tplData);
}

// PRIVATE FUNCTIONS

function xarTpl__getCompilerInstance()
{
    include_once 'includes/xarBLCompiler.php';
    return new xarTpl__Compiler();
}

function xarTpl__execute($templateCode, $tplData)
{
    // $tplData should be an array (-even-if- it only has one value in it) 
    assert('is_array($tplData)');

    $tplData['_bl_data'] = $tplData;

    extract($tplData, EXTR_OVERWRITE);

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

function xarTpl__executeFromFile($sourceFileName, $tplData)
{
    global $xarTpl_cacheTemplates;

    // $tplData should be an array (-even-if- it only has one value in it) 
    assert('is_array($tplData)');

    $needCompilation = true;

    if ($xarTpl_cacheTemplates) {
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
        if ($xarTpl_cacheTemplates) {
            $fd = fopen($cachedFileName, 'w');
            fwrite($fd, $templateCode);
            fclose($fd);
            // Add an entry into CACHEKEYS
            $fd = fopen($varDir . '/cache/templates/CACHEKEYS', 'a');
            fwrite($fd, $cacheKey. ': '.$sourceFileName . "\n");
            fclose($fd);
        } else {
            return xarTpl__execute($templateCode, $tplData);
        }
    }


    $tplData['_bl_data'] = $tplData;
    extract($tplData, EXTR_OVERWRITE);

    // Start output buffering
    ob_start();

    // Load cached template file
    $res = include $cachedFileName;

    // Fetch output and clean buffer
    $output = ob_get_contents();
    ob_end_clean();

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

class xarTemplateAttribute {
    var $_name;     // Attribute name
    var $_flags;    // Attribute flags (datatype, required/optional, etc.)
        
    function xarTemplateAttribute($name, $flags = NULL)
    {
        if (!eregi('^[a-z][a-z0-9\-_]*$', $name)) {
            $msg = xarML("Illegal attribute name ('#(1)'): Tag name may contain letters, numbers, _ and -, and must start with a letter.", $name);
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

class xarTemplateTag {
    var $_name;
    var $_attributes;
    var $_handler;
    var $_module;

    function xarTemplateTag($module, $name, $attributes = array(), $handler = NULL)
    {
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
        // FIXME: <marco> how do you think to handle exceptions here?
        //                you should use xarModAPIFunc!
        xarModAPILoad($this->_module);
        $func = $this->_handler;
        return $func($args);
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
    
    $tag_table = $xartable['template_tags'];
    
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
    if (!eregi('^[a-z][a-z\-_]*$', $tag_name)) {
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

    $tag_table = $xartable['template_tags'];

    $query = "SELECT xar_data FROM $tag_table WHERE xar_name='$tag_name'";
    
    $result = $dbconn->SelectLimit($query, 1);
    if (!$result) return;

    if ($result->EOF) {
        $result->Close();
        return NULL; // tag does not exist
    }

    list($obj) = $result->fields;

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

   if (!file_exists($template_file) ||
        filemtime($template_sourcefile) > filemtime($template_file) ||
        xarVarCleanFromInput('regenerate') == true) {

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
