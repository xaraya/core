<?php
// File: $Id$
// ----------------------------------------------------------------------
// Xaraya eXtensible Management System
// Copyright (C) 2002 by the Xaraya Development Team.
// http://www.xaraya.org
// ----------------------------------------------------------------------
// Original Author of file: Jim McDonald
// Purpose of file:  Example user display functions
// ----------------------------------------------------------------------

// ----------------------------------------------------------------------
// Hook functions (user GUI)
// ----------------------------------------------------------------------


// TODO: replace this with block/cached variables/special template tag/... ?
//
//       Ideally, people should be able to use the dynamic fields in their
//       module templates as if they were 'normal' fields -> this means
//       adapting the get() function in the user API of the module, perhaps...

/**
 * display dynamicdata for an item - hook for ('item','display','GUI')
 *
 * @param $args['objectid'] ID of the object
 * @param $args['extrainfo'] extra information
 * @returns bool
 * @return true on success, false on failure
 * @raise BAD_PARAM, NO_PERMISSION, DATABASE_ERROR
 */
function dynamicdata_user_displayhook($args)
{
    extract($args);

    if (!isset($extrainfo)) {
        $msg = xarML('Invalid #(1) for #(2) function #(3)() in module #(4)',
                    'extrainfo', 'user', 'displayhook', 'dynamicdata');
        xarExceptionSet(XAR_USER_EXCEPTION, 'BAD_PARAM',
                       new SystemException($msg));
        return $msg;
    }

    if (!isset($objectid) || !is_numeric($objectid)) {
        $msg = xarML('Invalid #(1) for #(2) function #(3)() in module #(4)',
                    'object ID', 'user', 'displayhook', 'dynamicdata');
        xarExceptionSet(XAR_USER_EXCEPTION, 'BAD_PARAM',
                       new SystemException($msg));
        return $msg;
    }

    // When called via hooks, the module name may be empty, so we get it from
    // the current module
    if (empty($extrainfo['module']) || !is_array($extrainfo['module'])) {
        $modname = xarModGetName();
    } else {
        $modname = $extrainfo['module'];
    }

    $modid = xarModGetIDFromName($modname);
    if (empty($modid)) {
        $msg = xarML('Invalid #(1) for #(2) function #(3)() in module #(4)',
                    'module name ' . $modname, 'user', 'displayhook', 'dynamicdata');
        xarExceptionSet(XAR_USER_EXCEPTION, 'BAD_PARAM',
                       new SystemException($msg));
        return $msg;
    }

    if (!empty($extrainfo['itemtype']) && is_numeric($extrainfo['itemtype'])) {
        $itemtype = $extrainfo['itemtype'];
    } else {
        $itemtype = null;
    }

    if (!empty($extrainfo['itemid']) && is_numeric($extrainfo['itemid'])) {
        $itemid = $extrainfo['itemid'];
    } else {
        $itemid = $objectid;
    }

    if (!xarModAPILoad('dynamicdata', 'user'))
    {
        $msg = xarML('Unable to load #(1) #(2) API',
                    'dynamicdata','user');
        xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'UNABLE_TO_LOAD',
                       new SystemException($msg));
        return $msg;
    }
    $fields = xarModAPIFunc('dynamicdata','user','getall',
                           array('modid' => $modid,
                                 'itemtype' => $itemtype,
                                 'itemid' => $itemid));
    if (!isset($fields) || $fields == false || count($fields) == 0) {
        return;
    }

// TODO: use custom template per module + itemtype ?
     return xarTplModule('dynamicdata','user','displayhook',
                         array('fields' => $fields));

}

// ----------------------------------------------------------------------
// TODO: all of the 'standard' user functions, if that makes sense someday...
//

/**
 * the main user function
 * This function is the default function, and is called whenever the module is
 * initiated without defining arguments.  As such it can be used for a number
 * of things, but most commonly it either just shows the module menu and
 * returns or calls whatever the module designer feels should be the default
 * function (often this is the view() function)
 */
