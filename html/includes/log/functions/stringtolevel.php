<?php

    /**
     * Returns the defined integer representation of a string from the configuration.
     *
     * @param string $string   One of the priority level strings.
     * @return constant           The constant representing the $level string.
     */
    function xarLog__stringToLevel($string)
    {
        static $strings = array (
            'EMERGENCY' => XARLOG_LEVEL_EMERGENCY,
            'ALERT'     => XARLOG_LEVEL_ALERT,
            'CRITICAL'  => XARLOG_LEVEL_CRITICAL,
            'ERROR'     => XARLOG_LEVEL_ERROR,
            'WARNING'   => XARLOG_LEVEL_WARNING,
            'NOTICE'    => XARLOG_LEVEL_NOTICE,
            'INFO'      => XARLOG_LEVEL_INFO,
            'DEBUG'     => XARLOG_LEVEL_DEBUG
        );

        return $strings[$string];
    }
    
?>