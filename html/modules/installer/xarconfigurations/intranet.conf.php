<?php
/**
 * File: community.conf.php
 *
 * Configuration file for a community site
 *
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2003 by the Xaraya Development Team.
 * @link http://www.xaraya.com
 *
 * @subpackage installer
 * @author Marc Lutolf
 */

 $configuration_name = 'Intranet';

    $options = array(
    array(
        'option' => 'true',
        'comment' => 'Registered users have read access to the non-core modules of the site.')
    );
 $configuration_options = $options;


/**
 * Load the configuration
 *
 * @access public
 * @return boolean
 */
function intranet_configuration_load($args)
{
    return true;
}
?>
