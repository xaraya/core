<?php
/**
 * Convert Aruba themes and modules to Jamaica 2.1.x
 *
 * This script does most of the heavy lifting involved in converting a Xaraya Aruba (1.x) 
 * theme or module for use with Xaraya Jamaica (2.1.x)
 *
 * It performs the following tasks
 * Replaces calls to deprecated/replaced functions in files
 * Replaces html entities with xml compatible unicode entities
 * Adds xml declarations to templates, and wraps them in <xar:template /> tags
 * Replaces deprecated/replaced template tags
 *
 * The script can also rename .xd templates to .xt using mtn mv or by a straight file copy
 * See the commented section on renaming in the convertFile function
 *
 * You can also optionally create a backup of files before changes are made
 * See the commented section on backups in the the convertFile function
 * 
 * NOTE: this script does not check PHP syntax
 * NOTE: this script does not check for well-formed XML
 * 
**/
/**
 * @author Chris Powis <crisp@crispcreations.co.uk>
 * @param string path to folder of the theme or module to convert (optional, default current working dir)
 * @return void
 *
 * USAGE: This script is intended to be run from the command line
 * If a path isn't specified, current working dir is the folder from which this script is called 
 * and NOT the folder where this script is located
 * Usage examples:
 * When in working dir of theme or module to convert
 * user@localhost:$ php /path/to/aruba2jamaica-2.1.php
 * From anywhere
 * user@localhost:/$ php /path/to/aruba2jamaica-2.1.php /path/to/modules/mymodule 
 *
 * This script was inspired by the web server based XarayaJamaicaUpgrade script 
 * by Ryan Walker <ryan@webcommunicate.net>
 * See: http://www.webcommunicate.net/xaraya/XarayaJamaicaUpgrade.zip
**/

// handle args
// shift out script path
$scriptpath = array_shift($argv);
$curpath = getcwd();
// first param passed to this script is an optional path to the folder we're converting
if (!empty($argv[0])) {
    $workspace = $argv[0];
}else {
    $workspace = $curpath;
}

// attempt to process files in folder
try {
    chdir($workspace);
    $files = getFiles($workspace);
    if (!empty($files)) {
        echo("Checking: {$workspace}\n");
        foreach ($files as $file) {
            if ($file == $scriptpath) continue;
            $parent = basename($workspace);
            $short = str_replace($workspace, '', $file);
            echo("    ./{$parent}{$short} : ");
            $converted = convertFile($file);
            $msg = !empty($converted) ? "Updated" : "Unchanged";
            echo("{$msg}\n");
        }
    }
    chdir($curpath);
} catch (Exception $e) { throw ($e); }


/**
 * getFiles
 * Returns files in specified folder and all subfolders
 *
 * @author Chris Powis <crisp@crispcreations.co.uk>
 * @param string $path folder to get files in
 * @return array files found
**/
function getFiles($path)
{
    static $_files = array();
    $exts = array('php','xt','xd','xml');
    // create new DirectoryIterator object and loop items
    foreach ( new DirectoryIterator($path) as $item )
    {
        if ($item->isDir() && 
            !$item->isDot() && 
            $item->current() != '_MTN' 
            && $item->isWritable()) {
            // is dir, is writable, not . or .. or _MTN, get files
            getFiles($item->getPathName());
        } elseif ($item->isFile() && 
            strpos($item->current(), '.') !== 0 &&
            $item->isWritable() &&
            in_array(pathinfo($item, PATHINFO_EXTENSION), $exts)) {
            // is file, valid extension, not beginning with . (hidden) and is writable, add file
            $_files[] = $item->getPathName();
        }
    }
    return $_files;
}

