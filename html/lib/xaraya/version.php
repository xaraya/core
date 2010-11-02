<?php
/**
 * Exceptions defined by this subsystem
 *
 * @package core
 * @package version
 */
class BadVersionException extends xarExceptions
{
    protected $message = 'The version number "#(1)" is not valid';
}

/**
 * Version utility class
 *
 * @package core
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage version
 * @author Marc Lutolf <mfl@netspan.ch>
**/

class xarVersion extends Object
{
/**
 * Parse a version number into an associative array of components
 *
 * @param $args['version'] string version number to parse
 * @return array on success or false on faliure
 */
    public static function parse($version='')
    {
        if (empty($version)) throw new Exception(xarML('Missing a version parameter'));

        $filter = '/^([1-9]\d*|0)\.([1-9]\d*|0)\.([1-9]\d*|0)(-(a|b|rc)([1-9]\d*))?$/';
        if (!preg_match($filter,$version,$matches)) return false;
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
                            'version' => $version,
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
    public static function assemble($versionnumber=0)
    {
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
 * @param $version1 version 1 (string)
 * @param $version2 version 2 (string)
 * @param $levels number of levels to use in the comparison
 * @return int number indicating which version number is the latest
 */
    public static function compare($versionstring1='', $versionstring2='', $levels=5)
    {
        $version1 = self::parse($versionstring1);
        $version2 = self::parse($versionstring2);
        if (!$version1) throw new BadVersionException($version1);
        if (!$version2) throw new BadVersionException($version2);

        if (!isset($args['levels']) || !is_numeric($args['levels'])) $args['levels'] = 5;
        $power = 5 - $args['levels'];

        $factor = pow(100, $power);
        $version1 = (int)($version1['versionnumber']/$factor);
        $version2 = (int)($version2['versionnumber']/$factor);
        if ($version1 > $version2) return 1;
        elseif ($version1 < $version2) return -1;
        else return 0;
    }

/**
 * Asserts that the Xaraya core or a module version has reached a certain level.
 *
 * @param $version string version number to check
 * @param $application string the application to check against
 * @return boolean indicating whether the application is at least version $version
 */
    public static function assert($version, $application='core')
    {
        if (empty($version)) return true;
        if ($application == 'core') {
            $version2 = xarConfigVars::get(null, 'System.Core.VersionNum');
        } else {
            try {
                $info = getBaseInfo($application);
                $version2 = $info['version'];
            } catch (Exception $e) {
                throw new ModuleBaseInfoNotFoundException($application);
            }
        }
        $result = self::compare($version1, $version2);
        return $result >= 0;
    }
}
?>