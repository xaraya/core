<?php
// File: $Id$
// ----------------------------------------------------------------------
// Xaraya eXtensible Management System
// Copyright (C) 2002 by the Xaraya Development Team.
// http://www.xaraya.org
// ----------------------------------------------------------------------
// Original Author of file: mikespub
// Purpose of file: "wrapper" file to use Block Layout templates in PN .7x
// ----------------------------------------------------------------------

/*
   Introduction
   ============
   This is a quick & dirty wrapper that allows you to template your module
   functions and blocks in PN .7x, using Xaraya's Block Layout templating.
   For more information and examples, see http://www.xaraya.com/downloads/

   Current status (02/11/2002)
   ===========================
   It works for me on my PN .714 site, and that's about all I have to say :-)
   See http://mikespub.net/copdab/modules.php?op=modload&name=BL&file=index

   Since the final specs for BL 1.0 aren't out yet, you should be prepared
   for changes to the current syntax, and use this mainly for test purposes.

   Installation notes
   ==================
   1) copy this file and pnBLCompiler.php to your html/includes/ directory
   2) create a directory html/cache/templates/ and chmod it to 777 (or
      770 is that's enough) so that the webserver user can write "compiled"
      templates to it
   3) in order to use Block Layout templates for your module functions,
      create a subdirectory pntemplates/ under your module directory to put
      your templates. For blocks, create a subdirectory pntemplates/pnblocks/

   Converting a module function 'myfunc' to use BL templates
   =========================================================
   1) create a template called 'user-myfunc.pnd' in your pntemplates directory
      with the HTML, BL tags and entities you'll want to use to generate your
      HTML output [for admin functions, replace 'user' with 'admin' :)]
   2) at the start of your module file, add the following line :
          include_once 'includes/pnTemplate7x.php';
   3) inside your function, instead of using pnHTML(), fill in a $data array
      with all the variables you'll be using in your template
   4) at the end of your function code, use :
          return pnTplModule('mymodule', 'user', 'myfunc', $data);
      instead of 
          return $output->GetOutput();

   Converting a block 'myblock' to use BL templates
   ================================================
   1) create a template called 'myblock.pnd' in your pntemplates/pnblocks
      directory with the HTML, BL tags and entities you'll want to use to
      generate your HTML output
   2) at the start of your block file, add the following line :
          include_once 'includes/pnTemplate7x.php';
   3) inside your display function, instead of using pnHTML(), fill in a $data
      array with all the variables you'll be using in your template
   4) at the end of your block display code, use :
          $blockinfo['content'] = pnTplBlock('mymodule', 'myblock', $data);
      instead of 
          $blockinfo['content'] = $output->GetOutput();

   Limitations compared to full Block Layout templating
   ====================================================
   1) this wrapper file can only be used to template module functions and
      blocks, *not* to create complete 'page templates' for your site (at
      least not without installing additional files on your PN .7x site).
   2) BL tags and entities are limited to those that rely on standard HTML,
      PHP and functions available in the PN .7x API for their implementation.
      This should include most commonly used tags and entities that you'll
      want to use for your module functions and blocks.
      It will *not* support multi-language tags, events, widgets or 'page'-
      related tags for the placement of blocks and modules in page templates.
   3) theme-dependent templates (in themes/<mytheme>/modules/<mymodule>) that
      override your default templates are currently commented out in the code
      below - feel free to add the right theme detection code for PN .7x and
      enable this again :-)

   Alternative solutions for templating
   ====================================
   Smarty (*), ModeliXe, PHPLib, FastTemplate, VTemplate, phpBB templates, ...
   (*) currently used in Envolution

*/

// ----------------------------------------------------------------------
// Wrapper for pnCore.php
// ----------------------------------------------------------------------

function pnCoreGetVarDirPath()
{
   return '.';
}

function pnCore_getSiteVar($name)
{
    return '';
}

// ----------------------------------------------------------------------
// Wrapper for pnMLS.php
// ----------------------------------------------------------------------

function pnML($string)
{
    return $string;
}

