<?php 
/**
 * File: $Id$
 *
 * Displays a HTML editible Block
 *
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2002 by the Xaraya Development Team.
 * @link http://www.xaraya.com
 * 
 * @subpackage Base Module
 * @author Patrick Kellum
*/

/**
 * Block init - holds security.
 */
function themes_metablock_init()
{
    // Security
    xarSecAddSchema('themes:metablock', 'Block title::');
}

/**
 * block information array
 */
function themes_metablock_info()
{
    return array('text_type' => 'Meta',
		 'text_type_long' => 'Meta',
		 'module' => 'themes',
         'func_update' => 'themes_metablock_update',
		 'allow_multiple' => false,
		 'form_content' => false,
		 'form_refresh' => false,
		 'show_preview' => true);

}

/**
 * Display func.
 * @param $blockinfo array containing title,content
 */
function themes_metablock_display($blockinfo)
{
    if (!xarSecAuthAction(0, 'themes:metablock', "$blockinfo[title]::", ACCESS_OVERVIEW)) {
        return;
    }

    //$incoming = xarVarGetCached('Blocks.articles','summary');
    //$incoming .= ' ';  
    $incoming = xarVarGetCached('Blocks.articles','body');

    if (!empty($incoming)){

        // The following is from PostNuke's Header.php.  I believe the original function
        // Was written by Tim Litwiller.

        // TODO Strip words that are used more than once.
        // TODO Strip all html.
        $htmlless = check_html($incoming, $strip ='nohtml');
        $symbolLess = trim(ereg_replace('("|\?|!|:|\.|\(|\)|;|\\\\)+', ' ', $htmlless));

        $keywords = explode(" ", strtolower($symbolLess));
        $keywords = array_unique($keywords);
        $text = implode(",",$keywords);
        /*
        $htmlless = check_html($incoming, $strip ='nohtml');
        $symbolLess = trim(ereg_replace('("|\?|!|:|\.|\(|\)|;|\\\\)+', ' ', $htmlless));
        $keywords = ereg_replace('( |'.CHR(10).'|'.CHR(13).')+', ',', $symbolLess);
        $text = ereg_replace(",+", ",",$keywords);
        */
    } else {
        $text = 1;
    }
    
    $blockinfo['content'] = $text;
    return $blockinfo;

}

/**
 * Modify Function to the Blocks Admin
 * @param $blockinfo array containing title,content
 */
function themes_metablock_modify($blockinfo)
{
    // Get current content
    $vars = @unserialize($blockinfo['content']);

 //   $content = xarTplBlock('base', 'htmlAdmin', $vars);

    return $blockinfo;
}

/**
 * Updates the Block config from the Blocks Admin
 * @param $blockinfo array containing title,content
 */
function themes_metablock_update($blockinfo)
{
    list($vars['expire'],
         $vars['html_content']) = xarVarCleanFromInput('expire',
                                                       'html_content');
    return $blockinfo;
}

function check_html ($str, $strip = '') {
    
    // The core of this code has been lifted from phpslash
    // which is licenced under the GPL.
      
    if ($strip == "nohtml")
        $AllowableHTML=array('');
    $str = stripslashes($str);
    $str = eregi_replace("<[[:space:]]*([^>]*)[[:space:]]*>",
                         '<\\1>', $str);
    // Delete all spaces from html tags .
    $str = eregi_replace("<a[^>]*href[[:space:]]*=[[:space:]]*\"?[[:space:]]*([^\" >]*)[[:space:]]*\"?[^>]*>",
                         '<a href="\\1">', $str); # "
    // Delete all attribs from Anchor, except an href, double quoted.
    $tmp = "";
    while (ereg("<(/?[[:alpha:]]*)[[:space:]]*([^>]*)>",$str,$reg)) {
        $i = strpos($str,$reg[0]);
        $l = strlen($reg[0]);
        if ($reg[1][0] == "/") $tag = strtolower(substr($reg[1],1));
        else $tag = strtolower($reg[1]);
        if (isset($AllowableHTML[$tag])) {
            if ($a=$AllowableHTML[$tag])
            if ($reg[1][0] == "/") $tag = "</$tag>";
            elseif (($a == 1) || ($reg[2] == "")) $tag = "<$tag>";
            else {
              # Place here the double quote fix function.
              $attrb_list=delQuotes($reg[2]);
              $tag = "<$tag" . $attrb_list . ">";
            } # Attribs in tag allowed
        } else $tag = "";
        $tmp .= substr($str,0,$i) . $tag;
        $str = substr($str,$i+$l);
    }
    $str = $tmp . $str;
    return $str;
    exit;
    // Squash PHP tags unconditionally
    $str = ereg_replace("<\?","",$str);
    return $str;
}

?>