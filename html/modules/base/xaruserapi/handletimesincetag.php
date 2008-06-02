<?php
/**
 * Time Since Tag Handler
 *
 * @package modules
 * @copyright (C) 2002-2006 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Base module
 * @link http://xaraya.com/index.php/release/68.html
 */

/*
 * ProvHandling for <xar:base-timesince ...>  tags
 *
 * Format: <xar:base-timesince stamp="originaltime" />
 *         stamp attribute has a value of a unix timestamp
 *         Output is a string in years, months, week, days, hours, minutes ago format
 *
 * @author jojodee
 * @param timestamp $stamp
 * @returns string
 * @return the PHP code needed to invoke timesince in the BL template
 */
function base_userapi_handletimesincetag($args)
{
     $out = "echo xarModAPIFunc('base', 'user', 'timesince',\n";
     $out .= " array(\n";
        foreach ($args as $key => $val) {
            if (is_numeric($val) || substr($val,0,1) == '$') {
                $out .= " '$key' => $val,\n";
            } else {
                $out .= " '$key' => '.$val.',\n";
            }
        }
        $out .= "));";

return $out;
}

?>