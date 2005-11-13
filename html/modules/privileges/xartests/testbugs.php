<?php
/**
 * A suite to add the tests to
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2005 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Privileges module
 */

/* a suite to add the tests to
 * @author Roger Keays <r.keays@ninthave.net>
*/
$tmp = new xarTestSuite('Privileges Bugzilla Bugs');


/**
 * Example test class.
 *
 * @package example
 * @author Roger Keays <r.keays@ninthave.net>
 */
class testPrivilegesBugs extends xarTestCase 
{

    /**
     * Initialize the Xaraya core.
     */
    function setup() 
    {

        /* these must point to the correct location of the core */
        chdir('../..');
        include_once 'includes/xarCore.php';
        include_once 'includes/xarLog.php';
        include_once 'includes/xarDB.php';
        include_once 'includes/xarMLS.php';
        include_once 'includes/xarVar.php';
        include_once 'includes/xarException.php';
        include_once 'includes/xarSecurity.php';
        include_once 'modules/privileges/xarprivileges.php';

        /*
         * This code is currently no good, since Xaraya relies on the user
         * agent being a browser to do most of its work.
         *| 

        /* initialize logging *|
        $systemArgs = 
                array('loggerName' => xarCore_getSystemVar('Log.LoggerName'),
                      'loggerArgs' => xarCore_getSystemVar('Log.LoggerArgs'),
                      'level' => xarCore_getSystemVar('Log.LogLevel'));
        xarLog_init($systemArgs, 0);

        /* initialize database *|
        $userName = xarCore_getSystemVar('DB.UserName');
        $password = xarCore_getSystemVar('DB.Password');
        if (xarCore_getSystemVar('DB.Encoded') == '1') {
            $userName = base64_decode($userName);
            $password  = base64_decode($password);
        }
        $systemArgs = array('userName' => $userName,
                            'password' => $password,
                            'databaseHost' => xarCore_getSystemVar('DB.Host'),
                            'databaseType' => xarCore_getSystemVar('DB.Type'),
                            'databaseName' => xarCore_getSystemVar('DB.Name'),
                            'systemTablePrefix' => xarCore_getSystemVar('DB.TablePrefix'),
                            'siteTablePrefix' => xarCore_getSystemVar('DB.TablePrefix'));
        // Connect to database
        xarDB_init($systemArgs, 0);
        xarErrorFree();
        /* end comment block */
    }
  
    /**
     * Test for Bug 1970 (Fatal php error in xarprivileges.php). The safe way
     * is to delete the child then the parent.
     *
     *      This bug occurs when
     *        1) privilege has a parent
     *        2) privilege does not have a parent 'root'
     *        3) privilege's parent is deleted
     *        4) privilege is deleted itself
     */
    function testBug1970Safe() 
    {
        /*
         * This code is currently no good, since Xaraya relies on the user
         * agent being a browser to do most of its work.
         *|
        /* 1) privilege has a parent *|/g
        xarLogMessage("Hello");
        xarRegisterPrivilege('Bug1970Parent', 'All', 'themes', 'All', 'All',
                'ACCESS_ADMIN');
        xarMakePrivilegeRoot('Bug1970Parent');

        /* 2) privilege does not have a parent 'root' *|/g
        xarRegisterPrivilege('Bug1970Child', 'All', 'themes', 'All', 'All',
                'ACCESS_ADMIN');
        xarMakePrivilegeMember('Bug1970Child', 'Bug1970Parent');
        
        /* 4) privilege is deleted itself *|/g
        $privs = new xarPrivileges();
        $priv = $privs->findPrivilege('Bug1970Child');
        $priv->remove();  /* causing fatal error *|/g

        /* 3) parent is deleted *|/g
        $priv = $privs->findPrivilege('Bug1970Parent');
        $out = $priv->remove();

        return $this->assertTrue($out, 
            "Testing bug 1970 the safe way (fatal error)");
        /* end comment block */
    } 


    /**
     * Test for Bug 1970 (Fatal php error in xarprivileges.php). The unsafe
     * way is to delete the parent then the child.
     *
     *      This bug occurs when
     *        1) privilege has a parent
     *        2) privilege does not have a parent 'root'
     *        3) privilege's parent is deleted
     *        4) privilege is deleted itself
     *
     * This can't occur through the GUI, because once you do step 3), there is
     * no way in the GUI to do step 4). It is still a problem though.
     */
    function testBug1970Unsafe() 
    {
        /*
         * This code is currently no good, since Xaraya relies on the user
         * agent being a browser to do most of its work.
         *|
        /* 1) privilege has a parent *|/g
        xarRegisterPrivilege('Bug1970Parent', 'All', 'themes', 'All', 'All',
                'ACCESS_ADMIN');
        xarMakePrivilegeRoot('Bug1970Parent');

        /* 2) privilege does not have a parent 'root' *|/g
        xarRegisterPrivilege('Bug1970Child', 'All', 'themes', 'All', 'All',
                'ACCESS_ADMIN');
        xarMakePrivilegeMember('Bug1970Child', 'Bug1970Parent');
        
        /* 3) parent is deleted *|/g
        $privs = new xarPrivileges();
        $priv = $privs->findPrivilege('Bug1970Parent');
        $priv->remove();

        /* 4) privilege is deleted itself *|/g
        $priv = $privs->findPrivilege('Bug1970Child');
        $out = $priv->remove();  /* causing fatal error *|/g

        return $this->assertTrue($out, 
            "Testing bug 1970 the unsafe way (fatal error)");
        /* end comment block */
    } 
}

/* add the tests to the suite */
$tmp->AddTestCase('testPrivilegesBugs', 'Tests for bugs submitted to bugzilla');

/* add this suite to the list */
$suites[] = $tmp;

?>
