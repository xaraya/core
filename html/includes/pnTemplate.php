<?php
// File: $Id$
// ----------------------------------------------------------------------
// Xaraya eXtensible Management System
// Copyright (C) 2002 by the Xaraya Development Team.
// http://www.xaraya.org
// ----------------------------------------------------------------------
// Original Author of file: Paul Rosania <paul@postnuke.com>
// Purpose of file: The BlockLayout template engine
// ----------------------------------------------------------------------

//  Find specifications at the following address
//  http://developer.hostnuke.com/modules.php?op=modload&name=Sections&file=index&req=viewarticle&artid=1&page=1

function pnTpl_init($args)
{
    global $pnTpl_cacheTemplates, $pnTpl_themeDir;

    $pnTpl_themeDir = $args['themeDirectory'];
    if (!file_exists($pnTpl_themeDir)) {
        pnCore_die("pnTpl_init: Unexistent theme directory '$pnTpl_themeDir'.");
    }
    if (!is_writeable(pnCoreGetVarDirPath().'/cache/templates')) {
        pnCore_die("pnTpl_init: Cannot write in cache/templates directory '".
                   pnCoreGetVarDirPath().'/cache/templates'.
                   "'. Control directory permissions.");
    }

    $pnTpl_cacheTemplates = $args['enableTemplatesCaching'];
}

/**
 * Turns module output into a template.
 *
 * @access public
 * @param modName the module name
 * @param modType user|admin
 * @param funcName module function to template
 * @param tplData arguments for the template
 * @param templateName the specific template to call
 * @returns string
 * @return output of the template
 **/
function pnTplModule($modName, $modType, $funcName, $tplData = array(), $templateName = NULL)
{
    global $pnTpl_themeDir;

    if (!empty($templateName)) {
        $templateName = pnVarPrepForOS($templateName);
    }

    $modBaseInfo = pnMod_getBaseInfo($modName);
    if (!isset($modBaseInfo)) return; // throw back
    $modOsDir = $modBaseInfo['osdirectory'];

    // Try theme template
    $sourceFileName = "$pnTpl_themeDir/modules/$modOsDir/$modType-$funcName" . (empty($templateName) ? '.pnt' : "-$templateName.pnt");
    if (!file_exists($sourceFileName)) {
        // Use internal template
        $sourceFileName = "modules/$modOsDir/pntemplates/$modType-$funcName" . (empty($templateName) ? '.pnd' : "-$templateName.pnd");
    }

    $tplData['_bl_module_name'] = $modName;
    $tplData['_bl_module_type'] = $modType;
    $tplData['_bl_module_func'] = $funcName;
    
    return pnTpl__executeFromFile($sourceFileName, $tplData);
}

/**
 * Turns block output into a template.
 *
 * @access public
 * @param modName the module name
 * @param blockName the block name
 * @param tplData arguments for the template
 * @param templateName the specific template to call
 * @returns string
 * @return output of the template
 **/
function pnTplBlock($modName, $blockName, $tplData = array(), $templateName = NULL)
{
    global $pnTpl_themeDir;

    if (!empty($templateName)) {
        $templateName = pnVarPrepForOS($templateName);
    }

    $modBaseInfo = pnMod_getBaseInfo($modName);
    if (!isset($modBaseInfo)) return; // throw back
    $modOsDir = $modBaseInfo['osdirectory'];

    // Try theme template
    $sourceFileName = "$pnTpl_themeDir/modules/$modOsDir/blocks/$blockName" . (empty($templateName) ? '.pnt' : "-$templateName.pnt");
    if (!file_exists($sourceFileName)) {
        // Use internal template
        $sourceFileName = "modules/$modOsDir/pntemplates/pnblocks/$blockName" . (empty($templateName) ? '.pnd' : "-$templateName.pnd");
    }

    return pnTpl__executeFromFile($sourceFileName, $tplData);
}

function pnTplString($templateCode, $tplData)
{
    return pnTpl__execute($templateCode, $tplData);
}

function pnTplFile($fileName, $tplData)
{
    return pnTpl__executeFromFile($fileName, $tplData);
}


// PROTECTED FUNCTIONS

/**
 * Rendes a page template.
 *
 * @access protected
 * @param mainModuleOutput the module output
 * @param otherModulesOutput TODO
 * @param page the theme's page to use
 * @returns string
 * @return page output
 **/
