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
    return true;
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
    if(!xarSecurityCheck('ReadDynamicDataBlock',1,'Block',"$blockinfo[title]:All:All")) return;

    // Get variables from content block
    $vars = @unserialize($blockinfo['content']);

    // Defaults
    if (empty($vars['numitems'])) {
        $vars['numitems'] = 5;
    }

    // Database information
    xarModDBInfoLoad('dynamicdata');
    list($dbconn) = xarDBGetConn();
    $xartable =xarDBGetTables();
    $dynamicdata = $xartable['dynamic_data'];

    // Query
    $sql = "SELECT xar_exid,
                   xar_name
            FROM $dynamicdata
            ORDER by xar_name";
    $result = $dbconn->SelectLimit($sql, $vars['numitems']);

    if ($dbconn->ErrorNo() != 0) {
        return;
    }

    if ($result->EOF) {
        return;
    }
    // Create output object
    $output = new xarHTML();

    // Display each item, privileges permitting
    for (; !$result->EOF; $result->MoveNext()) {
        list($exid, $name) = $result->fields;

        if(!xarSecurityCheck('ViewDynamicDataBlocks',0,'Block',"$name:All:$exid")) {
            if(!xarSecurityCheck('ReadDynamicDataBlock',0,'Block',"$name:All:$exid")) {
                $output->URL(xarModURL('dynamicdata',
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
                               xarVarPrepForDisplay($vars['numitems']),
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
    $vars['numitems'] = xarVarCleanFromInput('numitems');

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
