<?php
/**
 * @package modules\base
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://xaraya.info/index.php/release/68.html
 * @author Marc Lutolf <marc@luetolf-carroll.com>
 */

/*
 * Remove or change unallowed characters from a file name
 * Taken from http://www.house6.com/blog/?p=83
 */
function base_adminapi_sanitize_filename($args)
{
    if (empty($args['filename'])) 
        throw new BadParameterException('filename');
    $f = $args['filename'];
        
     // a combination of various methods
     // we don't want to convert html entities, or do any url encoding
     // we want to retain the "essence" of the original file name, if possible
     // char replace table found at:
     // http://www.php.net/manual/en/function.strtr.php#98669
     $replace_chars = array(
     'Š'=>'S', 'š'=>'s', 'Ð'=>'Dj','Ž'=>'Z', 'ž'=>'z', 'À'=>'A', 'Á'=>'A', 'Â'=>'A', 'Ã'=>'A', 'Ä'=>'Ae',
     'Å'=>'A', 'Æ'=>'A', 'Ç'=>'C', 'Č'=>'C', 'È'=>'E', 'É'=>'E', 'Ê'=>'E', 'Ë'=>'E', 'Ì'=>'I', 'Í'=>'I', 'Î'=>'I',
     'Ï'=>'I', 'Ñ'=>'N', 'Ò'=>'O', 'Ó'=>'O', 'Ô'=>'O', 'Õ'=>'O', 'Ö'=>'Oe', 'Ø'=>'O', 'Ù'=>'U', 'Ú'=>'U',
     'Û'=>'U', 'Ü'=>'Ue', 'Ý'=>'Y', 'Þ'=>'B', 'ß'=>'Ss','à'=>'a', 'á'=>'a', 'â'=>'a', 'ã'=>'a', 'ä'=>'ae',
     'å'=>'a', 'æ'=>'a', 'ç'=>'c', 'ć'=>'c', 'č'=>'c', 'è'=>'e', 'é'=>'e', 'ê'=>'e', 'ė'=>'e', 'ë'=>'e', 'ì'=>'i', 'í'=>'i', 'î'=>'i',
     'ï'=>'i', 'ð'=>'o', 'ñ'=>'n', 'ò'=>'o', 'ó'=>'o', 'ô'=>'o', 'õ'=>'o', 'ö'=>'oe', 'ø'=>'o', 'ù'=>'u',
     'ü'=>'ue','ú'=>'u', 'û'=>'u', 'ý'=>'y', 'þ'=>'b', 'ÿ'=>'y', 'ƒ'=>'f'
     );
     $f = str_replace(array_keys($replace_chars), $replace_chars, $f);
     // convert & to "and", @ to "at", and # to "number"
     $f = preg_replace(array('/[\&]/', '/[\@]/', '/[\#]/'), array('-and-', '-at-', '-number-'), $f);
     $f = preg_replace('/[^(\x20-\x7F)]*/','', $f); // removes any special chars we missed
     $f = str_replace(' ', '-', $f); // convert space to hyphen 
     $f = str_replace('\'', '', $f); // removes apostrophes
     $f = preg_replace('/[^\w\-\.]+/', '', $f); // remove non-word chars (leaving hyphens and periods)
     $f = preg_replace('/[\-]+/', '-', $f); // converts groups of hyphens into one
     return $f;
}