/**
 * convertFile
 * Convert a single file from 1x to 2x
 *
 * @author Chris Powis <crisp@crispcreations.co.uk>
 * @param string $path path to file to convert
 * @return bool true if converted
**/
function convertFile($path)
{
    $ext = pathinfo($path, PATHINFO_EXTENSION);
    $str = file_get_contents($path);
    if (!$str) return;    
    $compare = $str;
    
    // convert functions first (applies to all files)
    $str = xarFunctions($str);
    
    // do conversions specific to php files
    if ($ext == 'php') {
        $str = xarPHP($str);
    }
    
    // template specific conversions 
    if ($ext == 'xt' || $ext == 'xd') {
        // wrap templates in <xar:template /> tags and add xml declarations 
        if (strpos($path, '/themes/') === false && strpos($path, '/pages/') === false) {
            $str = xarTemplates($str);
        }
        // replace BL tags
        $str = xarBLTags($str);        
        // replace html entities
        $str = xmlEntities($str);
    }
    
    // nothing changed? we're done...
    if ($compare == $str) return false;
    
    /* Uncomment if you want to create backups of changed files
    // NOTE: backups will be created in the same folder as the source file
    $backup = "{$path}.bak";
    if (!copy($path, $backup)) {
        echo("Unable to create backup file {$backup}: Source unchanged\n");
        return false;
    }
    */

    // write updated file contents
    $f = fopen($path,'w');
	fwrite($f, $str);
	fclose($f);
	
	// Rename templates to xt 
    if ($ext == 'xd') {
		$toxt = str_replace('.xd','.xt', $path);
        $oldname = basename($path);
        $newname = basename($toxt);
		if (!file_exists($toxt)) {
	        /* uncomment if you just want to rename xd to xt in the filesystem 
	        // NOTE: this should be done using mtn mv (see below) when in a monotone workspace
		    rename($path, $toxt);
            */
            /* uncomment if mtn executable is available 
            // NOTE: only do this if you're updating files in a monotone workspace
            $mtnex = 'mtn'; // set an absolute path if mtn is not in your environment $PATH, eg /usr/bin/mtn
            $todir = dirname($path);
            chdir($todir);
            exec("$mtnex mv $oldname $newname");
            */
        } else {
            echo("Unable to rename $oldname -> $newname, file already exists\n");
        }
	}
    
    return true;
}

/**
 * xarFunctions
 * Replace 1x functions with 2x functions
 *
 * @author Chris Powis <crisp@crispcreations.co.uk>
 * @param string $str string to replace functions in
 * @return string string with functions replaced
 * @todo: complete function list
**/
function xarFunctions($str)
{
    $aruba = array(
        // xar* functions
        'xarCoreGetVarDirPath', 'xarConfigSetVar', 'xarConfigGetVar',
        // xarCache
        'xarCache_getStorage',
        // xarDB
        'xarDBGetHost', 'xarDBGetName', 'xarDBGetTables', 'xarDBGetConn', 'xarDBGetType', 'xarDBLoadTableMaintenanceAPI();', 'xarDBGetSiteTablePrefix',
        // xarMod
	    'xarModAPIFunc', 'xarModFunc', 'xarModGetVar', 'xarModSetVar', 'xarModDelVar', 'xarModDelAllVars', 'xarModGetUserVar', 'xarModSetUserVar', 'xarModDelUserVar', 'xarModGetVarId', 'xarModGetNameFromId', 'xarModGetName', 'xarModGetDisplayableName', 'xarModGetDisplayableDescription', 'xarModGetIdFromName', 'xarModGetInfo', 'xarModIsAvailable', 'xarModLoad', 'xarModAPILoad', 'xarModGetAlias', 'xarModSetAlias', 'xarModDelAlias',
        // xarRequest
	    'xarRequestGetVar', 'xarRequestGetInfo', 'xarRequestIsLocalReferer', 'xarResponseRedirect', 
	    // xarServer
	    'xarServerGetVar', 'xarServerGetBaseURI', 'xarServerGetHost', 'xarServerGetProtocol', 'xarServerGetBaseURL', 'xarServerGetCurrentURL', 
        // xarSession
        'xarSessionSetVar', 'xarSessionGetVar', 'xarSessionDelVar', 'xarSessionGetId',
        // xarTemplate
	    'xarTplPagerInfo', 'xarTplGetPager',    
    );
    $jamaica = array(
        // xar* functions
        'sys::varpath', 'xarConfigVars::set', 'xarConfigVars::get',
        // xarCache
        'xarCache::getStorage',
        // xarDB
        'xarDB::getHost', 'xarDB::getName', 'xarDB::getTables', 'xarDB::getConn', 'xarDB::getType', 'sys::import(\'xaraya.tableddl\');', 'xarDB::getPrefix',  
        // xarMod
        'xarMod::apiFunc', 'xarMod::guiFunc', 'xarModVars::get', 'xarModVars::set', 'xarModVars::delete', 'xarModVars::delete_all', 'xarModUserVars::get', 'xarModUserVars::set', 'xarModUserVars::delete', 'xarModVars::getID', 'xarMod::getName', 'xarMod::getName', 'xarMod::getDisplayName', 'xarMod::getDisplayDescription', 'xarMod::getRegID', 'xarMod::getInfo', 'xarMod::isAvailable', 'xarMod::load', 'xarMod::apiLoad', 'xarModAlias::resolve', 'xarModAlias::set', 'xarModAlias::delete', 
        // xarRequest
        'xarRequest::getVar', 'xarRequest::getInfo', 'xarRequest::isLocalReferer', 'xarResponse::redirect',
        // xarServer
        'xarServer::getVar', 'xarServer::getBaseURI', 'xarServer::getHost', 'xarServer::getProtocol', 'xarServer::getBaseURL', 'xarServer::getCurrentURL',
        // xarSession
        'xarSession::setVar', 'xarSession::getVar', 'xarSession::delVar', 'xarSession::getId',
        // xarTemplate
        'xarTplPager::getInfo', 'xarTplPager::getPager',
    );
    // replace functions
    $str = str_replace($aruba, $jamaica, $str);
    $str = str_ireplace($aruba, $jamaica, $str);
    
    return $str;
}

