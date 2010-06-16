<?php
    // @TODO: see validations note in constructor
/**
 * MenuBlock class, default parent class for menu blocks
**/
sys::import('xaraya.structures.containers.blocks.basicblock');

class MenuBlock extends BasicBlock implements iBlock
{
    public $module          = 'BlockModule';  // Module your child class belongs to
    public $text_type       = 'Menu Block';  // Block name
    public $text_type_long  = 'Parent class for menu blocks'; // Block description

    // store current request info as static properties
    public static $thismodname;
    public static $thismodtype;
    public static $thisfuncname;
    public static $currenturl;
    public static $truecurrenturl;

    public function display(Array $args=array())
    {
        self::setRequestInfo();
        $data = parent::display($args);
        return $data;
    }

    public function getMenuLinks(Array $args=array())
    {
        self::setRequestInfo();
        if (empty($args['modname'])) $args['modname'] = self::$thismodname;
        if (empty($args['modtype'])) $args['modtype'] = self::$thismodtype;
        //if (empty($args['funcname'])) $args['funcname'] = self::$thisfuncname;

        $menulinks = xarMod::apiFunc('base', 'admin', 'loadmenuarray', $args);

        if (!empty($menulinks)) {
            foreach ($menulinks as $k => $v) {
                // sec check
                if (!empty($v['mask']) && !xarSecurityCheck($v['mask'], 0)) {
                    unset($menulinks[$k]);
                    continue;
                }
                // active link?
                if (!empty($v['active']) && is_array($v['active']) && in_array(self::$thisfuncname, $v['active']) ||
                    $v['url'] == self::$currenturl) {
                    $menulinks[$k]['isactive'] = 1;
                } else {
                    $menulinks[$k]['isactive'] = 0;
                }
                $menulinks[$k]['url'] = $v['url'] == self::$currenturl ? '' : $v['url'];
            }
        }

        return $menulinks;
    }

    public function setRequestInfo()
    {
        if (!isset(self::$thismodname) || !isset(self::$thismodtype) || !isset(self::$thisfuncname)) {
            // set current request info properties
            list(self::$thismodname, self::$thismodtype, self::$thisfuncname) = xarController::$request->getInfo();
        }
        if (!isset(self::$currenturl))
            self::$currenturl = xarServer::getCurrentURL();
        if (!isset(self::$truecurrenturl))
            self::$truecurrenturl = xarServer::getCurrentURL(array(), false);
    }

}
?>