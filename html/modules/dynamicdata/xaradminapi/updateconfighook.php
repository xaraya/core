<?php

/**
 * update configuration for a module - hook for ('module','updateconfig','API')
 * Needs $extrainfo['dd_*'] from arguments, or 'dd_*' from input
 *
 * @param $args['objectid'] ID of the object
 * @param $args['extrainfo'] extra information
 * @returns bool
 * @return true on success, false on failure
 * @raise BAD_PARAM, NO_PERMISSION, DATABASE_ERROR
 */
function dynamicdata_adminapi_updateconfighook($args)
{
    if (!isset($args['extrainfo'])) {
        $args['extrainfo'] = array();
    }
    // Return the extra info
    return $args['extrainfo'];

    /*
     * currently NOT used (we're going through the 'normal' updateconfig for now)
     */

}

?>