<?php
/**
 * @package modules
 * @copyright (C) 2002-2006 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage base
 * @link http://xaraya.com/index.php/release/68.html
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
        if (!isset($value)) {
            $value = $this->value;
        }
        // Make sure $value['link'] is set, has a length > 0 and does not equal simply 'http://'
        $value = trim($value);
        if (!empty($value) && $value != 'http://')  {
           //let's process futher then
           //check it is not invalid eg html tag
            if (preg_match('/[<>"]/',$value)) {
                $this->invalid = xarML('URL');
                $this->value = '';
                return false;
            } else {
              // If we have a scheme but nothing following it,
                // then consider the link empty :-)
                if (eregi('^[a-z]+\:\/\/$', $value)) {
                    $this->value = '';
                } else {
                    // Do some URL validation below. Separate for better understanding
                    // Still not perfect. Add as seen fit.
                    $uri = parse_url($value);
                    if (empty($uri['scheme']) && empty($uri['host']) && empty($uri['path'])) {
                        $this->invalid = xarML('URL');
                        $this->value = '';
                        return false;
                    } elseif (empty($uri['scheme'])) {
                        $this->value = 'http://' . $value;
                    } else {
                        // it has at least a scheme (http/ftp/etc) and a host (domain.tld)
                        $this->value = $value;
                    }
                }

            } //end checks for other schemes
        }
        return true;
    }
}
?>