/**
 * xarPHP
 * Replacements specific to php files
 *
 * @author Chris Powis <crisp@crispcreations.co.uk>
 * @param string $str string to check for functions
 * @return string string with functions replaced
**/
function xarPHP($str)
{
    // Check if php file and xarTplPager is in use
    if (stripos($str, 'xarTplPager') !== false && strpos($str, '<?php') !== false) {
        // Check if pager class is already included 
        $pgr_re = '!sys::import\(\s*["|\']+modules\.base\.class\.pager["|\']+\s*\);!';
        if (!preg_match($pgr_re, $str)) {
            // add pager class at beginning, right after php start tag
            $pager = "\nsys::import('modules.base.class.pager');\n";
            $str = str_replace ('<?php', "<?php{$pager}", $str);
        }
    }  
    
    // replace require|include(_once) "modules/*" with sys::import("modules.*") 
    $inc_re = '!^(\s*(?:include|require)+(?:_once)?[^\'|"]*)+((?:\'|")+modules/[^\'|"]*(?:\'|")+)+([^;]*;)+!m';
    if (preg_match_all($inc_re, $str, $matches)) {
        foreach ($matches[2] as $k => $match) {
            $match = str_replace('.php', '', $match);
            $match = str_replace('/', '.', $match);
            $match = "sys::import({$match});";
            $str = str_replace($matches[0][$k], $match, $str);
        }
    }
    
    return $str;
}

/**
 * xarTemplates
 * Convert templates to BL2
 *
 * @author Chris Powis <crisp@crispcreations.co.uk>
 * @param string $str string to check for declarations
 * @return string string with declarations in place
**/
function xarTemplates($str)
{
    $xml_re = '!<\?xml[^>]*>!';
    $tpl_re = '!<xar:template\s+xmlns[^>]*>!';
    // check for xml declaration...
    if (preg_match($xml_re, $str)) {
        // remove it...
        $str = preg_replace($xml_re, '', $str);
    }
    // check for xar:template tag...
    if (preg_match($tpl_re, $str)) {
        // remove it...
        $str = preg_replace($tpl_re, '', $str);
    }
    // place xml and template declarations at beginning of file...
    $str = "<?xml version=\"1.0\" encoding=\"utf-8\"?>\n"
         . "<xar:template xmlns:xar=\"http://xaraya.com/2004/blocklayout\">\n" 
         . $str;
    // add closing template tag
    if (strpos($str, '</xar:template>') === false) {
        $str .= "\n</xar:template>";
    }
    return $str;
}    

/**
 * xarBLTags
 * Convert template tags to BL2
 *
 * @author Chris Powis <crisp@crispcreations.co.uk>
 * @param string $str string to replace BL markup in
 * @return string string with BL markup replaced
 * @todo Replace only <xar:mlstring>'s not in <xar:ml> constructs (whole template is ignored for now)
 * @todo Replace &xar-modurl...; entities
 * @todo wrap <script> tags in CDATA declarations
**/
function xarBLTags($str)
{
    // replace tags
    $aruba = array('<xar:set name="$', '<xar:base-include-javascript', '<xar:base-render-javascript', '<xar:additional-styles', ' && ');
    $jamaica = array('<xar:set name="', '<xar:javascript', '<xar:place-javascript', '<xar:place-css', ' and ');
    // Only remove <xar:mlstring> tags in templates with no <xar:ml>...</xar:ml> constructs  
    if (strpos($str, '<xar:ml>') === false) {
        $aruba += array('<xar:mlstring>', '</xar:mlstring>');
        $jamaica += array('', '');
    } 
    $str = str_replace($aruba, $jamaica, $str);
    return $str;
}

