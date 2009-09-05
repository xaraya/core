<?php
/**
 * @package modules
 * @copyright (C) 2002-2006 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Base module
 * @link http://xaraya.com/index.php/release/68.html
 */
/**
 * Display some system information
 *
 * This information can be used for support / debugging
 *
 * @return array of info from phpinfo()
 */
function base_admin_sysinfo()
{
    xarVarFetch('what','int:-1:127',$what,INFO_GENERAL, XARVAR_NOT_REQUIRED);
    $data['what'] = $what;
    // Security Check
    if(!xarSecurityCheck('AdminBase')) return;
    // FIXME: dirty dirty
    ob_start();
    // FIXME: can we split this up in more manageable parts?
    phpinfo($what);
    $val_phpinfo = ob_get_contents();
    ob_end_clean();
    // get a substring of the php info to get rid of the html, head, title, etc.
    // Credit to Jason Judge.
    // Remove the header and footer.
    $val_phpinfo = preg_replace(
        array('/^.*<body[^>]*>/is', '/<\/body[^>]*>.*$/is'), '', $val_phpinfo, 1
    );
    // Remove pixel table widths.
    $val_phpinfo = preg_replace(
        '/width="[0-9]+"/i', 'width="80%"', $val_phpinfo
    );
    $data['phpinfo'] = $val_phpinfo;
    return $data;
}
?>
