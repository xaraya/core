<?php
/**
 * Overview displays standard Overview page
 *
 * @package modules
 * @copyright (C) 2002-2006 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Base module
 * @link http://xaraya.com/index.php/release/68.html
 */

/**
 * Overview displays standard Overview page
 *
 * Only used if you actually supply an overview link in your adminapi menulink function
 * and used to call the template that provides display of the overview
 *
 * @returns array xarTplModule with $data containing template data
 * @return array containing the menulinks for the overview item on the main manu
 * @since 2 Oct 2005
 */
function base_admin_overview()
{
   /* Security Check */
    if (!xarSecurityCheck('AdminBase',0)) return;
    if (!xarVarFetch('template','str:',$template,'',XARVAR_NOT_REQUIRED)) return;
    $data=array();
    /* if there is a separate overview function return data to it
     * else just call the main function that usually displays the overview 
     */

   if ($template !='') {
      return xarTplModule('base', 'admin', 'main', $data,$template);
   } else {
       return xarTplModule('base', 'admin', 'main', $data,'main');
   }
}

?>
