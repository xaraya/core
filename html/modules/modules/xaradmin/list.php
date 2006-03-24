<?php
/**
 * List modules and current settings
 *
 * @package modules
 * @copyright (C) 2002-2006 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Module System
 * @link http://xaraya.com/index.php/release/1.html
 */
/**
 * List modules and current settings
 * @author Xaraya Development Team
 * @param several params from the associated form in template
 * @todo  finish cleanup, styles, filters and sort orders
 */
function modules_admin_list()
{
    // Security Check
    if(!xarSecurityCheck('AdminModules')) return;

    // form parameters
    if (!xarVarFetch('startnum', 'isset', $startnum, NULL, XARVAR_DONT_SET)) {return;}
    if (!xarVarFetch('regen',    'isset', $regen,    NULL, XARVAR_DONT_SET)) {return;}

    // Specify labels for display (most are done in template now)
    $data['infolabel']      = xarML('Info');
    $data['reloadlabel']    = xarML('Reload');

    $authid                 = xarSecGenAuthKey();

    // make sure we dont miss empty variables (which were not passed thru)
    //if(empty($selstyle)) $selstyle                  = 'plain';
    //if(empty($selfilter)) $selfilter                = XARMOD_STATE_ANY;
    //if(empty($selsort)) $selsort                = 'namedesc';

    // pass tru some of the form variables (we dont store them anywhere, atm)
    $data['hidecore']                               = xarModGetUserVar('modules', 'hidecore');
    $data['regen']                                  = $regen;
    $data['selstyle']                               = xarModGetUserVar('modules', 'selstyle');
    $data['selfilter']                              = xarModGetUserVar('modules', 'selfilter');
    $data['selsort']                                = xarModGetUserVar('modules', 'selsort');

    // select vars for drop-down menus
    $data['style']['plain']                         = xarML('Plain');
    $data['style']['icons']                         = xarML('Icons');
//    $data['style']['compacta']                      = xarML('Compact-A');
//    $data['style']['compactb']                      = xarML('Compact-B');

    $data['filter'][XARMOD_STATE_ANY]               = xarML('All Modules');
    $data['filter'][XARMOD_STATE_INSTALLED]         = xarML('All Installed');
    $data['filter'][XARMOD_STATE_ACTIVE]            = xarML('All Active');
    $data['filter'][XARMOD_STATE_INACTIVE]          = xarML('All Inactive');
    $data['filter'][XARMOD_STATE_UPGRADED]          = xarML('All Upgraded');
    $data['filter'][XARMOD_STATE_UNINITIALISED]     = xarML('Not Installed');
    $data['filter'][XARMOD_STATE_MISSING_FROM_UNINITIALISED] = xarML('Missing (Not Installed)');
    $data['filter'][XARMOD_STATE_MISSING_FROM_INACTIVE] = xarML('Missing (Inactive)');
    $data['filter'][XARMOD_STATE_MISSING_FROM_ACTIVE]   = xarML('Missing (Active)');
    $data['filter'][XARMOD_STATE_MISSING_FROM_UPGRADED] = xarML('Missing (Upgraded)');
    $data['filter'][XARMOD_STATE_ERROR_UNINITIALISED]  = xarML('Update (Not Installed)');
    $data['filter'][XARMOD_STATE_ERROR_INACTIVE]       = xarML('Update (Inactive)');
    $data['filter'][XARMOD_STATE_ERROR_ACTIVE]         = xarML('Update (Active)');
    $data['filter'][XARMOD_STATE_ERROR_UPGRADED]       = xarML('Update (Upgraded)');


    $data['sort']['nameasc']                        = xarML('Name [a-z]');
    $data['sort']['namedesc']                       = xarML('Name [z-a]');


    // reset session-based message var
    xarSessionDelVar('statusmsg');

    // obtain list of modules based on filtering criteria
    // think we need to always check the filesystem
    if(!xarModAPIFunc('modules', 'admin', 'regenerate')) return;
    $modlist = xarModAPIFunc('modules','admin','getlist',array('filter' => array('State' => $data['selfilter'], 'numitems' =>20)));

    // get action icons/images
    $img_disabled       = xarTplGetImage('set1/disabled.png');
    $img_none           = xarTplGetImage('set1/none.png');
    $img_activate       = xarTplGetImage('set1/activate.png');
    $img_deactivate     = xarTplGetImage('set1/deactivate.png');
    $img_upgrade        = xarTplGetImage('set1/upgrade.png');
    $img_initialise     = xarTplGetImage('set1/initialise.png');
    $img_remove         = xarTplGetImage('set1/remove.png');

    // get other images
    $data['infoimg']    = xarTplGetImage('set1/info.png');
    $data['editimg']    = xarTplGetImage('set1/hooks.png');
    $data['propimg']    = xarTplGetImage('set1/hooks.png');

    $data['listrowsitems'] = array();
    $listrows = array();
    $i = 0;

    // now we can prepare data for template
    // we will use standard xarMod api calls as much as possible
    //We want class as Authentication for auth mods so need to allow for this. Use hardcode list or core mods for now.
    $coreMods = array('base','roles','privileges','blocks','themes','authsystem','mail','dynamicdata','installer','modules');
    foreach($modlist as $mod){

        // we're going to use the module regid in many places
        $thismodid = $mod['regid'];
        $listrows[$i]['modid'] = $thismodid;
        // if this module has been classified as 'Core'
        // we will disable certain actions
        $modinfo = xarModGetInfo($thismodid);
        $coremod = in_array(strtolower($modinfo['name']),$coreMods);
        /* coreMods are hardcoded so we can gain independance from class for now for core mods
        if(substr($modinfo['class'], 0, 4)  == 'Core'){
            $coremod = true;
        }else{
            $coremod = false;
        }
        */

        // lets omit core modules if a user chosen to hide them from the list
        if($coremod && $data['hidecore']) continue;

        // for the sake of clarity, lets prepare all our links in advance
        $installurl                = xarModURL('modules',
                                    'admin',
                                    'install',
                                     array( 'id'        => $thismodid,
                                            'authid'    => $authid));
        $deactivateurl              = xarModURL('modules',
                                    'admin',
                                    'deactivate',
                                     array( 'id'        => $thismodid,
                                            'authid'    => $authid));
        $removeurl                  = xarModURL('modules',
                                    'admin',
                                    'remove',
                                     array( 'id'        => $thismodid,
                                            'authid'    => $authid));
        $upgradeurl                 = xarModURL('modules',
                                    'admin',
                                    'upgrade',
                                     array( 'id'        => $thismodid,
                                            'authid'    => $authid));

        $errorurl                   = xarModURL('modules',
                                    'admin',
                                    'viewerror',
                                     array( 'id'        => $thismodid,
                                            'authid'    => $authid));


        // link to module main admin function if any
        $listrows[$i]['modconfigurl'] = '';
        if(isset($mod['admin']) && $mod['admin'] == 1 && $mod['state'] == XARMOD_STATE_ACTIVE){
            $listrows[$i]['modconfigurl'] = xarModURL($mod['name'], 'admin');
            // link title for modules main admin function - common
            $listrows[$i]['adminurltitle'] = xarML('Go to administration of');
        }

        // common urls
        $listrows[$i]['editurl']    = xarModURL('modules',
                                    'admin',
                                    'modify',
                                     array( 'id'        => $thismodid));
        $listrows[$i]['propurl']    = xarModURL('modules',
                                    'admin',
                                    'modifyproperties',
                                     array( 'id'        => $thismodid));
        $listrows[$i]['infourl']    = xarModURL('modules',
                                    'admin',
                                    'modinfo',
                                     array( 'id'        => $thismodid,
                                            'authid'    => $authid));
        // added due to the feature request - opens info in new window
        $listrows[$i]['infourlnew'] = xarModURL('modules',
                                    'admin',
                                    'modinfonew',
                                    array( 'id'        => $thismodid));
        // image urls


        // common listitems
        $listrows[$i]['coremod']        = $coremod;
        $listrows[$i]['name']           = $mod['name'];
        $listrows[$i]['displayname']    = $mod['displayname'];
        $listrows[$i]['version']        = $mod['version'];
        $listrows[$i]['regid']          = $thismodid;
        $listrows[$i]['edit']           = xarML('On/Off');
        $listrows[$i]['prop']           = xarML('Modify');

        // conditional data
        if($mod['state'] == XARMOD_STATE_UNINITIALISED){
            // this module is 'Uninitialised' or 'Not Installed' - set labels and links
            $statelabel = xarML('Not Installed');
            $listrows[$i]['state'] = XARMOD_STATE_UNINITIALISED;

            $listrows[$i]['actionlabel']        = xarML('Install');
            $listrows[$i]['actionurl']          = $installurl;
            $listrows[$i]['removeurl']          = '';

            $listrows[$i]['actionimg1']         = $img_initialise;
            $listrows[$i]['actionimg2']         = $img_none;


        }elseif($mod['state'] == XARMOD_STATE_INACTIVE){
            // this module is 'Inactive'        - set labels and links
            $statelabel = xarML('Inactive');
            $listrows[$i]['state'] = XARMOD_STATE_INACTIVE;

            $listrows[$i]['removelabel']        = xarML('Remove');
            $listrows[$i]['removeurl']          = $removeurl;

            $listrows[$i]['actionlabel']        = xarML('Activate');
            $listrows[$i]['actionlabel2']       = xarML('Remove');
            $listrows[$i]['actionurl']          = $installurl;

            $listrows[$i]['actionimg1']         = $img_activate;
            $listrows[$i]['actionimg2']         = $img_remove;
        }elseif($mod['state'] == XARMOD_STATE_ACTIVE){
            // this module is 'Active'          - set labels and links
            $statelabel = xarML('Active');
            $listrows[$i]['state'] = XARMOD_STATE_ACTIVE;
            // here we are checking for module class
            // to prevent ppl messing with the core modules
            if(!$coremod){
                $listrows[$i]['actionlabel']    = xarML('Deactivate');
                $listrows[$i]['actionurl']      = $deactivateurl;
                $listrows[$i]['removeurl']      = '';

                $listrows[$i]['actionimg1']     = $img_deactivate;
                $listrows[$i]['actionimg2']     = $img_none;
            }else{
                $listrows[$i]['actionlabel']    = xarML('[core module]');
                $listrows[$i]['actionurl']      = '';
                $listrows[$i]['removeurl']      = '';

                $listrows[$i]['actionimg1']     = $img_disabled;
                $listrows[$i]['actionimg2']     = $img_disabled;
            }
        }elseif($mod['state'] == XARMOD_STATE_MISSING_FROM_UNINITIALISED ||
                $mod['state'] == XARMOD_STATE_MISSING_FROM_INACTIVE ||
                $mod['state'] == XARMOD_STATE_MISSING_FROM_ACTIVE ||
                $mod['state'] == XARMOD_STATE_MISSING_FROM_UPGRADED){
            // this module is 'Missing'         - set labels and links
            $statelabel = xarML('Missing');
            $listrows[$i]['state'] = XARMOD_STATE_MISSING_FROM_UNINITIALISED;

            $listrows[$i]['actionlabel']        = xarML('Remove');
            $listrows[$i]['actionlabel2']       = xarML('Remove');
            $listrows[$i]['actionurl']          = $removeurl;
            $listrows[$i]['removeurl']          = $removeurl;

            $listrows[$i]['actionimg1']         = $img_none;
            $listrows[$i]['actionimg2']         = $img_remove;
        }elseif($mod['state'] == XARMOD_STATE_ERROR_UNINITIALISED ||
                $mod['state'] == XARMOD_STATE_ERROR_INACTIVE ||
                $mod['state'] == XARMOD_STATE_ERROR_ACTIVE ||
                $mod['state'] == XARMOD_STATE_ERROR_UPGRADED){
            // Bug 1664 - this module db version is greater than file version
            // 'Error' - set labels and links
            $statelabel = xarML('Error');
            $listrows[$i]['state'] = XARMOD_STATE_ERROR_UNINITIALISED;

            $listrows[$i]['actionlabel']        = xarML('View Error');
            $listrows[$i]['actionurl']          = $errorurl;
            $listrows[$i]['removeurl']          = '';

            $listrows[$i]['actionimg1']         = $img_disabled;
            $listrows[$i]['actionimg2']         = $img_disabled;
        }elseif($mod['state'] == XARMOD_STATE_UPGRADED){
            // this module is 'Upgraded'        - set labels and links
            $statelabel = xarML('New version');
            $listrows[$i]['state'] = XARMOD_STATE_UPGRADED;

            $listrows[$i]['actionlabel']        = xarML('Upgrade');
            $listrows[$i]['actionurl']          = $upgradeurl;
            $listrows[$i]['removeurl']          = '';

            $listrows[$i]['actionimg2']         = $img_none;
            $listrows[$i]['actionimg1']         = $img_upgrade;

        } else {
      // Something seriously wrong
      $statelabel = xarML('Unknown');
      $listrows[$i]['actionurl'] = $removeurl;
          $listrows[$i]['actionlabel'] = xarML('Remove (Bug! in list generation)');
          $listrows[$i]['state'] = xarML('Remove');

        }

        // nearly done
        $listrows[$i]['statelabel']     = $statelabel;


        $data['listrowsitems'] = $listrows;
        $i++;
    }

    // total count of items
    $data['totalitems'] = $i;

    // detailed info image url
    $data['infoimage'] = xarTplGetImage('help.gif');

    // not ideal but would do for now - reverse sort by module names
    if($data['selsort'] == 'namedesc') krsort($data['listrowsitems']);

    // special sort for compact-b style
    if($data['selstyle'] == 'compactb'){
        if($i >= 2){
        // more than 2 items in the array, we need to sort it, dont bother is less
            $temparray = array_chunk($data['listrowsitems'], $i/2+1);
            $newarray = array();
            for($j = 0; $j <= $i/2; $j++){
                if(!empty($temparray[0][$j])) array_push($newarray,$temparray[0][$j]);
                if(!empty($temparray[1][$j])) array_push($newarray,$temparray[1][$j]);
            }
            $data['listrowsitems'] = $newarray;
        }
    }

    // Include 'smoothscroll' JavaScript.
    // TODO: move this to a template widget when available.
    xarModAPIfunc('base', 'javascript', 'modulefile',
        array('module'=>'modules', 'filename'=>'smoothscroll.js')
    );

    // Send to BL.
    return $data;
}

?>