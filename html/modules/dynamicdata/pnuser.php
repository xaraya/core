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
    if (!pnSecAuthAction(0, 'Example::', '::', ACCESS_OVERVIEW)) {
        $msg = pnML('Not authorized to access to #(1)',
                    'Example');
        pnExceptionSet(PN_SYSTEM_EXCEPTION, 'NO_PERMISSION',
                       new SystemException($msg));
        return;
    }

    // If you want to go directly to some default function, instead of
    // having a separate main function, you can simply call it here, and
    // use the same template for user-main.pnd as for user-view.pnd
    // return dynamicdata_user_view();

    // Initialise the $data variable that will hold the data to be used in
    // the blocklayout template, and get the common menu configuration - it
    // helps if all of the module pages have a standard menu at the top to
    // support easy navigation
    $data = dynamicdata_user_menu();

    // Specify some other variables used in the blocklayout template
    $data['welcome'] = pnML('Welcome to this Example module...');

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
    // function should be obtained from pnVarCleanFromInput(), getting them
    // from other places such as the environment is not allowed, as that makes
    // assumptions that will not hold in future versions of PostNuke.
    // Note that for retrieving 1 parameter, we can use $var1 = ... (see below)
    $startnum = pnVarCleanFromInput('startnum');

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
    $data['someheader'] = pnMLByKey('EXAMPLENAME');
    $data['pager'] = '';

    // Security check - important to do this as early as possible to avoid
    // potential security holes or just too much wasted processing
    if (!pnSecAuthAction(0, 'Examples::', '::', ACCESS_OVERVIEW)) {
        $msg = pnML('Not authorized to access to #(1)',
                    'Example');
        pnExceptionSet(PN_SYSTEM_EXCEPTION, 'NO_PERMISSION',
                       new SystemException($msg));
        return;
    }

    // Load API.  All of the actual work for obtaining information on the items
    // is done within the API, so we need to load that in before we can do
    // anything.  If the API fails to load the raised exception is thrown back to PostNuke
    if (!pnModAPILoad('example', 'user')) return; // throw back

    // The API function is called.  The arguments to the function are passed in
    // as their own arguments array.
    // Security check 1 - the getall() function only returns items for which the
    // the user has at least OVERVIEW access.
    $items = pnModAPIFunc('example',
                          'user',
                          'getall',
                          array('startnum' => $startnum,
                                'numitems' => pnModGetVar('example',
                                                          'itemsperpage')));
    if (!isset($items) && pnExceptionMajor() != PN_NO_EXCEPTION) return; // throw back

// TODO: check for conflicts between transformation hook output and
//       pnVarCensor / pnVarPrepForDisplay
    // Loop through each item and display it.  Note the use of pnVarCensor() to
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
        //list($item['name']) = pnModCallHooks('item',
        //                                     'transform',
        //                                     $item['exid'],
        //                                     array($item['name']));

        // Security check 2 - if the user has read access to the item, show a
        // link to display the details of the item
        if (pnSecAuthAction(0,
                            'Examples::',
                            "$item[name]::$item[exid]",
                            ACCESS_READ)) {
            $item['link'] = pnModURL('example',
                                     'user',
                                     'display',
                                     array('exid' => $item['exid']));

        // Security check 2 - else only display the item name (or whatever is
        // appropriate for your module)
        } else {
            $item['link'] = '';
        }

        // Clean up the item text before display
        $item['name'] = pnVarPrepForDisplay(pnVarCensor($item['name']));

        // Add this item to the list of items to be displayed
        $data['items'][] = $item;
    }

// TODO: how to integrate cat ids in pager (automatically) when needed ???