function dynamicdata_user_main()
{
    // Security check - important to do this as early as possible to avoid
    // potential security holes or just too much wasted processing.  For the
    // main function we want to check that the user has at least overview
    // privilege for some item within this component, or else they won't be
    // able to see anything and so we refuse access altogether.  The lowest
    // level of access for users depends on the particular module, but it is
    // generally either 'overview' or 'read'
    if (!xarSecAuthAction(0, 'Example::', '::', ACCESS_OVERVIEW)) {
        $msg = xarML('Not authorized to access to #(1)',
                    'Example');
        xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'NO_PERMISSION',
                       new SystemException($msg));
        return;
    }

    // If you want to go directly to some default function, instead of
    // having a separate main function, you can simply call it here, and
    // use the same template for user-main.xd as for user-view.xd
    // return dynamicdata_user_view();

    // Initialise the $data variable that will hold the data to be used in
    // the blocklayout template, and get the common menu configuration - it
    // helps if all of the module pages have a standard menu at the top to
    // support easy navigation
    $data = dynamicdata_user_menu();

    // Specify some other variables used in the blocklayout template
    $data['welcome'] = xarML('Welcome to this Example module...');

    // Return the template variables defined in this function
    return $data;

    // Note : instead of using the $data variable, you could also specify
    // the different template variables directly in your return statement :
    //
    // return array('menutitle' => ...,
    //              'welcome' => ...,
    //              ... => ...);
}

/**
 * view a list of items
 * This is a standard function to provide an overview of all of the items
 * available from the module.
 */
function dynamicdata_user_view()
{
    // Get parameters from whatever input we need.  All arguments to this
    // function should be obtained from xarVarCleanFromInput(), getting them
    // from other places such as the environment is not allowed, as that makes
    // assumptions that will not hold in future versions of PostNuke.
    // Note that for retrieving 1 parameter, we can use $var1 = ... (see below)
    $startnum = xarVarCleanFromInput('startnum');

    // Initialise the $data variable that will hold the data to be used in
    // the blocklayout template, and get the common menu configuration - it
    // helps if all of the module pages have a standard menu at the top to
    // support easy navigation
    $data = dynamicdata_user_menu();

    // Prepare the variable that will hold some status message if necessary
    $data['status'] = '';

    // Prepare the array variable that will hold all items for display
    $data['items'] = array();

    // Specify some other variables for use in the function template
    $data['someheader'] = xarMLByKey('EXAMPLENAME');
    $data['pager'] = '';

    // Security check - important to do this as early as possible to avoid
    // potential security holes or just too much wasted processing
    if (!xarSecAuthAction(0, 'Examples::', '::', ACCESS_OVERVIEW)) {
        $msg = xarML('Not authorized to access to #(1)',
                    'Example');
        xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'NO_PERMISSION',
                       new SystemException($msg));
        return;
    }

    // Load API.  All of the actual work for obtaining information on the items
    // is done within the API, so we need to load that in before we can do
    // anything.  If the API fails to load the raised exception is thrown back to PostNuke
    if (!xarModAPILoad('example', 'user')) return; // throw back

    // The API function is called.  The arguments to the function are passed in
    // as their own arguments array.
    // Security check 1 - the getall() function only returns items for which the
    // the user has at least OVERVIEW access.
    $items = xarModAPIFunc('example',
                          'user',
                          'getall',
                          array('startnum' => $startnum,
                                'numitems' => xarModGetVar('example',
                                                          'itemsperpage')));
    if (!isset($items) && xarExceptionMajor() != XAR_NO_EXCEPTION) return; // throw back

// TODO: check for conflicts between transformation hook output and
//       xarVarCensor / xarVarPrepForDisplay
    // Loop through each item and display it.  Note the use of xarVarCensor() to
    // remove any words from the name that the administrator has deemed
    // unsuitable for the site
    foreach ($items as $item) {

        // Let any transformation hooks know that we want to transform some text
        // You'll need to specify the item id, and an array containing all the
        // pieces of text that you want to transform (e.g. for autolinks, wiki,
        // smilies, bbcode, ...).
        // Note : for your module, you might not want to call transformation
        // hooks in this overview list, but only in the display of the details
        // in the display() function.
        //list($item['name']) = xarModCallHooks('item',
        //                                     'transform',
        //                                     $item['exid'],
        //                                     array($item['name']));

        // Security check 2 - if the user has read access to the item, show a
        // link to display the details of the item
        if (xarSecAuthAction(0,
                            'Examples::',
                            "$item[name]::$item[exid]",
                            ACCESS_READ)) {
            $item['link'] = xarModURL('example',
                                     'user',
                                     'display',
                                     array('exid' => $item['exid']));

        // Security check 2 - else only display the item name (or whatever is
        // appropriate for your module)
        } else {
            $item['link'] = '';
        }

        // Clean up the item text before display
        $item['name'] = xarVarPrepForDisplay(xarVarCensor($item['name']));

        // Add this item to the list of items to be displayed
        $data['items'][] = $item;
    }

