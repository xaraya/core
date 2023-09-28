<?php

/*
 *  $Id: ForeignKeyInfo.php,v 1.9 2005/08/02 14:42:36 sethr Exp $
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
 * "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
 * LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR
 * A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT
 * OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL,
 * SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT
 * LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE,
 * DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY
 * THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE
 * OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 * This software consists of voluntary contributions made by many individuals
 * and is licensed under the LGPL. For more information please see
 * <http://creole.phpdb.org>.
 */

/**
 * Represents a foreign key.
 *
 * @author    Hans Lellelid <hans@xmpl.org>
 * @version   $Revision: 1.9 $
 * @package   creole.metadata
 */
class ForeignKeyInfo
{
    private $name;
    private $references = array();

    /**
     * Additional and optional vendor specific information.
     * @var array $vendorSpecificInfo
     */
    protected $vendorSpecificInfo = array();


    public const NONE       = "";            // No "ON [ DELETE | UPDATE]" behaviour specified.
    public const NOACTION   = "NO ACTION";
    public const CASCADE    = "CASCADE";
    public const RESTRICT   = "RESTRICT";
    public const SETDEFAULT = "SET DEFAULT";
    public const SETNULL    = "SET NULL";

    /**
     * @param string $name The name of the foreign key.
     */
    public function __construct($name, $vendorInfo = array())
    {
        $this->name = $name;
        $this->vendorSpecificInfo = $vendorInfo;
    }

    /**
     * Get foreign key name.
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Adds a foreign-local mapping.
     * @param ColumnInfo $local
     * @param ColumnInfo $foreign
     */
    public function addReference(ColumnInfo $local, ColumnInfo $foreign, $onDelete = self::NONE, $onUpdate = self::NONE)
    {
        $this->references[] = array($local, $foreign, $onDelete, $onUpdate);
    }

    /**
     * Gets the local-foreign column mapping.
     * @return array<mixed> array( [0] => array([0] => local ColumnInfo object, [1] => foreign ColumnInfo object, [2] => onDelete, [3] => onUpdate) )
     */
    public function getReferences()
    {
        return $this->references;
    }

    /**
     * Get vendor specific optional information for this primary key.
     * @return array<mixed> vendorSpecificInfo[]
     */
    public function getVendorSpecificInfo()
    {
        return $this->vendorSpecificInfo;
    }

    /**
     * @return string
     */
    public function toString()
    {
        return $this->name;
    }

}
