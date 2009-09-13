<?php
/**
 * Parse a version number into an associative array of components
 *
 * @param $args['version'] string version number to parse
 * @return array on success or false on faliure
 */
function base_versionsapi_parse($args)
{
    if (!isset($args['version'])) throw new Exception(xarML('Missing a version parameter'));
    
    $filter = '/^([1-9]\d*|0)\.([1-9]\d*|0)\.([1-9]\d*|0)(-(a|b|rc)([1-9]\d*))?$/';
    if (!preg_match($filter, $args['version'],$matches)) return false;
    if (($matches[1] > 99) || ($matches[2] > 99) || ($matches[3] > 99)) return false;
    if (isset($matches[5])) {
        switch ($matches[5]) {
            case 'a':  $matches[5] = 1; break;
            case 'b':  $matches[5] = 2; break;
            case 'rc': $matches[5] = 3; break;
            default:   $matches[5] = 0; break;
        }
    } else {
            $matches[5] = 99;
    }
    if (!isset($matches[6])) $matches[6] = 99;
    if ($matches[6] > 99) return false;

    $number = (int)$matches[1] * 100000000
            + (int)$matches[2] * 1000000
            + (int)$matches[3] * 10000
            + (int)$matches[5] * 100
            + (int)$matches[6];

    $versionarray = array(
                        'version' => $args['version'],
                        'major' => $matches[1],
                        'minor' => $matches[2],
                        'micro' => $matches[3],
                        'suffix' => $matches[5],
                        'revision' => $matches[6],
                        'versionnumber' => $number,
                    );
    return $versionarray;
}

/**
 * Reassemble a parsed version number back into a string
 *
 * @param $args['versionnumber'] version number to reconstruct
 * @return string on success, false on failure
 */
function base_versionsapi_unparse($args)
{
    if (!isset($args['versionnumber'])) throw new Exception(xarML('Missing a version number parameter'));

    $major = (int)($args['versionnumber']/100000000);
    $versionnumber = $args['versionnumber'] - $major * 100000000;
    $minor = (int)($versionnumber/1000000);
    $versionnumber = $versionnumber - $minor * 1000000;
    $micro = (int)($versionnumber/10000);
    $versionnumber = $versionnumber - $micro * 10000;
    $suffix = (int)($versionnumber/100);
    $versionnumber = $versionnumber - $suffix * 100;
    $revision = (int)$versionnumber;
        
    switch ($suffix) {
        case 1:  $suffix = 'a'; break;
        case 2:  $suffix = 'b'; break;
        case 3:  $suffix = 'rc'; break;
        default: $suffix = ''; break;
    }
    $version = $major . '.' . $minor . '.' . $micro;
    if (!empty($suffix)) $version .= '-' . $suffix . $revision;
    return $version;
}

/**
 * Compare two legal-style versions supplied as strings
 *
 * @param $args['version1'] version number 1 (string)
 * @param $args['version2'] version number 2 (string)
 * @param $args['levels'] number of levels to use in the comparison
 * @return numeric number indicating which version number is the latest
 */
function base_versionsapi_compare($args)
{
    if (!isset($args['version1'])) throw new Exception(xarML('Missing a version1 parameter'));
    if (!isset($args['version2'])) throw new Exception(xarML('Missing a version2 parameter'));

    $version1 = xarModAPIFunc('base', 'versions', 'parse', array('version' => $args['version1']));
    $version2 = xarModAPIFunc('base', 'versions', 'parse', array('version' => $args['version2']));
    if (!$version1) throw new Exception(xarML('Could not parse the version1 parameter'));
    if (!$version2) throw new Exception(xarML('Could not parse the version2 parameter'));
    
    if (!isset($args['levels']) || !is_numeric($args['levels'])) $args['levels'] = 5;
    $power = 5 - $args['levels'];

    $factor = pow(100, $power);
    $version1 = (int)($version1['versionnumber']/$factor);
    $version2 = (int)($version2['versionnumber']/$factor);
    if ($version1 > $version2) return 1;
    elseif ($version1 < $version2) return 2;
    else return 0;
}

/**
 * Asserts that the Xaraya application version has reached a certain level.
 *
 * @param $args['version'] string version number to compare
 * @return boolean indicating whether the application is at least version $version
 */
function base_versionsapi_assert_application($args)
{
    if (!isset($args['version'])) throw new Exception(xarML('Missing a version1 parameter'));
    $result = xarModAPIfunc('base', 'versions', 'compare',
        array(
            'version1' => $args['version'],
            'version2' => xarConfigVars::get(null, 'System.Core.VersionNum'),
        )
    );
    return $result >= 0;
}
?>