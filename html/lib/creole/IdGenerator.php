<?php

/**
 * Interface for classes that provide functionality to get SEQUENCE or AUTO-INCREMENT ids from the database.
 *
 * @author    Hans Lellelid <hans@xmpl.org>
 * @version   $Revision: 1.3 $
 * @package   creole
 */
interface IdGenerator
{
    /** SEQUENCE id generator type */
    public const SEQUENCE = 1;

    /** AUTO INCREMENT id generator type */
    public const AUTOINCREMENT = 2;

    /**
     * Convenience method that returns TRUE if id is generated
     * before an INSERT statement.  This is the same as checking
     * whether the generator type is SEQUENCE.
     * @return boolean TRUE if gen id method is SEQUENCE
     * @see getIdMethod()
     */
    public function isBeforeInsert();

    /**
     * Convenience method that returns TRUE if id is generated
     * after an INSERT statement.  This is the same as checking
     * whether the generator type is AUTOINCREMENT.
     * @return boolean TRUE if gen id method is AUTOINCREMENT
     * @see getIdMethod()
     */
    public function isAfterInsert();

    /**
     * Get the preferred type / style for generating ids for RDBMS.
     * @return int SEQUENCE or AUTOINCREMENT
     */
    public function getIdMethod();

    /**
     * Get the autoincrement or sequence id given the current connection
     * and any additional needed info (e.g. sequence name for sequences).
     * <p>
     * Note: if you take advantage of the fact that $keyInfo may not be specified
     * you should make sure that your code is setup in such a way that it will
     * be portable if you change from an RDBMS that uses AUTOINCREMENT to one that
     * uses SEQUENCE (i.e. in which case you would need to specify sequence name).
     *
     * @param mixed $keyInfo Any additional information (e.g. sequence name) needed to fetch the id.
     * @return int The last id / next id.
     */
    public function getId($keyInfo = null);

    // XARAYA MODIFICATION
    /**
     * Get the last ID generated for a certain table. We implement this
     * because the getId method is ambiguous. It returns the next ID for
     * sequence based system and the last ID for auto increment based backends.
     * As there is controversy on what the proper method is for dealing with
     * id generating columns we just provide two explicit method and leave developer
     * a choice on how to deal with them.
     *
     * The two methods will do at least an unambiguous thing, deliver the next/last ID
     * if it can be determined, and NULL otherwise. Note that calling getNextID advances
     * the sequence.
     */
    public function getLastId($tableName);
    public function getNextId($tableName);
    // END XARAYA MODIFICATION

}
