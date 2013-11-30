<?php
/**
 * Update the configuration parameters
 *
 * @package modules\blocks
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 * @link http://xaraya.com/index.php/release/13.html
 */

/**
 * Update the configuration parameters of the module based on data from the modification form
 *
 * @author Jon Haworth
 * @author jsb <jsb@xaraya.com>
 * 
 * @access public
 * @param array    $args array of optional parameters<br/>
 * @param string   $args['starttime'] (seconds or hh:mm:ss)<br/>
 * @param string   $args['direction'] (from or to)
 * @return string $convertedtime (hh:mm:ss or seconds)
 */
function blocks_userapi_convertseconds(Array $args=array())
{
    /**
     * Pending 
     * @todo maybe add support for days?
     */
    extract($args);

    $convertedtime = '';

    // if the value is set to zero, we can leave it that way
    if ($starttime === 0) {
        return $starttime;
    }

    switch($direction) {
        case 'from':
            if (!empty($countdays)) {
                // convert to days
                $days = intval(intval($starttime) / (3600 * 24));
                $convertedtime .= $days . ':';
                // subtract days from starttime
                $starttime -= ($days * (3600 * 24));
            }
            // convert time to hours
            $hours = intval(intval($starttime) / 3600);
            // add leading 0
            $convertedtime .= str_pad($hours, 2, '0', STR_PAD_LEFT). ':';
            // get the minutes
            $minutes = intval(($starttime / 60) % 60);
            // then add to $hms (with a leading 0 if needed)
            $convertedtime .= str_pad($minutes, 2, '0', STR_PAD_LEFT). ':';
            // get the seconds
            $seconds = intval($starttime % 60);
            // add to $hms, again with a leading 0 if needed
            $convertedtime .= str_pad($seconds, 2, '0', STR_PAD_LEFT);
            break;
        case 'to':
            // break apart the time elements
            $elements = explode(':', $starttime);
            if (!empty($countdays)) {
                // make sure it's all there
                $allelements = array_pad($elements, 4, 0);
                // calculate the total seconds
                $convertedtime = (($allelements[0] * (3600 * 24)) + ($allelements[1] * 3600) + ($allelements[2] * 60) + $allelements[3]);
            } else {
                // make sure it's all there
                $allelements = array_pad($elements, 3, 0);
                // calculate the total seconds
                $convertedtime = (($allelements[0] * 3600) + ($allelements[1] * 60) + $allelements[2]);
            }
            // make sure we're sending back an integer
            settype($convertedtime, 'integer');
            break;
    }

    return $convertedtime;
}

?>
