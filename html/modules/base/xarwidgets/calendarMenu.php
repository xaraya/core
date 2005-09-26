 /**
  * @package Xaraya eXtensible Management System
  * @copyright (C) 2005 The Digital Development Foundation
  * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
  * @link http://www.xaraya.com
  *
  * @subpackage Base module
  */
  
//could this be a first widget?

// added by AV to reuse the code
function news_adminapi_buildCalendarMenu($args) //$automated, &$year, &$day, &$month, &$hour, &$min)
{
    $out = '';
    $out .= _NEWS_NOWIS.': '.strftime(_DATETIMELONG).'<br />'
        .'<br />'
        ._NEWS_HOUR.": <select name=\"hour\" class=\"xar-text\">"
    ;
    $cur = intval(date('H', $args['datestamp']));
    for ($hour = 0; $hour <= 23; $hour++)
    {
        $num = sprintf('%02d', $hour);
        $out .= '<option value="'.$num.'"'.(($hour == $cur)?' selected="selected"':'').'>'.xarVarPrepForDisplay($num)."</option>\n";
    }
    $out .= '</select>&nbsp;'
        .": <select name=\"min\" class=\"xar-text\">"
    ;
    $cur = intval(date('i', $args['datestamp']));
    for ($minute = 0; $minute <= 59; $minute++)
    {
        $num = sprintf('%02d', $minute);
        $out .= '<option value="'.$num.'"'.(($minute == $cur)?' selected="selected"':'').'>'.xarVarPrepForDisplay($num)."</option>\n";
    }
    $out .= '</select>&nbsp;&nbsp;'
        ._NEWS_DAY.": <select name=\"day\" class=\"xar-text\">"
    ;
    $cur = trim(date('j', $args['datestamp']));
    for ($day = 1; $day <= 31; $day++)
    {
        $num = sprintf('%02d', $day);
        $out .= '<option value="'.$num.'"'.(($day == $cur)?' selected="selected"':'').'>'.xarVarPrepForDisplay($num)."</option>\n";
    }
    $out .= "</select>&nbsp;&nbsp;"
        ._NEWS_MONTH.": <select name=\"month\" class=\"xar-text\">"
    ;
    $cur = trim(date('n', $args['datestamp']));
    for ($month = 1; $month <= 31; $month++)
    {
        $num = sprintf('%02d', $month);
        $out .= '<option value="'.$num.'"'.(($month == $cur)?' selected="selected"':'').'>'.xarVarPrepForDisplay($num)."</option>\n";
    }
    $out .= '</select>&nbsp;&nbsp;'
        ._NEWS_YEAR.': <input type="text" name="year" id="year" value="'.date('Y', $args['datestamp']).'" size="5" maxlength="4" /><br />'
    ;

    return $out;
}
?>