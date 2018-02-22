<?php
/**
 * Include the base class
 */
sys::import('modules.base.xarproperties.url');

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
 * This property displays an icon for a URL; if a link is provided then the icon is shown as a link to the URL
 */
class URLIconProperty extends URLProperty
{
    public $id         = 27;
    public $name       = 'urlicon';
    public $desc       = 'URL Icon';

    public $initialization_icon_url = 'http://';

    function __construct(ObjectDescriptor $descriptor)
    {
        parent::__construct($descriptor);
        $this->template = 'urlicon';
    }

    public function showOutput(Array $data = array())
    {
        if (empty($data['value'])) $data['value'] = $this->value;
        if (empty($data['link'])) $data['link'] = '';

        if (!empty($data['value']) && $data['value'] != 'http://' && empty($data['link'])) {
            $data['link'] = xarVarPrepForDisplay($data['value']);
        }
        if (empty($data['icon'])) {
            /* We don't have a validated icon to display, use favicon */
            /* FIXME: getfavicon needs to send back nothing if the favicon doens't exist. */
            $data['icon'] = xarMod::apiFunc('base',
                                          'user',
                                          'getfavicon',
                                          array('url' => $data['value']));
            if (empty($data['icon'])) {
                /* we'll have to use the default system icon */
                $data['icon'] = xarTpl::getImage('icons/go-home.png','theme');
            }
        }
        return parent::showOutput($data);
    }
}
?>