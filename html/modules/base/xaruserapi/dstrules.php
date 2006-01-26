<?php
/**
 * Simplified DST rules
 *
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2005 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Base module
 */
/**
 * Simplified DST rules (based on modules/timezone/tzdata.php)
 * (cfr. modules/timezone/xaradmin/regenerate.php)
 *
 * @author mikespub
 * @param $args['rule'] string DST rule we're looking for (default all)
 * @param $args['time'] integer timestamp for the period we're interested in (unsupported)
 * @author the Base module development team
 * @return array containing the different DST rules
 */
function base_userapi_dstrules($args)
{
/*
    if (isset($time) && xarModIsAvailable('timezone')) {
        // get time-dependent DST rules from the timezone module
        ...
        return $Rules;
    }
*/

    $Rules = array();

    // Rule    NAME    FROM    TO    TYPE    IN    ON    AT    SAVE    LETTER

    $Rules['AN'] = array(
        array('1996', 'max', '-', 'Mar', 'lastSun', '2:00s', '0', '-'),
        array('2001', 'max', '-', 'Oct', 'lastSun', '2:00s', '1:00', '-'),
    );
    $Rules['AQ'] = array();
    $Rules['AS'] = array(
        array('1987', 'max', '-', 'Oct', 'lastSun', '2:00s', '1:00', '-'),
        array('1995', 'max', '-', 'Mar', 'lastSun', '2:00s', '0', '-'),
    );
    $Rules['AT'] = array(
        array('1991', 'max', '-', 'Mar', 'lastSun', '2:00s', '0', '-'),
        array('2001', 'max', '-', 'Oct', 'Sun>=1', '2:00s', '1:00', '-'),
    );
    $Rules['AV'] = array(
        array('1995', 'max', '-', 'Mar', 'lastSun', '2:00s', '0', '-'),
        array('2001', 'max', '-', 'Oct', 'lastSun', '2:00s', '1:00', '-'),
    );
    $Rules['Aus'] = array();
    $Rules['Azer'] = array(
        array('1997', 'max', '-', 'Mar', 'lastSun', '1:00', '1:00', 'S'),
        array('1997', 'max', '-', 'Oct', 'lastSun', '1:00', '0', '-'),
    );
    $Rules['Bahamas'] = array(
        array('1964', 'max', '-', 'Oct', 'lastSun', '2:00', '0', 'S'),
        array('1987', 'max', '-', 'Apr', 'Sun>=1', '2:00', '1:00', 'D'),
    );
    $Rules['Barb'] = array();
    $Rules['Belize'] = array();
    $Rules['Brazil'] = array(
        array('2001', 'max', '-', 'Feb', 'Sun>=15', '0:00', '0', '-'),
        array('2003', 'max', '-', 'Oct', 'Sun>=15', '0:00', '1:00', 'S'),
    );
    // unused in timezones.php
    $Rules['C-Eur'] = array(
        array('1981', 'max', '-', 'Mar', 'lastSun', '2:00s', '1:00', 'S'),
        array('1996', 'max', '-', 'Oct', 'lastSun', '2:00s', '0', '-'),
    );
    $Rules['CO'] = array();
    $Rules['CR'] = array();
    $Rules['Canada'] = array(
        array('1974', 'max', '-', 'Oct', 'lastSun', '2:00', '0', 'S'),
        array('1987', 'max', '-', 'Apr', 'Sun>=1', '2:00', '1:00', 'D'),
    );
    $Rules['Chatham'] = array(
        array('1990', 'max', '-', 'Mar', 'Sun>=15', '2:45s', '0', 'S'),
    );
    $Rules['Chile'] = array(
        array('1999', 'max', '-', 'Oct', 'Sun>=9', '4:00u', '1:00', 'S'),
        array('2000', 'max', '-', 'Mar', 'Sun>=9', '3:00u', '0', '-'),
    );
    $Rules['ChileAQ'] = array(
        array('1999', 'max', '-', 'Oct', 'Sun>=9', '0:00', '1:00', 'S'),
        array('2000', 'max', '-', 'Mar', 'Sun>=9', '0:00', '0', '-'),
    );
    $Rules['Cook'] = array();
    $Rules['Cuba'] = array(
        array('1998', 'max', '-', 'Oct', 'lastSun', '0:00s', '0', 'S'),
        array('2000', 'max', '-', 'Apr', 'Sun>=1', '0:00s', '1:00', 'D'),
    );
    // unused in timezones.php
    $Rules['E-Eur'] = array(
        array('1981', 'max', '-', 'Mar', 'lastSun', '0:00', '1:00', 'S'),
        array('1996', 'max', '-', 'Oct', 'lastSun', '0:00', '0', '-'),
    );
    $Rules['E-EurAsia'] = array(
        array('1981', 'max', '-', 'Mar', 'lastSun', '0:00', '1:00', 'S'),
        array('1996', 'max', '-', 'Oct', 'lastSun', '0:00', '0', '-'),
    );
    $Rules['EU'] = array(
        array('1981', 'max', '-', 'Mar', 'lastSun', '1:00u', '1:00', 'S'),
        array('1996', 'max', '-', 'Oct', 'lastSun', '1:00u', '0', '-'),
    );
    $Rules['EUAsia'] = array(
        array('1981', 'max', '-', 'Mar', 'lastSun', '1:00u', '1:00', 'S'),
        array('1996', 'max', '-', 'Oct', 'lastSun', '1:00u', '0', '-'),
    );
    $Rules['Edm'] = array(
        array('1972', 'max', '-', 'Oct', 'lastSun', '2:00', '0', 'S'),
        array('1987', 'max', '-', 'Apr', 'Sun>=1', '2:00', '1:00', 'D'),
    );
    $Rules['Egypt'] = array(
        array('1995', 'max', '-', 'Apr', 'lastFri', '0:00s', '1:00', 'S'),
        array('1995', 'max', '-', 'Sep', 'lastThu', '23:00s', '0', '-'),
    );
    $Rules['Falk'] = array(
        array('2001', 'max', '-', 'Apr', 'Sun>=15', '2:00', '0', '-'),
        array('2001', 'max', '-', 'Sep', 'Sun>=1', '2:00', '1:00', 'S'),
    );
    $Rules['Fiji'] = array();
    $Rules['Ghana'] = array();
    $Rules['Guat'] = array();
    $Rules['HK'] = array();
    $Rules['Haiti'] = array();
    $Rules['Holiday'] = array();
    $Rules['Iran'] = array();
    $Rules['Iraq'] = array(
        array('1991', 'max', '-', 'Apr', '1', '3:00s', '1:00', 'D'),
        array('1991', 'max', '-', 'Oct', '1', '3:00s', '0', 'S'),
    );
    $Rules['Jordan'] = array(
        array('1999', 'max', '-', 'Sep', 'lastThu', '0:00s', '0', '-'),
        array('2000', 'max', '-', 'Mar', 'lastThu', '0:00s', '1:00', 'S'),
    );
    $Rules['Kirgiz'] = array(
        array('1997', 'max', '-', 'Mar', 'lastSun', '2:30', '1:00', 'S'),
        array('1997', 'max', '-', 'Oct', 'lastSun', '2:30', '0', '-'),
    );
    $Rules['LH'] = array(
        array('1996', 'max', '-', 'Mar', 'lastSun', '2:00', '0', '-'),
        array('2001', 'max', '-', 'Oct', 'lastSun', '2:00', '0:30', '-'),
    );
    $Rules['Lebanon'] = array(
        array('1993', 'max', '-', 'Mar', 'lastSun', '0:00', '1:00', 'S'),
        array('1999', 'max', '-', 'Oct', 'lastSun', '0:00', '0', '-'),
    );
    $Rules['Mexico'] = array(
        array('2002', 'max', '-', 'Apr', 'Sun>=1', '2:00', '1:00', 'D'),
        array('2002', 'max', '-', 'Oct', 'lastSun', '2:00', '0', 'S'),
    );
    $Rules['Mongol'] = array();
    $Rules['NC'] = array();
    $Rules['NT_YK'] = array(
        array('1980', 'max', '-', 'Oct', 'lastSun', '2:00', '0', 'S'),
        array('1987', 'max', '-', 'Apr', 'Sun>=1', '2:00', '1:00', 'D'),
    );
    $Rules['NZ'] = array(
        array('1990', 'max', '-', 'Mar', 'Sun>=15', '2:00s', '0', 'S'),
    );
    $Rules['NZAQ'] = array(
        array('1990', 'max', '-', 'Oct', 'Sun>=1', '2:00s', '1:00', 'D'),
        array('1990', 'max', '-', 'Mar', 'Sun>=15', '2:00s', '0', 'S'),
    );
    $Rules['Namibia'] = array(
        array('1994', 'max', '-', 'Sep', 'Sun>=1', '2:00', '1:00', 'S'),
        array('1995', 'max', '-', 'Apr', 'Sun>=1', '2:00', '0', '-'),
    );
    $Rules['PRC'] = array();
    $Rules['Pakistan'] = array();
    $Rules['Palestine'] = array(
        array('1999', 'max', '-', 'Apr', 'Fri>=15', '0:00', '1:00', 'S'),
        array('1999', 'max', '-', 'Oct', 'Fri>=15', '0:00', '0', '-'),
    );
    $Rules['Para'] = array(
        array('2002', 'max', '-', 'Apr', 'Sun>=1', '0:00', '0', '-'),
        array('2002', 'max', '-', 'Sep', 'Sun>=1', '0:00', '1:00', 'S'),
    );
    $Rules['Peru'] = array();
    $Rules['Phil'] = array();
    $Rules['ROK'] = array();
    // unused in timezones.php
    $Rules['RussAQ'] = array(
        array('1993', 'max', '-', 'Mar', 'lastSun', '2:00s', '1:00', 'S'),
        array('1996', 'max', '-', 'Oct', 'lastSun', '2:00s', '0', '-'),
    );
    $Rules['Russia'] = array(
        array('1993', 'max', '-', 'Mar', 'lastSun', '2:00s', '1:00', 'S'),
        array('1996', 'max', '-', 'Oct', 'lastSun', '2:00s', '0', '-'),
    );
    $Rules['RussiaAsia'] = array(
        array('1993', 'max', '-', 'Mar', 'lastSun', '2:00s', '1:00', 'S'),
        array('1996', 'max', '-', 'Oct', 'lastSun', '2:00s', '0', '-'),
    );
    $Rules['SA'] = array();
    $Rules['SL'] = array();
    $Rules['Salv'] = array();
    $Rules['StJohns'] = array(
        array('1987', 'max', '-', 'Oct', 'lastSun', '0:01', '0', 'S'),
        array('1989', 'max', '-', 'Apr', 'Sun>=1', '0:01', '1:00', 'D'),
    );
    $Rules['Syria'] = array(
        array('1994', 'max', '-', 'Oct', '1', '0:00', '0', '-'),
        array('1999', 'max', '-', 'Apr', '1', '0:00', '1:00', 'S'),
    );
    $Rules['TC'] = array(
        array('1979', 'max', '-', 'Oct', 'lastSun', '0:00', '0', 'S'),
        array('1987', 'max', '-', 'Apr', 'Sun>=1', '0:00', '1:00', 'D'),
    );
    $Rules['Taiwan'] = array();
    $Rules['Thule'] = array(
        array('1993', 'max', '-', 'Apr', 'Sun>=1', '2:00', '1:00', 'D'),
        array('1993', 'max', '-', 'Oct', 'lastSun', '2:00', '0', 'S'),
    );
    $Rules['Tonga'] = array();
    $Rules['Tunisia'] = array();
    $Rules['US'] = array(
        array('1967', 'max', '-', 'Oct', 'lastSun', '2:00', '0', 'S'),
        array('1987', 'max', '-', 'Apr', 'Sun>=1', '2:00', '1:00', 'D'),
    );
    $Rules['Uruguay'] = array();
    $Rules['Vanc'] = array(
        array('1962', 'max', '-', 'Oct', 'lastSun', '2:00', '0', 'S'),
        array('1987', 'max', '-', 'Apr', 'Sun>=1', '2:00', '1:00', 'D'),
    );
    $Rules['Vanuatu'] = array();
    // unused in timezones.php
    $Rules['W-Eur'] = array(
        array('1981', 'max', '-', 'Mar', 'lastSun', '1:00s', '1:00', 'S'),
        array('1996', 'max', '-', 'Oct', 'lastSun', '1:00s', '0', '-'),
    );
    $Rules['Winn'] = array(
        array('1987', 'max', '-', 'Apr', 'Sun>=1', '2:00', '1:00', 'D'),
        array('1987', 'max', '-', 'Oct', 'lastSun', '2:00s', '0', 'S'),
    );
    $Rules['Zion'] = array(
        array('2005', 'max', '-', 'Apr', '1', '1:00', '1:00', 'D'),
        array('2005', 'max', '-', 'Oct', '1', '1:00', '0', 'S'),
    );

    if (isset($args['rule'])) {
        if (!empty($args['rule']) && isset($Rules[$args['rule']])) {
            return $Rules[$args['rule']];
        } else {
            return array();
        }
    } else {
        return $Rules;
    }

}
?>