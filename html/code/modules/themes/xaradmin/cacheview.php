<?php
/**
 * Modify the configuration settings of this module
 *
 * @package modules\themes
 * @subpackage themes
 * @copyright see the html/credits.html file in this release
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://xaraya.info/index.php/release/70.html
 */
/**
 * @author Jo Dalle Nogare <jojodee@xaraya.com>
 *
 * @ View Cache Files
 * @param  $ 'action' action taken on cache file
 * @param $ 'confirm' confirm action on delete
 */
function themes_admin_cacheview($args)
{
    /* Get parameters from whatever input we need. */
    if (!xarVarFetch('action',  'str:1',  $action,  false, XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('confirm', 'str:1:', $confirm, '',    XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('hashn',   'str:1:', $hashn,   false, XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('templn',  'str:1:', $templn,  false, XARVAR_NOT_REQUIRED)) return;

    /* Security check - important to do this as early as possible */
    if (!xarSecurityCheck('AdminThemes')) {
        return;
    }
    xarModVars::set('themes', 'templcachepath', sys::varpath()."/cache/templates");

    $cachedir  = xarModVars::get('themes','templcachepath');
    if (!file_exists($cachedir)) {
        $cachedir = sys::varpath() . "/cache/templates";
    }
    $cachefile = xarModVars::get('themes','templcachepath').'/CACHEKEYS';
    if (!file_exists($cachefile)) {
        $cachefile = sys::varpath() . "/cache/templates/CACHEKEYS";
    }

    // CHECKME: what is this?
    $data['popup'] = false;
    
    /* Check for confirmation. */
    $data['authid'] = xarSecGenAuthKey();
    if (empty($action)) {
        /* No action set yet - display cache file list and await action */
         $data['showfiles']=false;
        /* Generate a one-time authorisation code for this operation */
        $data['items']='';
        $cachelist=array();
        $cachenames=array();

        /* put all the names of the templates and hashed cache file into an array */
        umask();
        $count=0;
        $cachekeyfile=file($cachefile);
        $fd = fopen($cachefile,'r');
        foreach($cachekeyfile as $line_num => $line) {
              $cachelist[]=array(explode(": ", $line));
            ++$count;
        }
        $data['count']=$count;
        fclose($fd);

        /* generate all the URLS for cache file list */
        foreach($cachelist as $hashname) {
            foreach ($hashname as $filen) {
               $hashn=htmlspecialchars($filen[0]);
               $templn=htmlspecialchars($filen[1]);
               $fullnurl=xarModURL('themes','admin','cacheview',
                                  array('action'=>'show','templn'=>$templn,'hashn'=>$hashn));
               $cachenames[$hashn]=array('hashn'=>$hashn,
                                   'templn'=>$templn,
                                   'fullnurl'=>$fullnurl);
            }
        }
        asort($cachenames);
        $data['items']=$cachenames;

        /* Return the template variables defined in this function */
        return $data;

    } elseif ($action=='show'){
        $data['showfiles']= true;
        $hashfile=$cachedir.'/'.$hashn.'.php';
        $newfile=array();
        $filetxt=array();
        $newfile = file($hashfile);
        $i=0;
        foreach ($newfile as $line_num => $line) {
            ++$i;
            $filetxt[]=array('lineno' =>(int)$i,
                          'linetxt'=>htmlspecialchars($line));
        }
        $data['templn']=$templn;
        $data['hashfile']=$hashfile;
        $data['items']=$filetxt;
        return $data;
    }

    xarResponse::Redirect(xarModURL('themes', 'admin', 'cacheview'));
    /*  Return */
    return true;
}
?>