function pnTpl_renderPage($mainModuleOutput, $otherModulesOutput = NULL, $pageName = NULL)
{
    global $pnTpl_themeDir;

    if (empty($pageName)) {
        $pageName = 'default';
    }
    /* TODO => This will set all 'admin' type templates to just 
    $modType = pnVarCleanUntrusted(pnRequestGetVar('type')); 
    if (empty($modType)) {
        $modType = 'user';
    }

    if($modType == 'admin'){
        $pageName = pnVarPrepForOS($pageName);
        $sourceFileName = "admin/pages/$pageName.pnt";
    } else {*/
        $pageName = pnVarPrepForOS($pageName);
        $sourceFileName = "$pnTpl_themeDir/pages/$pageName.pnt";
    //}

    $tplData = array('_bl_mainModuleOutput' => $mainModuleOutput);

    return pnTpl__executeFromFile($sourceFileName, $tplData);
}

function pnTpl_renderBlockBox($blockInfo, $templateName = NULL)
{
    global $pnTpl_themeDir;

    if (empty($templateName)) {
        $templateName = 'default';
    }

    $templateName = pnVarPrepForOS($templateName);

    $sourceFileName = "$pnTpl_themeDir/blocks/$templateName.pnt";
    // FIXME: <marco> I'm removing the code to fall back to 'default' template since
    // I don't think it's what we need to do here.

    return pnTpl__executeFromFile($sourceFileName, $blockInfo);
}

function pnTpl_renderWidget($widgetName, $tplData)
{
    global $pnTpl_themeDir;

    $sourceFileName = "$pnTpl_themeDir/widgets/$widgetName.pnt";

    return pnTpl__executeFromFile($sourceFileName, $tplData);
}

// PRIVATE FUNCTIONS

function pnTpl__getCompilerInstance()
{
    include_once 'includes/pnBLCompiler.php';
    return new pnTpl__Compiler();
}