// TODO: replace with a blocklayout pager
    // Create output object - this object will store all of our output so that
    // we can return it easily when required
    $output = new pnHTML();

    // Call the pnHTML helper function to produce a pager in case of there
    // being many items to display.
    //
    // Note that this function includes another user API function.  The
    // function returns a simple count of the total number of items in the item
    // table so that the pager function can do its job properly
    $output->Pager($startnum,
            pnModAPIFunc('example', 'user', 'countitems'),
                         pnModURL('example',
                                  'user',
                                  'view',
                                  array('startnum' => '%%')),
                         pnModGetVar('example', 'itemsperpage'));
    $data['pager'] = $output->GetOutput();

    // Specify some other variables for use in the function template
    $data['someheader'] = pnMLByKey('EXAMPLENAME');

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
    // function should be obtained from pnVarCleanFromInput(), getting them
    // from other places such as the environment is not allowed, as that makes
    // assumptions that will not hold in future versions of PostNuke.
    // Note that for retrieving several parameters, we use list($var1,$var2) =
    list($exid,
         $objectid) = pnVarCleanFromInput('exid',
                                          'objectid');

    // User functions of this type can be called by other modules.  If this
    // happens then the calling module will be able to pass in arguments to
    // this function through the $args parameter.  Hence we extract these
    // arguments *after* we have obtained any form-based input through
    // pnVarCleanFromInput().
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
    if (!pnModAPILoad('example', 'user')) return; // throw back

    // The API function is called.  The arguments to the function are passed in
    // as their own arguments array.
    // Security check 1 - the get() function will fail if the user does not
    // have at least READ access to this item (also see below).
    $item = pnModAPIFunc('example',
                          'user',
                          'get',
                          array('exid' => $exid));
    if (!isset($item) && pnExceptionMajor() != PN_NO_EXCEPTION) return; // throw back

    // Security check 2 - if your API function does *not* check for the
    // appropriate access rights, or if for some reason you require higher
    // access than READ for this function, you *must* check this here !
    // if (!pnSecAuthAction(0, 'Examples::', "$item[name]::$item[exid]",
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
    list($item['name']) = pnModCallHooks('item',
                                         'transform',
                                         $item['exid'],
                                         array($item['name']));

// TODO: check for conflicts between transformation hook output and
//       pnVarCensor / input parsing of Text() by pnHTML
    // Fill in the details of the item.  Note the use of pnVarCensor() to remove
    // any words from the name that the administrator has deemed unsuitable for
    // the site.  Also note that a module variable is used here to determine
    // whether or not parts of the item information should be displayed in
    // bold type or not
    $data['name_label'] = pnMLByKey('EXAMPLENAME');
    $data['name_value'] = pnVarCensor($item['name']);
    $data['number_label'] = pnMLByKey('EXAMPLENUMBER');
    $data['number_value'] = $item['number'];

    $data['is_bold'] = pnModGetVar('example', 'bold');
    // Note : module variables can also be specified directly in the
    // blocklayout template by using &pnt-mod-<modname>-<varname>;

    // Note that you could also pass on the $item variable, and specify
    // the labels directly in the blocklayout template. But make sure you
    // use the <pnt:ml>, <pnt:mlstring> or <pnt:mlkey> tags then, so that
    // labels can be translated for other languages...


    // Save the currently displayed item ID in a temporary variable cache
    // for any blocks that might be interested (e.g. the Others block)
    // You should use this -instead of globals- if you want to make
    // information available elsewhere in the processing of this page request
    pnVarSetCached('Blocks.example', 'exid', $exid);

    // Let any hooks know that we are displaying an item.  As this is a display
    // hook we're passing a URL as the extra info, which is the URL that any
    // hooks will show after they have finished their own work.  It is normal
    // for that URL to bring the user back to this function
    $data['hookoutput'] = pnModCallHooks('item',
                                         'display',
                                         $exid,
                                         pnModURL('example',
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
    $menu['menutitle'] = pnMLByKey('EXAMPLE');

    // Specify the menu items to be used in your blocklayout template
    $menu['menulabel_view'] = pnMLByKey('EXAMPLEVIEW');
    $menu['menulink_view'] = pnModURL('example','user','view');

    // Specify the labels/links for more menu items if relevant
    // $menu['menulabel_other'] = pnML('Some other menu item');
    // $menu['menulink_other'] = pnModURL('example','user','other');
    // ...

    // Note : you could also put all menu items in a $menu['menuitems'] array
    //
    // Initialise the array that will hold the different menu items
    // $menu['menuitems'] = array();
    //
    // Define a menu item
    // $item = array();
    // $item['menulabel'] = _EXAMPLEVIEW;
    // $item['menulink'] = pnModURL('example','user','view');
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
    // <pnt:loop name="menuitems">
    //    <td><a href="&pnt-var-menulink;">&pnt-var-menulabel;</a></td>
    // </pnt:loop>
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

?>
