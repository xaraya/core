<?php

require_once 'creole/IdGenerator.php';

/**
 * MSSQL IdGenerator implimenation.
 *
 * @author    Hans Lellelid <hans@xmpl.org>
 * @version   $Revision: 1.6 $
 * @package   creole.drivers.mssql
 */
class MSSQLIdGenerator implements IdGenerator {
    
    /** Connection object that instantiated this class */
    private $conn;

    /**
     * Creates a new IdGenerator class, saves passed connection for use
     * later by getId() method.
     * @param Connection $conn
     */
    public function __construct(Connection $conn)
    {
        $this->conn = $conn;
    }
    
    /**
     * @see IdGenerator::isBeforeInsert()
     */
    public function isBeforeInsert()
    {
        return false;
    }    
    
    /**
     * @see IdGenerator::isAfterInsert()
     */
    public function isAfterInsert()
    {
        return true;
    }
        
    /**
     * @see IdGenerator::getIdMethod()
     */
    public function getIdMethod()
    {
        return self::AUTOINCREMENT;
    }
    
    /**
     * @see IdGenerator::getId()
     */
    public function getId($unused = null)
    {
        // XARAYA modification
        //$rs = $this->conn->executeQuery("select @@identity", ResultSet::FETCHMODE_NUM);
        $rs = $this->conn->executeQuery("select IDENT_CURRENT('$unused')", ResultSet::FETCHMODE_NUM);
        // END XARAYA modification
        $rs->next();
        return $rs->getInt(1);        
    }
    
}

