<?php
/**
 * Include the base class
 */
 sys::import('modules.base.xarproperties.textbox');
/**
 * @package modules\base
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://xaraya.info/index.php/release/68.html
 *
 * @author mikespub <mikespub@xaraya.com>
 */

/**
 * This property displays a title for a URL; if a link is provided then the icon is shown as a link to the URL
 */
class URLTitleProperty extends TextBoxProperty
{
    public $id         = 41;
    public $name       = 'urltitle';
    public $desc       = 'URL + Title';

    function __construct(ObjectDescriptor $descriptor)
    {
        parent::__construct($descriptor);
        $this->tplmodule = 'base';
        $this->template  = 'urltitle';
    }

	/**
	 * Validate the value of a url title textbox
	 *
	 * @return bool Returns true if the value passes all validation checks; otherwise returns false.
	 */
    public function validateValue($value = null)
    {
        if (!parent::validateValue($value)) return false;

        if (!empty($value)) {
            if (is_array($value)) {

                if (isset($value['title'])) {
                    $title = $value['title'];
                } else {
                    $title = '';
                }

                if (isset($value['link'])) {
                    $link = $value['link'];
                } else {
                    $link = '';
                }
                // Make sure $value['title'] is set and has a length > 0
                if (strlen(trim($title))) {
                    $title = $value['title'];
                } else {
                    $title = '';
                }

                // Make sure $value['link'] is set, has a length > 0 and does not equal simply 'http://'
                if (strlen(trim($link)) && trim($link) != 'http://') {
                        $link = $value['link'];
                } else {
                    // If we have a scheme but nothing following it,
                    // then consider the link empty :-)
                    if (preg_match('/^[a-z]+\:\/\/$/i', trim($link))) {
                        $link = '';
                    } else {

                        // Do some URL validation below - make sure the url
                        // has at least a scheme (http/ftp/etc) and a host (domain.tld)
                        $uri = parse_url($value['link']);

                        if ( (!isset($uri['scheme']) || empty($uri['scheme'])) ||
                            (!isset($uri['host']) || empty($uri['host']))) {
                                $this->invalid = xarML('URL');
                                xarLog::message($this->invalid, xarLog::LEVEL_ERROR);
                                $this->value = null;
                                return false;
                        }
                    }
                }
                $value = array('link' => $link, 'title' => $title);
                $this->value = serialize($value);
            } else {
            // TODO: do we need to check the serialized content here ?
                $this->value = $value;
            }
        } else {
            $this->value = '';
        }
        return true;
    }
	
	/**
	 * Display a textbox for input
	 * 
	 * @param array<string, mixed> $data An array of input parameters
	 * @return string     HTML markup to display the property for input on a web page
	 */
    public function showInput(Array $data = array())
    {
        if (!isset($data['value'])) {
            $value = $this->value;
        } else {
            $value = $data['value'];
        }

        // extract the link and title information
        if (empty($value)) {
        } elseif (is_array($value)) {
            if (isset($value['link'])) {
                $link = $value['link'];
            }
            if (isset($value['title'])) {
                $title = $value['title'];
            }
        } elseif (is_string($value) && substr($value,0,2) == 'a:') {
            $newval = unserialize($value);
            if (isset($newval['link'])) {
                $link = $newval['link'];
            }
            if (isset($newval['title'])) {
                $title = $newval['title'];
            }
        }
        if (empty($link)) {
            $link = 'http://';
        }
        if (empty($title)) {
            $title = '';
        }

        $data['title']    = xarVar::prepForDisplay($title);
        $data['value']    = isset($value) ? xarVar::prepForDisplay($value) : xarVar::prepForDisplay($this->value);
        $data['link']     = xarVar::prepForDisplay($link);

        return parent::showInput($data);
    }

	/**
	 * Display a textbox for output
	 * 
	 * @param array<string, mixed> $data An array of input parameters
	 * @return string     HTML markup to display the property for output on a web page
	 */	
    public function showOutput(Array $data = array())
    {
        extract($data);
        if (!isset($value)) $value = $this->value;

        if (empty($value)) $returndata= '';

        if (is_array($value)) {
            if (isset($value['link'])) {
                $link = $value['link'];
            }
            if (isset($value['title'])) {
                $title = $value['title'];
            }
        } elseif (is_string($value) && substr($value,0,2) == 'a:') {
            $newval = unserialize($value);
            if (isset($newval['link'])) {
                $link = $newval['link'];
            }
            if (isset($newval['title'])) {
                $title = $newval['title'];
            }
        }

        if (!empty($title)) $title = xarVar::prepForDisplay($title);

        $url_parts = parse_url($link);
        if (!isset($url_parts['host'])) {
            $truecurrenturl = xarServer::getCurrentURL(array(), false);
            $urldata = xarMod::apiFunc('roles','user','parseuserhome',array('url'=>$link,'truecurrenturl'=>$truecurrenturl));
            $link = $urldata['redirecturl'];
        }

        $data['value']   = $this->value;
        $data['link']    = (!empty($link) && $link != 'http://') ? $link : '';
        $data['title']   = (!empty($title)) ? $title : '';

        return parent::showOutput($data);
    }
}
