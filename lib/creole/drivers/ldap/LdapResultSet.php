<?php
/*
 *  $Id: LdapResultSet.php,v 1.24 2006/01/17 19:44:39 hlellelid Exp $
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
 
require_once 'creole/ResultSet.php';
require_once 'creole/common/ResultSetCommon.php';

/**
 * Ldap implementation of ResultSet class.
 *
 * Ldap supports OFFSET / LIMIT natively; this means that no adjustments or checking
 * are performed.  We will assume that if the lmitSQL() operation failed that an
 * exception was thrown, and that OFFSET/LIMIT will never be emulated for Ldap.
 * 
 * @author    Sébastien Cramatte <scramatte@zensoluciones.com>
 * @version   $Revision: 69 $
 * @package   creole.drivers.ldap
 */
class LdapResultSet extends ResultSetCommon implements ResultSet {

		private $firstentry = null;

    /**
     * @see ResultSet::seek()
     */ 
    public function seek($rownum)
    {
    		/* not implemented yet */
    }
    
    /**
     * @see ResultSet::next()
     */ 
    public function next()
    {
 	    	if (!$this->entry) $this->entry = ldap_first_entry( $this->conn->getResource(), $this->result );
    	  $this->entry = ldap_next_entry($this->conn->getResource(),$this->entry);

        if (!$this->entry) {
            $errno = mysql_errno($this->conn->getResource());
            if (!$errno) {
                // We've advanced beyond end of recordset.
                $this->afterLast();
                return false;
            } else {
                throw new SQLException("Error fetching result", mysql_error($this->conn->getResource()));
            }
        }

        $this->cursorPos++;                
        return $this->entry;
    }

    /**
     * @see ResultSet::getRecordCount()
     */
    function getRecordCount()
    {
        $rows = @ldap_count_entries($this->result);
        if ($rows === null) {
            throw new SQLException("Error fetching num entries", ldap_error($this->conn->getResource()));
        }
        return (int) $rows;
    }

    /**
     * @see ResultSet::close()
     */ 
    function close()
    {        
        @ldap_free_result($this->result);
        $this->fields = array();
    }    

    public function getRow()
    {
    	
			$conn = $this->conn->getResource();
   		$dn = ldap_get_dn($conn,$this->entry);
			$result = array();
			
			$attrs = ldap_get_attributes($conn, $this->entry);
	
			for ($i=0; $i < $attrs["count"]; $i++) {
	   		$attr_name = $attrs[$i];
	   		if ($attrs[$attr_name]["count"]==1) {
	   			  $result[ $attr_name ] = $attrs[$attr_name][0];
	   		} else {
	   			 	$result[ $attr_name ] = $attrs[ $attr_name ];
	   		}
			}	

			return $result;		
		 	
    }
    
        
    /**
     * Get string version of column.
     * No rtrim() necessary for MySQL, as this happens natively.
     * @see ResultSet::getString()
     */
    public function getString($column) 
    {
    		/* not implemented yet */
    }
    
    /**
     * Returns a unix epoch timestamp based on either a TIMESTAMP or DATETIME field.
     * @param mixed $column Column name (string) or index (int) starting with 1.
     * @return string
     * @throws SQLException - If the column specified is not a valid key in current field array.
     */
    function getTimestamp($column, $format='Y-m-d H:i:s') 
    {
    		/* not implemented yet */
    }

}
