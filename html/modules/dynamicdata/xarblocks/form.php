<?php 
// File: $Id$
// ----------------------------------------------------------------------
// Xaraya eXtensible Management System
// Copyright (C) 2002 by the Xaraya Development Team.
// http://www.xaraya.org
// ----------------------------------------------------------------------
// Original Author of file: mikespub
// Purpose of file: DynamicData Form Block
// ----------------------------------------------------------------------

/**
 * initialise block
 */
function dynamicdata_formblock_init()
{
    // Security
    pnSecAddSchema('DynamicData:Formblock:', 'Block title::');
}

/**
 * get information on block
 */
function dynamicdata_formblock_info()
{
    // Values
    return array('text_type' => 'form',
                 'module' => 'dynamicdata',
                 'text_type_long' => 'Show dynamic data form',
                 'allow_multiple' => true,
                 'form_content' => false,
                 'form_refresh' => false,
                 'show_preview' => true);
}

/**
 * display block
 */
function dynamicdata_formblock_display($blockinfo)
{
    // Security check
    if (!pnSecAuthAction(0,
                         'DynamicData:Formblock:',
                         "$blockinfo[title]::",
                         ACCESS_READ)) {
        return;
    }

    // Get variables from content block
    $vars = @unserialize($blockinfo['content']);

    // Defaults
    if (empty($vars['numitems'])) {
        $vars['numitems'] = 5;
    }

    // Database information
    pnModDBInfoLoad('dynamicdata');
    list($dbconn) = pnDBGetConn();
    $pntable =pnDBGetTables();
    $dynamicdata = $pntable['dynamic_data'];

    // Query
    $sql = "SELECT pn_exid,
                   pn_name
            FROM $dynamicdata
            ORDER by pn_name";
    $result = $dbconn->SelectLimit($sql, $vars['numitems']);

    if ($dbconn->ErrorNo() != 0) {
        return;
    }

    if ($result->EOF) {
        return;
    }
    // Create output object
    $output = new pnHTML();

    // Display each item, permissions permitting
    for (; !$result->EOF; $result->MoveNext()) {
        list($exid, $name) = $result->fields;

        if (pnSecAuthAction(0,
                            'DynamicData::',
                            "$name::$exid",
                            ACCESS_OVERVIEW)) {
            if (pnSecAuthAction(0,
                                'DynamicData::',
                                "$name::$exid",
                                ACCESS_READ)) {
                $output->URL(pnModURL('dynamicdata',
                                      'user',
                                      'display',
                                      array('exid' => $exid)),
                             $name);
            } else {
                $output->Text($name);
            }
            $output->Linebreak();
        }

    }
    $output->Linebreak();

// TODO: shouldn't this stuff be BL-able too ??
// Besides the fact that title & content are placed according to some
// master block template, why can't we create content via BL ?

    // Populate block info and pass to theme
    $blockinfo['content'] = $output->GetOutput();
    return $blockinfo;
}


/**
 * modify block settings
 */
function dynamicdata_formblock_modify($blockinfo)
{
    // Create output object
    $output = new pnHTML();

    // Get current content
    $vars = @unserialize($blockinfo['content']);

    // Defaults
    if (empty($vars['numitems'])) {
        $vars['numitems'] = 5;
    }

    // Create row
    $row = array();
    $output->SetOutputMode(_PNH_RETURNOUTPUT);
    $row[] = $output->Text(_NUMITEMS);
    $row[] = $output->FormText('numitems',
                               pnVarPrepForDisplay($vars['numitems']),
                               5,
                               5);
    $output->SetOutputMode(_PNH_KEEPOUTPUT);

    // Add row
    $output->SetInputMode(_PNH_VERBATIMINPUT);
    $output->TableAddRow($row, 'left');
    $output->SetInputMode(_PNH_PARSEINPUT);

    // Return output
    return $output->GetOutput();
}

/**
 * update block settings
 */
function dynamicdata_formblock_update($blockinfo)
{
    $vars['numitems'] = pnVarCleanFromInput('numitems');

    $blockinfo['content'] = serialize($vars);

    return $blockinfo;
}

/**
 * built-in block help/information system.
 */
function dynamicdata_formblock_help()
{
    $output = new pnHTML();

    $output->SetInputMode(_PNH_VERBATIMINPUT);
    $output->Text('Any related block info should be placed in your modname_blocknameblock_help() function.');
    $output->LineBreak(2);
    $output->Text('More information.');
    $output->SetInputMode(_PNH_PARSEINPUT);

    return $output->GetOutput();
}
?>