// TODO: how to integrate cat ids in pager (automatically) when needed ???

// TODO: replace with a blocklayout pager
    // Create output object - this object will store all of our output so that
    // we can return it easily when required
    $output = new xarHTML();

    // Call the xarHTML helper function to produce a pager in case of there
    // being many items to display.
    //
    // Note that this function includes another user API function.  The
    // function returns a simple count of the total number of items in the item
    // table so that the pager function can do its job properly
    $output->Pager($startnum,
            xarModAPIFunc('example', 'user', 'countitems'),
                         xarModURL('example',
                                  'user',
                                  'view',
                                  array('startnum' => '%%')),
                         xarModGetVar('example', 'itemsperpage'));
    $data['pager'] = $output->GetOutput();

    // Specify some other variables for use in the function template
    $data['someheader'] = xarMLByKey('EXAMPLENAME');

    // Return the template variables defined in this function
    return $data;

    // Note : instead of using the $data variable, you could also specify
    // the different template variables directly in your return statement :
    //
    // return array('menu' => ...,
    //              'items' => ...,
    //              'pager' => ...,
    //              ... => ...);
}

/**
 * display an item
 * This is a standard function to provide detailed informtion on a single item
 * available from the module.
 *
 * @param $args an array of arguments (if called by other modules)
 * @param $args['objectid'] a generic object id (if called by other modules)
 * @param $args['exid'] the item id used for this example module
 */
function dynamicdata_user_display($args)
{
    // Get parameters from whatever input we need.  All arguments to this
    // function should be obtained from xarVarCleanFromInput(), getting them
    // from other places such as the environment is not allowed, as that makes
    // assumptions that will not hold in future versions of PostNuke.
    // Note that for retrieving several parameters, we use list($var1,$var2) =
    list($exid,
         $objectid) = xarVarCleanFromInput('exid',
                                          'objectid');

    // User functions of this type can be called by other modules.  If this
    // happens then the calling module will be able to pass in arguments to
    // this function through the $args parameter.  Hence we extract these
    // arguments *after* we have obtained any form-based input through
    // xarVarCleanFromInput().
    extract($args);

    // At this stage we check to see if we have been passed $objectid, the
    // generic item identifier.  This could have been passed in by a hook or
    // through some other function calling this as part of a larger module, but
    // if it exists it overrides $exid
    //
    // Note that this module could just use $objectid everywhere to avoid all
    // of this munging of variables, but then the resultant code is less
    // descriptive, especially where multiple objects are being used.  The
    // decision of which of these ways to go is up to the module developer
    if (!empty($objectid)) {
        $exid = $objectid;
    }

    // Initialise the $data variable that will hold the data to be used in
    // the blocklayout template, and get the common menu configuration - it
    // helps if all of the module pages have a standard menu at the top to
    // support easy navigation
    $data = dynamicdata_user_menu();

    // Prepare the variable that will hold some status message if necessary
    $data['status'] = '';

    // Load API.  All of the actual work for obtaining information on the items
    // is done within the API, so we need to load that in before we can do
    // anything.  If the API fails to load the raised exception is thrown back to PostNuke
    if (!xarModAPILoad('example', 'user')) return; // throw back

    // The API function is called.  The arguments to the function are passed in
    // as their own arguments array.
    // Security check 1 - the get() function will fail if the user does not
    // have at least READ access to this item (also see below).
    $item = xarModAPIFunc('example',
                          'user',
                          'get',
                          array('exid' => $exid));
    if (!isset($item) && xarExceptionMajor() != XAR_NO_EXCEPTION) return; // throw back

    // Security check 2 - if your API function does *not* check for the
    // appropriate access rights, or if for some reason you require higher
    // access than READ for this function, you *must* check this here !
    // if (!xarSecAuthAction(0, 'Examples::', "$item[name]::$item[exid]",
    //        ACCESS_COMMENT)) {
    //    // Fill in the status variable with the status to be shown
    //    $data['status'] = _EXAMPLENOAUTH;
    //    // Return the template variables defined in this function
    //    return $data;
    //}

    // Let any transformation hooks know that we want to transform some text.
    // You'll need to specify the item id, and an array containing all the
    // pieces of text that you want to transform (e.g. for autolinks, wiki,
    // smilies, bbcode, ...).
    list($item['name']) = xarModCallHooks('item',
                                         'transform',
                                         $item['exid'],
                                         array($item['name']));

// TODO: check for conflicts between transformation hook output and
//       xarVarCensor / input parsing of Text() by xarHTML
    // Fill in the details of the item.  Note the use of xarVarCensor() to remove
    // any words from the name that the administrator has deemed unsuitable for
    // the site.  Also note that a module variable is used here to determine
    // whether or not parts of the item information should be displayed in
    // bold type or not
    $data['name_label'] = xarMLByKey('EXAMPLENAME');
    $data['name_value'] = xarVarCensor($item['name']);
    $data['number_label'] = xarMLByKey('EXAMPLENUMBER');
    $data['number_value'] = $item['number'];

    $data['is_bold'] = xarModGetVar('example', 'bold');
    // Note : module variables can also be specified directly in the
    // blocklayout template by using &xar-mod-<modname>-<varname>;

    // Note that you could also pass on the $item variable, and specify
    // the labels directly in the blocklayout template. But make sure you
    // use the <xar:ml>, <xar:mlstring> or <xar:mlkey> tags then, so that
    // labels can be translated for other languages...


    // Save the currently displayed item ID in a temporary variable cache
    // for any blocks that might be interested (e.g. the Others block)
    // You should use this -instead of globals- if you want to make
    // information available elsewhere in the processing of this page request
    xarVarSetCached('Blocks.example', 'exid', $exid);

    // Let any hooks know that we are displaying an item.  As this is a display
    // hook we're passing a URL as the extra info, which is the URL that any
    // hooks will show after they have finished their own work.  It is normal
    // for that URL to bring the user back to this function
    $data['hookoutput'] = xarModCallHooks('item',
                                         'display',
                                         $exid,
                                         xarModURL('example',
                                                  'user',
                                                  'display',
                                                  array('exid' => $exid)));

    // Return the template variables defined in this function
    return $data;

    // Note : instead of using the $data variable, you could also specify
    // the different template variables directly in your return statement :
    //
    // return array('menu' => ...,
    //              'item' => ...,
    //              'hookoutput' => ...,
    //              ... => ...);
}

