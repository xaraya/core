<?php

require_once 'creole/IdGenerator.php';

/**
 * PdoIdGenerator implimenation.
 *
 * @author    Hans Lellelid <hans@xmpl.org>
 * @version   $Revision: 1.4 $
 * @package   creole.drivers.sqlite
 */
abstract class PdoIdGeneratorCommon implements IdGenerator
{
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
        return $this->conn->getResource()->lastInsertId();
    }

}
