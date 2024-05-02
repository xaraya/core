<?php
/**
 * Password creation
 *
 */
/*
    Please note:

    This is a suggestion for a stronger password creation. Define SYLLABLES
    in a way you like (you do not need to use uppercase characters). In this
    example the letters a-z are thre times in the list, so we do not have
    to many numbers in the password.

    PASSWORD_LENGTH defines the length of the password, that will be produced;
    by default 8. If this suggestion will be taken over the define should be
    replaced by a configuration var.

    The PASSWORD_BOX defines the size of the random box. For a good secure
    it should be 1,000,000 but this is not possible on most of the servers
    (memory restrictions) and it took a while to create the box. The box
    contains a random collection of SYLLABLES with some uppercases (about
    nearly 20% are uppercase).

    I do not see a high risk if PASSWORD_BOX will be reduced to a value
    of 10,000.

    hdonner
*/

/**
 * TODO: align PASSWORD_LENGTH with $validation_min_length in the passwordbox property?
 *
 */

class Password extends xarObject
{
	private const SYLLABLES       = "*abcdefghijklmnopqrstuvwxyzabcdefghijklmnopqrstuvwxyzabcdefghijklmnopqrstuvwxyz0123456789";
	private const PASSWORD_LENGTH = 8;
	private const PASSWORD_BOX    = 5000;

	public static function make_pass()
	{
    	$length = strlen(self::SYLLABLES) - 1;
    	
	    // Create the box
    	$box = '';
		for($i = 0; $i < self::PASSWORD_BOX; $i++) {
			$ch = self::SYLLABLES[mt_rand(0, $length)];
			// Set 20% of the characters to upper case letters
			if (mt_rand(0, $length) % 5 == 1) {
				// Make sure we have a number here
				if (is_numeric($ch)) $ch = strtoupper($ch);
			}
			// filling up the box with random chars
			$box .= $ch;
		}
	
		// Now collect password from the box
		$result = '';
		for($i = 0; $i < self::PASSWORD_LENGTH; $i++) {
			$result .= $box[mt_rand(0, (self::PASSWORD_BOX - 1))];
		}
	
		return $result;
	}
}

/**
 * @package modules\roles
 * @subpackage roles
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://xaraya.info/index.php/release/27.html
 */
function roles_userapi_makepass(array $args = [], $context = null)
{
    return Password::make_pass();
}