// Now featuring *eval()* for your anti-caching pleasure :-)
function pnTpl__execute($templateCode, $tplData)
{
    $tplData['_bl_data'] = $tplData;

    // $__tplData should be an array (-even-if- it only has one value in it), 
    // if it's not throw an exception.
    if (is_array($tplData)) {
        extract($tplData, EXTR_OVERWRITE);
    } else {  
        $msg = 'Incorrect format for tplData, it must be an associative array of arguments';
        pnExceptionSet(PN_SYSTEM_EXCEPTION, 'BAD_PARAM',
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

function pnTpl__executeFromFile($sourceFileName, $tplData)
{
    global $pnTpl_cacheTemplates;

    $needCompilation = true;

    if ($pnTpl_cacheTemplates) {
        $varDir = pnCoreGetVarDirPath();
        $cacheKey = md5($sourceFileName);
        $cachedFileName = $varDir . '/cache/templates/' . $cacheKey . '.php';
        if (file_exists($cachedFileName)
            && (!file_exists($sourceFileName) || (filemtime($sourceFileName) < filemtime($cachedFileName)))) {
            $needCompilation = false;
        }
    }
    
    if (!file_exists($sourceFileName) && $needCompilation == true) {
        $msg = pnML('Could not locate template source, missing file path is: \'#(1)\'.', $sourceFileName);
        pnExceptionSet(PN_SYSTEM_EXCEPTION, 'TEMPLATE_NOT_EXIST',
                       new SystemException($msg));
        return;
    }
    
    //pnLogVariable('needCompilation', $needCompilation, PNLOG_LEVEL_ERROR);
    if ($needCompilation) {
        $blCompiler = pnTpl__getCompilerInstance();
        $templateCode = $blCompiler->compileFile($sourceFileName);
        if (!isset($templateCode)) {
            return; // exception! throw back
        }
        if ($pnTpl_cacheTemplates) {
            $fd = fopen($cachedFileName, 'w');
            fwrite($fd, $templateCode);
            fclose($fd);
        } else {
            return pnTpl__execute($templateCode, $tplData);
        }
    }
        $tplData['_bl_data'] = $tplData;
    // $__tplData should be an array (-even-if- it only has one value in it), 
    // if it's not throw an exception.
    if (is_array($tplData)) {
        extract($tplData, EXTR_OVERWRITE);
    } else {
        $msg = 'Incorrect format for tplData, it must be an associative array of arguments';
        pnExceptionSet(PN_SYSTEM_EXCEPTION, 'BAD_PARAM',
                       new SystemException($msg));
        return;
    }
    
    // Start output buffering
    ob_start();
    
    // Load cached template file
    if (pnCoreIsDebuggerActive()) {
        $res = include $cachedFileName;
    } else {
        // Suppress error report when debugger is not active to prevent
        // that the var dir hash key could be stolen
        $res = @include $cachedFileName;
    }
    
    // Fetch output and clean buffer
    $output = ob_get_contents();
    ob_end_clean();

    // Return output
    return $output;
}

define ('PN_TPL_OPTIONAL', 2);
define ('PN_TPL_REQUIRED', 0); // default for attributes

define ('PN_TPL_STRING', 64);
define ('PN_TPL_BOOLEAN', 128);
define ('PN_TPL_INTEGER', 256);
define ('PN_TPL_FLOAT', 512);
define ('PN_TPL_ANY', PN_TPL_STRING|PN_TPL_BOOLEAN|PN_TPL_INTEGER|PN_TPL_FLOAT);

class pnTemplateAttribute {
    var $_name;     // Attribute name
    var $_flags;    // Attribute flags (datatype, required/optional, etc.)
        
    function pnTemplateAttribute($name, $flags = NULL)
    {
        if (!eregi('^[a-z][a-z0-9\-_]*$', $name)) {
            $msg = pnML("Illegal attribute name ('#(1)'): Tag name may contain letters, numbers, _ and -, and must start with a letter.", $name);
            pnExceptionSet(PN_SYSTEM_EXCEPTION, 'UNKNOWN',
                           new SystemException($msg));
            return;
        }
        
        if (!is_integer($flags) && $flags != NULL) {
            $msg = pnML("Illegal attribute flags ('#(1)'): flags must be of integer type.", $flags);
            pnExceptionSet(PN_SYSTEM_EXCEPTION, 'UNKNOWN',
                           new SystemException($msg));
            return;
        }
        
        $this->_name  = $name;
        $this->_flags = $flags;
        
        // FIXME: <marco> Why do you need both PN_TPL_REQUIRED and PN_TPL_OPTIONAL when PN_TPL_REQUIRED = ~PN_TPL_OPTIONAL?
        if ($this->_flags == NULL) {
            $this->_flags = PN_TPL_ANY|PN_TPL_REQUIRED;
        } elseif ($this->_flags == PN_TPL_OPTIONAL) {
            $this->_flags = PN_TPL_ANY|PN_TPL_OPTIONAL;
        }
    }
    
    function getFlags()
    {
        return $this->_flags;
    }
    
    function getAllowedTypes()
    {
        return ($this->getFlags() & (~ PN_TPL_OPTIONAL));
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
        if ($this->_flags & PN_TPL_OPTIONAL) {
            return true;
        }
        return false;
    }
}

class pnTemplateTag {
    var $_name;
    var $_attributes;
    var $_handler;
    var $_module;

    function pnTemplateTag($module, $name, $attributes = array(), $handler = NULL)
    {
        if (!eregi('^[a-z][-_a-z0-9]*$', $name)) {
            $msg = pnML("Illegal tag definition: '#(1)' is an invalid tag name.", $name);
            pnExceptionSet(PN_SYSTEM_EXCEPTION, 'UNKNOWN',
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
        //                you should use pnModAPIFunc!
        pnModAPILoad($this->_module);
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
 * @param tag_attrs array of attributes associated with tag (pnTemplateAttribute objects)
 * @param tag_handler function of the tag
 * @return bool 
 **/
function pnTplRegisterTag($tag_module, $tag_name, $tag_attrs = array(), $tag_handler = NULL)
{
    // Check to make sure tag does not exist first
    if (pnTplGetTagObjectFromName($tag_name) != NULL) {
        // Already registered
        $msg = pnML('<pnt:#(1)> tag is already defined.', $tag_name);
        pnExceptionSet(PN_SYSTEM_EXCEPTION, 'UNKNOWN',
                       new SystemException($msg));
        return false;
    }

    $tag = new pnTemplateTag($tag_module, $tag_name, $tag_attrs, $tag_handler);
    
    list($tag_name,
	 $tag_module,
	 $tag_func,
	 $tag_data) = pnVarPrepForStore($tag->getName(),
					$tag->getModule(),
					$tag->getHandler(),
					serialize($tag));

    list($dbconn) = pnDBGetConn();
    $pntable = pnDBGetTables();
    
    $tag_table = $pntable['template_tags'];
    
    // Get next ID in table
    $tag_id = $dbconn->GenId($tag_table);
    
    $query = "INSERT INTO $tag_table
                (pn_id,
                 pn_name,
                 pn_module,
                 pn_handler,
                 pn_data)
              VALUES
                ('$tag_id',
                 '$tag_name',
                 '$tag_module',
                 '$tag_func',
                 '$tag_data');";

    $dbconn->Execute($query);

    if ($dbconn->ErrorNo() != 0) {
        return false;
    }

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
function pnTplUnregisterTag($tag_name)
{
    if (!eregi('^[a-z][a-z\-_]*$', $tag_name)) {
        // throw exception
        return false;
    }
    
    list($dbconn) = pnDBGetConn();
    $pntable = pnDBGetTables();
    
    $tag_table = $pntable['template_tags'];
    
    $query = "DELETE FROM $tag_table WHERE pn_name = '$tag_name';";
                 
    $dbconn->Execute($query);
    
    if ($dbconn->ErrorNo() != 0) {
        return false;
    }

    return true;
}

function pnTplCheckTagAttributes($name, $args)
{
    $tag_ref = pnTplGetTagObjectFromName($name);

    if ($tag_ref == NULL) {
        $msg = pnML('<pnt:#(1)> tag is not defined.', $name);
        pnExceptionSet(PN_SYSTEM_EXCEPTION, 'UNKNOWN',
                       new SystemException($msg));
        return;
    }

    $tag_attrs = $tag_ref->getAttributes();

    foreach ($tag_attrs as $attr) {
        $attr_name = $attr->getName();
	if (isset($args[$attr_name])) {
        // check that type matches
        $attr_types = $attr->getAllowedTypes();

        if ($attr_types & PN_TPL_STRING) {
            continue;
        } elseif (($attr_types & PN_TPL_BOOLEAN)
                  && eregi ('^(true|false|1|0)$', $args[$attr_name])) {
            continue;
        } elseif (($attr_types & PN_TPL_INTEGER)
                  && eregi('^\-?[0-9]+$', $args[$attr_name])) {
            continue;
        } elseif (($attr_types & PN_TPL_FLOAT)
                  && eregi('^\-?[0-9]*.[0-9]+$', $args[$attr_name])) {
            continue;
        }

        // bad type for attribute
        $msg = pnML("'#(1)' attribute in <pnt:#(2)> tag does not have correct type. See tag documentation.", $attr_name, $name);
        pnExceptionSet(PN_SYSTEM_EXCEPTION, 'UNKNOWN',
			           new SystemException($msg));
	    return false;
	} elseif ($attr->isRequired()) {
	    // required attribute is missing!
	    $msg = pnML("Required '#(1)' attribute is missing from <pnt:#(2)> tag. See tag documentation.", $attr_name, $name);
	    pnExceptionSet(PN_SYSTEM_EXCEPTION, 'UNKNOWN',
			           new SystemException($msg));
	    return false;
	}
    }

    return true;
}

function pnTplGetTagObjectFromName($tag_name)
{
    // cache tags for compile performance
    static $tag_objects = array();
    if (isset($tag_objects[$tag_name])) {
        return $tag_objects[$tag_name];
    }

    list($dbconn) = pnDBGetConn();
    $pntable = pnDBGetTables();

    $tag_table = $pntable['template_tags'];

    $query = "SELECT pn_data FROM $tag_table WHERE pn_name='$tag_name'";
    
    $result = $dbconn->SelectLimit($query, 1);

    if ($dbconn->ErrorNo() != 0) {
        // throw exception

        return NULL;
    }

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
function pnTplPrint($template_sourcefile, $args = array())
{
    $template_file = 'cache/templates/' . md5($template_sourcefile) . '.php';
    
    if (!file_exists($template_sourcefile)) {
        $msg = pnML('Template source not found: #(1).', $template_sourcefile);
        pnExceptionSet(PN_SYSTEM_EXCEPTION, 'UNKNOWN',
                       new SystemException($msg));
        return;
    }

   if (!file_exists($template_file) ||
        filemtime($template_sourcefile) > filemtime($template_file) ||
        pnVarCleanFromInput('regenerate') == true) {

        if (!pnTplCompile($template_sourcefile)) {
            return; // Throw back
        }
    }

    extract($args);

    include $template_file;

    return true;
} 

function pnTplPrintWidget($module, $widget_sourcefile, $args = array())
{
    $widget_sourcefile = "modules/$module/pnwidgets/$widget_sourcefile";
    return pnTplPrint($widget_sourcefile, $args);
}

?>