/**
 * xmlEntities 
 * Replace (x)html entities with xml compatible unicode entities
 *
 * @param string $str string to replace entities in
 * @return string string with entities replaced
 * Function courtesy of http://www.sourcerally.net/Scripts/39-Convert-HTML-Entities-to-XML-Entities
**/
function xmlEntities($str)
{
	$html = array('&quot;', '&amp;', '&lt;', '&gt;', '&nbsp;', '&iexcl;', '&cent;', '&pound;', '&curren;', '&yen;', '&brvbar;', '&sect;', '&uml;', '&copy;', '&ordf;', '&laquo;', '&not;', '&shy;', '&reg;', '&macr;', '&deg;', '&plusmn;', '&sup2;', '&sup3;', '&acute;', '&micro;', '&para;', '&middot;', '&cedil;', '&sup1;', '&ordm;', '&raquo;', '&frac14;', '&frac12;', '&frac34;', '&iquest;', '&Agrave;', '&Aacute;', '&Acirc;', '&Atilde;', '&Auml;', '&Aring;', '&AElig;', '&Ccedil;', '&Egrave;', '&Eacute;', '&Ecirc;', '&Euml;', '&Igrave;', '&Iacute;', '&Icirc;', '&Iuml;', '&ETH;', '&Ntilde;', '&Ograve;', '&Oacute;', '&Ocirc;', '&Otilde;', '&Ouml;', '&times;', '&Oslash;', '&Ugrave;', '&Uacute;', '&Ucirc;', '&Uuml;', '&Yacute;', '&THORN;', '&szlig;', '&agrave;', '&aacute;', '&acirc;', '&atilde;', '&auml;', '&aring;', '&aelig;', '&ccedil;', '&egrave;', '&eacute;', '&ecirc;', '&euml;', '&igrave;', '&iacute;', '&icirc;', '&iuml;', '&eth;', '&ntilde;', '&ograve;', '&oacute;', '&ocirc;', '&otilde;', '&ouml;', '&divide;', '&oslash;', '&ugrave;', '&uacute;', '&ucirc;', '&uuml;', '&yacute;', '&thorn;', '&yuml;');
	$xml = array('&#34;', '&#38;', '&#60;', '&#62;', '&#160;', '&#161;', '&#162;', '&#163;', '&#164;', '&#165;', '&#166;', '&#167;', '&#168;', '&#169;', '&#170;', '&#171;', '&#172;', '&#173;', '&#174;', '&#175;', '&#176;', '&#177;', '&#178;', '&#179;', '&#180;', '&#181;', '&#182;', '&#183;', '&#184;', '&#185;', '&#186;', '&#187;', '&#188;', '&#189;', '&#190;', '&#191;', '&#192;', '&#193;', '&#194;', '&#195;', '&#196;', '&#197;', '&#198;', '&#199;', '&#200;', '&#201;', '&#202;', '&#203;', '&#204;', '&#205;', '&#206;', '&#207;', '&#208;', '&#209;', '&#210;', '&#211;', '&#212;', '&#213;', '&#214;', '&#215;', '&#216;', '&#217;', '&#218;', '&#219;', '&#220;', '&#221;', '&#222;', '&#223;', '&#224;', '&#225;', '&#226;', '&#227;', '&#228;', '&#229;', '&#230;', '&#231;', '&#232;', '&#233;', '&#234;', '&#235;', '&#236;', '&#237;', '&#238;', '&#239;', '&#240;', '&#241;', '&#242;', '&#243;', '&#244;', '&#245;', '&#246;', '&#247;', '&#248;', '&#249;', '&#250;', '&#251;', '&#252;', '&#253;', '&#254;', '&#255;');

	$str = str_replace($html,$xml,$str);
	$str = str_ireplace($html,$xml,$str);
	return $str;
}

?>
