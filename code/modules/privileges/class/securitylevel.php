<?php
/**
 * Make security levels available
 *
 * @package default
 * @author Marcel van der Boom <mrb@hsdev.com>
 * @todo  this is here as replacement for what we used to have in a table, but wrapping levels are a bit high for getting to this info
 **/
final class SecurityLevel extends Object
{
    const INVALID =   -1;
    const NONE    =    0;
    const OVERVIEW = 100;
    const READ     = 200;
    const COMMENT  = 300;
    const MODERATE = 400;
    const EDIT     = 500;
    const ADD      = 600;
    const DELETE   = 700;
    const ADMIN    = 800;

    // This kinda sucks, but alas.
    private static $nameMap  = array(
        'ACCESS_INVALID'  => self::INVALID  ,
        'ACCESS_NONE'     => self::NONE     ,
        'ACCESS_OVERVIEW' => self::OVERVIEW ,
        'ACCESS_READ'     => self::READ     ,
        'ACCESS_COMMENT'  => self::COMMENT  ,
        'ACCESS_MODERATE' => self::MODERATE ,
        'ACCESS_EDIT'     => self::EDIT     ,
        'ACCESS_ADD'      => self::ADD      ,
        'ACCESS_DELETE'   => self::DELETE   ,
        'ACCESS_ADMIN'    => self::ADMIN);

    // @todo should we xarML these?, its perhaps better to move this to templates completely.
    // @todo this shouldn't be public if it is to stay here
    public static $displayMap = array(
        self::INVALID  => 'Invalid (-1)',
        self::NONE     => 'No Access (0)',
        self::OVERVIEW => 'Overview (100)',
        self::READ     => 'Read (200)',
        self::COMMENT  => 'Comment (300)',
        self::MODERATE => 'Moderate (400)',
        self::EDIT     => 'Edit (500)',
        self::ADD      => 'Add (600)',
        self::DELETE   => 'Delete (700)',
        self::ADMIN    => 'Administer (800)'
        );

    public static function get($name)
    {
        if(isset(self::$nameMap[$name])) {
            return self::$nameMap[$name];
        } else {
            return self::INVALID;
        }
    }
}
?>
