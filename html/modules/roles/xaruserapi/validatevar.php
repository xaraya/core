<?php
/**
 * Validate a user variable
 *
 * @package modules
 * @copyright (C) 2002-2006 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Roles module
 * @link http://xaraya.com/index.php/release/27.html
 */

/**
 * validate a user variable
 * @access public
 * @author Damien Bonvillain
 * @author Gregor J. Rothfuss
 * @since 1.23 - 2002/02/01
 * @param var the variable to validate
 * @param type the type of the validation to perform
 * @param args optional array with validation-specific settings
 * @returns bool
 * @return true if the validation was successful, false otherwise
 * @todo move this to xarVar* api
 */
function roles_userapi_validatevar($args)
{

    extract($args);

    if (empty($type)) {
        $type = 'email';
    }

    switch ($type) {
        case 'email':
        default:
            // all characters must be 7 bit ascii
            $length = strlen($var);
            $idx = 0;
            while($length--) {
               $c = $var[$idx++];
               if(ord($c) > 127){
                  return false;
               }
            }
            $regexp = '/^(?:[^\s\000-\037\177\(\)<>@,;:\\"\[\]]\.?)+@(?:[^\s\000-\037\177\(\)<>@,;:\\\"\[\]]\.?)+\.[a-z]{2,6}$/Ui';
            if(preg_match($regexp,$var)) {
                return true;
            } else {
                return false;
            }
            break;

        case 'url':
            // all characters must be 7 bit ascii
            $length = strlen($var);
            $idx = 0;
            while($length--) {
               $c = $var[$idx++];
               if(ord($c) > 127){
                 return false;
               }
            }
            $regexp = '/^([!\$\046-\073=\077-\132_\141-\172~]|(?:%[a-f0-9]{2}))+$/i';
            if(!preg_match($regexp, $var)) {
                return false;
            }
            $url_array = @parse_url($var);
            if(empty($url_array)) {
                return false;
            } else {
                return !empty($url_array['scheme']);
            }
            break;
    }
}
?>