/**
 * generate the common menu configuration
 */
function dynamicdata_user_menu()
{
    // Initialise the array that will hold the menu configuration
    $menu = array();

    // Specify the menu title to be used in your blocklayout template
    $menu['menutitle'] = xarMLByKey('EXAMPLE');

    // Specify the menu items to be used in your blocklayout template
    $menu['menulabel_view'] = xarMLByKey('EXAMPLEVIEW');
    $menu['menulink_view'] = xarModURL('example','user','view');

    // Specify the labels/links for more menu items if relevant
    // $menu['menulabel_other'] = xarML('Some other menu item');
    // $menu['menulink_other'] = xarModURL('example','user','other');
    // ...

    // Note : you could also put all menu items in a $menu['menuitems'] array
    //
    // Initialise the array that will hold the different menu items
    // $menu['menuitems'] = array();
    //
    // Define a menu item
    // $item = array();
    // $item['menulabel'] = _EXAMPLEVIEW;
    // $item['menulink'] = xarModURL('example','user','view');
    //
    // Add it to the array of menu items
    // $menu['menuitems'][] = $item;
    //
    // Add more menu items to the array
    // ...
    //
    // Then you can let the blocklayout template create the different
    // menu items *dynamically*, e.g. by using something like :
    //
    // <xar:loop name="$menuitems">
    //    <td><a href="&xar-var-menulink;">&xar-var-menulabel;</a></td>
    // </xar:loop>
    //
    // in the templates of your module. Or you could even pass an argument
    // to the user_menu() function to turn links on/off automatically
    // depending on which function is currently called...
    //
    // But most people will prefer to specify all this manually in each
    // blocklayout template anyway :-)

    // Return the array containing the menu configuration
    return $menu;
}

//
// TODO: all of the 'standard' user functions, if that makes sense someday...
// ----------------------------------------------------------------------

?>
