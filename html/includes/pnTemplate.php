<?php
// $Id$
// ----------------------------------------------------------------------
// PostNuke Content Management System
// Copyright (C) 2001 by the PostNuke Development Team.
// http://www.postnuke.com/
// ----------------------------------------------------------------------
// LICENSE
//
// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License (GPL)
// as published by the Free Software Foundation; either version 2
// of the License, or (at your option) any later version.
//
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// To read the license please visit http://www.gnu.org/copyleft/gpl.html
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
        die("pnTpl_init: Unexistent theme '$pnTpl_themeDir'.");
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
    return pnTpl__executeFromFile($templateCode, $tplData);
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

    $pageName = pnVarPrepForOS($pageName);
    $sourceFileName = "$pnTpl_themeDir/pages/$pageName.pnt";

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

function pnTpl__execute($templateCode, $tplData)
{
    // TODO: <marco> Here we can do it in 2 ways, by using eval or by saving $template in a tmp file
    // and including it.
    // Now for simplicity I choose the second option, but maybe it should be better to do the first.
    // Paul, can you basing on the dumpVariable function in pnLog__Logger do a function that can
    // create the PHP code necessary to recreate the $tplData array?
    $tmpFileName = tempnam('/tmp', 'bl-template-');
    $fd = fopen($tmpFileName);
    fwrite($fd, $templateCode);
    fclose($fd);

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
    ob_start();
    $res = include $tmpFileName;
    $output = ob_get_contents();
    ob_end_clean();
    unlink($tmpFileName);
    if ($res === false) {
        return; // throw back
    }
    return $output;
}

function pnTpl__executeFromFile($sourceFileName, $tplData)
{
    global $pnTpl_cacheTemplates;

    $needCompilation = true;

    if (!file_exists($sourceFileName)) {
        $msg = pnML('Could not locate template source, missing file path is: \'#(1)\'.', $sourceFileName);
        pnExceptionSet(PN_SYSTEM_EXCEPTION, 'TEMPLATE_NOT_EXIST',
                       new SystemException($msg));
        return;
    }
    if ($pnTpl_cacheTemplates) {
        $varDir = pnCoreGetVarDirPath();
        $cacheKey = md5($sourceFileName);
        $cachedFileName = $varDir . '/cache/templates/' . $cacheKey . '.php';
        if (file_exists($cachedFileName) && filemtime($sourceFileName) < filemtime($cachedFileName)) {
            $needCompilation = false;
        }
    }
    // FIXME: <marco> Paul, can we remove this now?
    if (pnVarCleanFromInput('regenerate') == 'true') {
        $needCompilation = true;
    }
    //pnLogVariable('needCompilation', $needCompilation, PNLOG_LEVEL_ERROR);
    if ($needCompilation) {
        $blCompiler = pnTpl__getCompilerInstance();
        $templateCode = $blCompiler->compileFile($sourceFileName);
        if (!isset($templateCode)) {
            return; // throw back
        }
        if ($pnTpl_cacheTemplates) {
            $fd = fopen($cachedFileName, 'w');
            fwrite($fd, $templateCode);
            fclose($fd);
        } else {
            return pnTpl__execute($templateCode, $tplData);
        }
    }

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
    ob_start();
    if (pnCoreIsDebuggerActive()) {
        $res = include $cachedFileName;
    } else {
        // Suppress error report when debugger is not active to prevent
        // that the var dir hash key could be stolen
        $res = @include $cachedFileName;
    }
    //var_dump($res);
    $output = ob_get_contents();
    ob_end_clean();
    /*if ($res === false) {
        return; // throw back
    }*/
    return $output;
}

define ('PN_TPL_OPTIONAL', 2);
define ('PN_TPL_REQUIRED', 0); // default for attributes

define ('PN_TPL_STRING', 64);
define ('PN_TPL_BOOLEAN', 128);
define ('PN_TPL_INTEGER', 256);
define ('PN_TPL_FLOAT', 512);
//define ('PN_TPL_ANY', 992); // default for attributes
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
    
    // FIXME: <marco> Also here isRequired = ~isOptional, do we need both?
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
        //$this->_attributes[0] = new pnTemplateAttribute('id', PN_TPL_STRING|PN_TPL_REQUIRED);
        
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