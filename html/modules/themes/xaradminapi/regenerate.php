<?php

/**
 * Regenerate theme list
 *
 * @param none
 * @returns bool
 * @return true on success, false on failure
 * @raise NO_PERMISSION
 */
function themes_adminapi_regenerate()
{
// Security Check
	if(!xarSecurityCheck('AdminTheme')) return;

    //Get all themes in the filesystem
    $fileThemes = xarModAPIFunc('themes','admin','getfilethemes');
    if (!isset($fileThemes)) return;

    // Get all themes in DB
    $dbThemes = xarModAPIFunc('themes','admin','getdbthemes');
    if (!isset($dbThemes)) return;

    // See if we have lost any themes since last generation
    foreach ($dbThemes as $name => $themeInfo) {
        if (empty($fileThemes[$name])) {
            // Old theme
            // Get theme ID
            $regId = $themeInfo['regid'];
            // Set state of theme to 'missing'
            $set = xarModAPIFunc('themes',
                                'admin',
                                'setstate',
                                array('regid'=> $regId,
                                      'state'=> XARTHEME_STATE_MISSING));
            //throw back
            if (!isset($set)) return;

            unset($dbThemes[$name]);
        }
    }

    //Setup database object for theme insertion
    list($dbconn) = xarDBGetConn();
    $xartable = xarDBGetTables();
    // See if we have gained any themes since last generation,
    // or if any current themes have been upgraded
    foreach ($fileThemes as $name => $themeInfo) {

        if (empty($dbThemes[$name])) {
            // New theme
            $themeId = $dbconn->GenId($xartable['themes']);
            $sql = "INSERT INTO $xartable[themes]
                      (xar_id,
                       xar_name,
                       xar_regid,
                       xar_directory,
                       xar_author,
                       xar_homepage,
                       xar_email,
                       xar_description,
                       xar_contactinfo,
                       xar_publishdate,
                       xar_license,
                       xar_version,
                       xar_xaraya_version,
                       xar_bl_version,
                       xar_class)
                    VALUES
                      (" . xarVarPrepForStore($themeId) . ",
                       '" . xarVarPrepForStore($themeInfo['name']) . "',
                       '" . xarVarPrepForStore($themeInfo['regid']) . "',
                       '" . xarVarPrepForStore($themeInfo['directory']) . "',
                       '" . xarVarPrepForStore($themeInfo['author']) . "',
                       '" . xarVarPrepForStore($themeInfo['homepage']) . "',
                       '" . xarVarPrepForStore($themeInfo['email']) . "',
                       '" . xarVarPrepForStore($themeInfo['description']) . "',
                       '" . xarVarPrepForStore($themeInfo['contact_info']) . "',
                       '" . xarVarPrepForStore($themeInfo['publish_date']) . "',
                       '" . xarVarPrepForStore($themeInfo['license']) . "',
                       '" . xarVarPrepForStore($themeInfo['version']) . "',
                       '" . xarVarPrepForStore($themeInfo['xar_version']) . "',
                       '" . xarVarPrepForStore($themeInfo['bl_version']) . "',
                       '" . xarVarPrepForStore($themeInfo['class']) . "')";
            $result = $dbconn->Execute($sql);
            if (!$result) return;

            $set = xarModAPIFunc('themes',
                                'admin',
                                'setstate',
                                array('regid' => $themeInfo['regid'],
                                      'state' => XARTHEME_STATE_UNINITIALISED));
            if (!isset($set)) return;
        } else {
          // BEGIN bugfix (561802) - cmgrote
            if ($dbThemes[$name]['version'] != $themeInfo['version'] && $dbThemes[$name]['state'] != XARTHEME_STATE_UNINITIALISED) {
                    $set = xarModAPIFunc('themes',
                                        'admin',
                                        'setstate',
                                        array('regid' => $dbthemes[$name]['regid'],
                                              'state' => XARTHEME_STATE_UPGRADED));
                    if (!isset($set)) die('upgrade');
                }
        }
    }

    return true;
}

?>