// ----------------------------------------------------------------------
// Wrapper for pnException.php
// ----------------------------------------------------------------------

define('PN_NO_EXCEPTION', 0);
define('PN_USER_EXCEPTION', 1);
define('PN_SYSTEM_EXCEPTION', 2);

class DefaultUserException
{
    var $msg;

    function DefaultUserException($msg)
    {
        $this->msg = $msg;
    }

    function toString()
    {
        return $this->msg;
    }

    function toHTML()
    {
        return nl2br(pnVarPrepForDisplay($this->msg)) . '<br/>';
    }

}

function pnExceptionSet($major, $exceptionId, $value = NULL)
{
    echo $exceptionId . ' : <p />' . $value->toHTML();
}

// ----------------------------------------------------------------------
// Wrapper for pnTemplate.php
// ----------------------------------------------------------------------

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
//    global $pnTpl_themeDir;

    if (!empty($templateName)) {
        $templateName = pnVarPrepForOS($templateName);
    }

//    $modBaseInfo = pnMod_getBaseInfo($modName);
//    if (!isset($modBaseInfo)) return; // throw back
//    $modOsDir = $modBaseInfo['osdirectory'];
    $modOsDir = pnVarPrepForOS($modName);

    // Try theme template
//    $sourceFileName = "$pnTpl_themeDir/modules/$modOsDir/$modType-$funcName" . (empty($templateName) ? '.pnt' : "-$templateName.pnt");
//    if (!file_exists($sourceFileName)) {
        // Use internal template
        $sourceFileName = "modules/$modOsDir/pntemplates/$modType-$funcName" . (empty($templateName) ? '.pnd' : "-$templateName.pnd");
//    }

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
//    global $pnTpl_themeDir;

    if (!empty($templateName)) {
        $templateName = pnVarPrepForOS($templateName);
    }

//    $modBaseInfo = pnMod_getBaseInfo($modName);
//    if (!isset($modBaseInfo)) return; // throw back
//    $modOsDir = $modBaseInfo['osdirectory'];
    $modOsDir = pnVarPrepForOS($modName);

    // Try theme template
//    $sourceFileName = "$pnTpl_themeDir/modules/$modOsDir/blocks/$blockName" . (empty($templateName) ? '.pnt' : "-$templateName.pnt");
//    if (!file_exists($sourceFileName)) {
        // Use internal template
        $sourceFileName = "modules/$modOsDir/pntemplates/pnblocks/$blockName" . (empty($templateName) ? '.pnd' : "-$templateName.pnd");
//    }

    return pnTpl__executeFromFile($sourceFileName, $tplData);
}

// PRIVATE FUNCTIONS

function pnTpl__getCompilerInstance()
{
    include_once 'includes/pnBLCompiler.php';
    return new pnTpl__Compiler();
}

function pnTpl__executeFromFile($sourceFileName, $tplData)
{
    $needCompilation = true;

    $varDir = pnCoreGetVarDirPath();
        
    $cacheKey = md5($sourceFileName);
    $cachedFileName = $varDir . '/cache/templates/' . $cacheKey . '.php';
    if (file_exists($cachedFileName)
        && (!file_exists($sourceFileName) || (filemtime($sourceFileName) < filemtime($cachedFileName)))) {
        $needCompilation = false;
    }
    
    if (!file_exists($sourceFileName) && $needCompilation == true) {
        die('Could not locate template source, missing file path is: ' . $sourceFileName);
    }

    if ($needCompilation) {
        $blCompiler = pnTpl__getCompilerInstance();
        $templateCode = $blCompiler->compileFile($sourceFileName);
        if (!isset($templateCode)) {
            return; // exception! throw back
        }
        $fd = fopen($cachedFileName, 'w');
        fwrite($fd, $templateCode);
        fclose($fd);
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
    // Suppress error report when debugger is not active to prevent
    // that the var dir hash key could be stolen
    $res = @include $cachedFileName;
    
    // Fetch output and clean buffer
    $output = ob_get_contents();
    ob_end_clean();

    // Return output
    return $output;
}

?>
