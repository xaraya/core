<?php
/**
 * @package modules\base
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 * @link http://xaraya.com/index.php/release/68.html
 *
 * @author mikespub <mikespub@xaraya.com>
 */
/**
 * Include the base class
 */
sys::import('modules.base.xarproperties.textbox');

/**
 * Handle the URL property
 */
class URLProperty extends TextBoxProperty
{
    public $id         = 11;
    public $name       = 'url';
    public $desc       = 'URL';

    function __construct(ObjectDescriptor $descriptor)
    {
        parent::__construct($descriptor);
        $this->template = 'url';
    }

    function validateValue($value = null)
    {
        if (!parent::validateValue($value)) return false;

        // Make sure $value['link'] is set, has a length > 0 and does not equal simply 'http://'
        $value = trim($value);
        if (!empty($value) && $value != 'http://')  {
           //let's process futher then
           //check it is not invalid eg html tag
            if (preg_match('/[<>"]/',$value)) {
                $this->invalid = xarML('Invalid URL: #(1)', $value);
                xarLog::message($this->invalid, XARLOG_LEVEL_ERROR);
                $this->value = null;
                return false;
            } else {
              // If we have a scheme but nothing following it,
                // then consider the link empty :-)
                if (mb_eregi('^[a-z]+\:\/\/$', $value)) {
                    $this->value = '';
                } else {
                    // Do some URL validation below. Separate for better understanding
                    // Still not perfect. Add as seen fit.
                    $uri = parse_url($value);
                    if (empty($uri['scheme'])) $value = 'http://' . $value;
                    if (!filter_var($value, FILTER_VALIDATE_URL)) {
                        $this->invalid = xarML('Invalid URL: #(1)', $value);
                        xarLog::message($this->invalid, XARLOG_LEVEL_ERROR);
                        $this->value = null;
                        return false;
                    } 
                    $this->value = $value;
                }

            } //end checks for other schemes
        }
        return true;
    }
}
?>