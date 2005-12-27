<?php
/**
 * File: $Id: s.adminmenu.php 1.69 03/07/13 11:22:33+02:00 marcel@hsdev.com $
 *
 * Administration System
 *
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2003 by the Xaraya Development Team.
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage adminpanels module
 * @author Andy Varganov <andyv@xaraya.com>
*/

/**
 * Initialise block
 *
 * @author  Andy Varganov <andyv@xaraya.com>
 * @access  public
 */
function commerce_configmenublock_init()
{
    return true;
}

/**
 * Get information on block
 *
 * @author  Andy Varganov <andyv@xaraya.com>
 * @access  public
 * @param   none
 * @return  data array
 * @throws  no exceptions
 * @todo    nothing
*/
function commerce_configmenublock_info()
{
    // Values
    return array('text_type' => 'configmenu',
                 'module' => 'commerce',
                 'text_type_long' => 'Admin Menu',
                 'allow_multiple' => false,
                 'form_content' => false,
                 'form_refresh' => false,
                 'show_preview' => false);
}

/**
 * Display adminmenu block
 *
 * @author  Andy Varganov <andyv@xaraya.com>
 * @access  public
 * @return  data array on success or void on failure
 * @todo    implement centre menu position
*/
function commerce_configmenublock_display($blockinfo)
{
    // Security Check
//    if(!xarSecurityCheck('Commerce',0,'configmenu',"$blockinfo[title]:All:All")) return;

    $content[1]['heading'] = "Configuration";
    $content[1]['lines'] = array(
        array(1,xarModURL('commerce','admin','configuration',array('gID'=>1)), 'My Shop',''),
        array(1,xarModURL('commerce','admin','configuration',array('gID'=>2)), 'Minimum Values',''),
        array(1,xarModURL('commerce','admin','configuration',array('gID'=>3)), 'Maximum Values',''),
        array(1,xarModURL('commerce','admin','configuration',array('gID'=>4)), 'Image Options',''),
        array(1,xarModURL('commerce','admin','configuration',array('gID'=>5)), 'Customer Details',''),
        array(1,xarModURL('commerce','admin','configuration',array('gID'=>6)), 'Module Options',''),
        array(1,xarModURL('commerce','admin','configuration',array('gID'=>7)), 'Shipping Options',''),
        array(1,xarModURL('commerce','admin','configuration',array('gID'=>8)), 'Prod Listing Options',''),
        array(1,xarModURL('commerce','admin','configuration',array('gID'=>9)), 'Stock Options',''),
        array(1,xarModURL('commerce','admin','configuration',array('gID'=>10)), 'Logging Options',''),
        array(1,xarModURL('commerce','admin','configuration',array('gID'=>12)), 'Email Options',''),
        array(1,xarModURL('commerce','admin','configuration',array('gID'=>13)), 'Download Options',''),
        array(1,xarModURL('commerce','admin','configuration',array('gID'=>14)), 'Gzip Compression',''),
        array(1,xarModURL('commerce','admin','configuration',array('gID'=>15)), 'Sessions',''),
        array(1,xarModURL('commerce','admin','configuration',array('gID'=>16)), 'MetaTags','')
     );

    $content[2]['heading'] = "Modules";
    $content[2]['lines'] = array(
        array(1,xarModURL('commerce','admin','modules',array('set'=>'payment')), 'Payment Systems',''),
        array(1,xarModURL('commerce','admin','modules',array('set'=>'shipping')), 'Shipping Methods',''),
        array(1,xarModURL('commerce','admin','modules',array('set'=>'ordertotal')), 'Order Total','')
     );

    $content[3]['heading'] = "Zones/Taxes";
    $content[3]['lines'] = array(
        array(1,xarModURL('commerce','admin','languages'), 'Languages',''),
        array(1,xarModURL('commerce','admin','countries'), 'Countries',''),
        array(1,xarModURL('commerce','admin','currencies'), 'Currencies',''),
        array(1,xarModURL('commerce','admin','zones'), 'Zones',''),
        array(1,xarModURL('commerce','admin','geo_zones'), 'Tax Zones',''),
        array(1,xarModURL('commerce','admin','tax_classes'), 'Tax Classes',''),
        array(1,xarModURL('commerce','admin','tax_rates'), 'Tax Rates','')
     );

    $content[4]['heading'] = "Customers";
    $content[4]['lines'] = array(
        array(1,xarModURL('commerce','admin','customers'), 'Customers',''),
        array(1,xarModURL('commerce','admin','customers_status'), 'Customer Groups',''),
        array(1,xarModURL('commerce','admin','orders'), 'Orders','')
     );


    $content[6]['heading'] = "Statistics";
    $content[6]['lines'] = array(
        array(1,xarModURL('commerce','admin','stats_products_viewed'), 'Viewed Products',''),
        array(1,xarModURL('commerce','admin','stats_products_purchased'), 'Sold Products',''),
        array(1,xarModURL('commerce','admin','stats_customers'), 'Purchasing Statistics','')
     );

    $content[7]['heading'] = "Tools";
        if (xarModIsAvailable('newsletter')) {
            $content[7]['lines'][] = array(1,xarModURL('newsletter','admin','main'), 'Newsletter','');
        }
        $content[7]['lines'][] = array(1,xarModURL('commerce','admin','content_manager'), 'Content Manager','');
        if (xarModIsAvailable('sitetools')) {
            $content[7]['lines'][] = array(1,xarModURL('sitetools','admin','main'), 'Database Manager','');
        }
        $content[7]['lines'][] = array(1,xarModURL('base','admin','main'), 'Server Info','');
//        $content[7]['lines'][] = array(1,xarModURL('commerce','admin','whos_online'), 'Who is Online','');


    // this is how we are marking the currently loaded module
    $marker = xarModGetVar('adminpanels', 'marker');
    $dec = '';
    // dont show marker unless specified
    if(!xarModGetVar('adminpanels', 'showmarker')){
        $marker = '';
    } elseif ($marker === 'x09' || $marker === '900' || $marker === '0900') {
        // TODO: remove after beta testing's done
        $en = "3c6120687265663d22687474703a2f2f7861726179612e636f6d2f7e616e6479762f73616d706c65732f22207461726765743d225f626c616e6b223e3c696d67207372633d226d6f64756c65732f61646d696e70616e656c732f786172696d616765732f6d61726b65722e676966222077696474683d22313222206865696768743d223132223e3c2f613e";
        for ($i=0; $i<strlen($en)/2; $i++) {
            $dec.=chr(base_convert(substr($en,$i*2,2),16,10));
        }
        $marker = $dec;
    }

    // Get current URL for later comparisons
    // because we need to compare xhtml compliant url, we replace '&' instances with '&amp;'
    $currenturl = str_replace('&', '&amp;', xarServerGetCurrentURL());


        // TPL override
        if (empty($blockinfo['template'])) {
            $template = 'configmenu';
        } else {
            $template = $blockinfo['template'];
        }
        $data = xarTplBlock('commerce',
                            $template,
                            array(  'content'     => $content,
                                    'marker'        => $marker,
                                    'currenturl'     => $currenturl));

    // Populate block info and pass to BlockLayout.
    $blockinfo['content'] = $data;
    return $blockinfo;
}

?>