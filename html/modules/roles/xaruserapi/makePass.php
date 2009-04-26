<?php
/**
 * Password creation
 *
 * @package modules
 * @copyright (C) 2002-2006 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Roles module
 * @link http://xaraya.com/index.php/release/27.html
 */
/*
    Please note:

    This is a suggestion for a stronger password creation. Define _SYLLABELS
    in a way you like (you do not need to use uppercase characters). In this
    example the letters a-z are thre times in the list, so we do not have
    to many numbers in the password.

    _MAKEPASS_LEN defines the length of the password, that will be produced;
    by default 8. If this suggestion will be taken over the define should be
    replaced by a configuration var.

    The _MAKEPASS_BOX defines the size of the random box. For a good secure
    it should be 1,000,000 but this is not possible on most of the servers
    (memory restrictions) and it took a while to create the box. The box
    contains a random collection of _SYLLABELS with some uppercases (about
    nearly 20% are uppercase).

    I do not see a high risk if _MAKEPASS_BOX will be reduced to a value
    of 10,000.

    hdonner
*/
define('_SYLLABELS', "*abcdefghijklmnopqrstuvwxyzabcdefghijklmnopqrstuvwxyzabcdefghijklmnopqrstuvwxyz0123456789");
define('_MAKEPASS_LEN', 8);
define('_MAKEPASS_BOX', 5000);

// taken from php.net
//
function _make_seed()
{
    list($usec, $sec) = explode(' ', microtime());
    return (float) $sec + ((float) $usec * 100000);
}

function roles_userapi_makepass()
{
    // init some
    $result = '';
    mt_srand(_make_seed());
    $syllabels = _SYLLABELS;
    $len = strlen($syllabels) - 1;
    $box = "";

    // create box
    for($i = 0; $i < _MAKEPASS_BOX; $i++) {
        $ch = $syllabels[mt_rand(0, $len)];
        // about 20% upper case letters
        if (mt_rand(0, $len) % 5 == 1) {
            $ch = strtoupper($ch);
        }
        // filling up the box with random chars
        $box .= $ch;
    }

    // now collect password from box
    for($i = 0; $i < _MAKEPASS_LEN; $i++) {
        $result .= $box[mt_rand(0, (_MAKEPASS_BOX - 1))];
    }

    return $result;
}
?>