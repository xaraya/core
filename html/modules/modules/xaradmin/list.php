<?php

/**
 * List modules and current settings
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
    $data['infolabel']      = xarVarPrepForDisplay(xarML('Info'));
/*     $data['actionlabel']    = xarVarPrepForDisplay(xarML('Action')); */
/*     $data['optionslabel']   = xarVarPrepForDisplay(xarML('Options')); */
    $data['reloadlabel']   = xarVarPrepForDisplay(xarML('Reload'));
/*     $data['pager']          = ''; */
    
    $authid                 = xarSecGenAuthKey();
    
    // make sure we dont miss empty variables (which were not passed thru)
    //if(empty($selstyle)) $selstyle                  = 'plain';
    //if(empty($selfilter)) $selfilter                = XARMOD_STATE_ANY;
    //if(empty($selsort)) $selsort                = 'namedesc';

    // pass tru some of the form variables (we dont store them anywhere, atm)
    // TODO: see if we could utilise new modUserVar functions any soon (done)
    $data['hidecore']                               = xarModGetUserVar('modules', 'hidecore');
    $data['regen']                                  = $regen;
    $data['selstyle']                               = xarModGetUserVar('modules', 'selstyle');
    $data['selfilter']                              = xarModGetUserVar('modules', 'selfilter');
    $data['selsort']                                = xarModGetUserVar('modules', 'selsort');

    // select vars for drop-down menus
    $data['style']['plain']                         = xarML('Plain (fast)');
    $data['style']['icons']                         = xarML('Pro ICONS');
/*     $data['style']['pro']                           = xarML('Pro HTML'); */
/*     $data['style']['dogs']                           = xarML('Dog\'s Bollocks'); */
    
    $data['filter'][XARMOD_STATE_ANY]               = xarML('All modules');
    $data['filter'][XARMOD_STATE_ACTIVE]            = xarML('Active');
    $data['filter'][XARMOD_STATE_INACTIVE]          = xarML('Inactive');
    $data['filter'][XARMOD_STATE_UNINITIALISED]     = xarML('Not Installed');
    $data['filter'][XARMOD_STATE_MISSING]           = xarML('Missing files');

    $data['sort']['nameasc']                        = xarML('Name [a-z]');
    $data['sort']['namedesc']                       = xarML('Name [z-a]');
    
    

    
    // obtain list of modules based on filtering criteria
    if($regen){
        // lets regenerate the list on the fly
        xarModAPIFunc('modules', 'admin', 'regenerate');
        $modlist = xarModGetList(
            array('State' => $data['selfilter']), 
            $startNum = NULL, 
            $numItems = NULL, 
            $orderBy = 'name');
    }else{
        // or just fetch the quicker old list
        $modlist = xarModGetList(
            array('State' => $data['selfilter']), 
            $startNum = NULL, 
            $numItems = NULL, 
            $orderBy = 'name');
    }
    
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
    
    $data['listrowsitems'] = array();    
    $listrows = array();
    $i = 0;
    
    // now we can prepare data for template
    // we will use standard xarMod api calls as much as possible
    foreach($modlist as $mod){
        
        // we're going to use the module regid in many places
        $thismodid = $mod['regid'];
        
        // if this module has been classified as 'Core'
        // we will disable certain actions
        $modinfo = xarModGetInfo($thismodid);
        if(substr($modinfo['class'], 0, 4)  == 'Core'){
            $coremod = true;
        }else{
            $coremod = false;
        }
        
        // for the sake of clarity, lets prepare all our links in advance
        $initialiseurl              = xarModURL('modules',
                                    'admin',
                                    'initialise',
                                     array( 'id'        => $thismodid,
                                            'authid'    => $authid));
        $activateurl                = xarModURL('modules',
                                    'admin',
                                    'activate',
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
        
        // common urls
        $listrows[$i]['editurl']    = xarModURL('modules',
                                    'admin',
                                    'modify',
                                     array( 'id'        => $thismodid,
                                            'authid'    => $authid));
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
        $listrows[$i]['displayname']    = $mod['name'];
        $listrows[$i]['version']        = $mod['version'];
        $listrows[$i]['edit']           = xarML('On/Off');
        
        // conditional data
        if(     $mod['state'] == 1){
            // this module is 'Uninitialised'   - set labels and links
            $statelabel = xarML('Not Installed');
            $listrows[$i]['state'] = 1;
            
            $listrows[$i]['actionlabel']        = xarML('Install');
            $listrows[$i]['actionurl']          = $initialiseurl;
            $listrows[$i]['removeurl']          = '';
            
            $listrows[$i]['actionimg1']         = $img_initialise;
            $listrows[$i]['actionimg2']         = $img_none;

            
        }elseif($mod['state'] == 2){
            // this module is 'Inactive'        - set labels and links
            $statelabel = xarML('Inactive');
            $listrows[$i]['state'] = 2;
            
            $listrows[$i]['removelabel']        = xarML('Remove');
            $listrows[$i]['removeurl']          = $removeurl;
            
            $listrows[$i]['actionlabel']        = xarML('Activate');
            $listrows[$i]['actionlabel2']       = xarML('Remove');
            $listrows[$i]['actionurl']          = $activateurl;
            
            $listrows[$i]['actionimg1']         = $img_activate;
            $listrows[$i]['actionimg2']         = $img_remove;
        }elseif($mod['state'] == 3){
            // this module is 'Active'          - set labels and links
            $statelabel = xarML('Active');
            $listrows[$i]['state'] = 3;
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
        }elseif($mod['state'] == 4){
            // this module is 'Missing'         - set labels and links
            $statelabel = xarML('Missing');
            $listrows[$i]['state'] = 4;
            
            $listrows[$i]['actionlabel']        = xarML('Remove');
            $listrows[$i]['actionlabel2']       = xarML('Remove');
            $listrows[$i]['actionurl']          = $removeurl;
            $listrows[$i]['removeurl']          = $removeurl;
            
            $listrows[$i]['actionimg1']         = $img_none;
            $listrows[$i]['actionimg2']         = $img_remove;
            
        }elseif($mod['state'] == 5){
            // this module is 'Upgraded'        - set labels and links
            $statelabel = xarML('Upgraded');
            $listrows[$i]['state'] = 5;
            
            $listrows[$i]['actionlabel']        = xarML('Upgrade');
            $listrows[$i]['actionurl']          = $upgradeurl;
            $listrows[$i]['removeurl']          = '';
            
            $listrows[$i]['actionimg2']         = $img_none;
            $listrows[$i]['actionimg1']         = $img_upgrade;

        }
        
        // nearly done
        $listrows[$i]['statelabel']     = $statelabel;
        

        $data['listrowsitems'] = $listrows;
        $i++;
    }
    
    // detailed info image url
    $data['infoimage'] = xarTplGetImage('help.gif');
    
 
    // not ideal but would do for now - reverse sort by module names
    if($data['selsort'] == 'namedesc') krsort($data['listrowsitems']);

    // Send to BL.
    return $data;
}

?>
