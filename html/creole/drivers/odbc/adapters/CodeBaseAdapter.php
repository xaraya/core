<?php
/*
 *  $Id: CodeBaseAdapter.php,v 1.1 2004/07/27 23:08:30 hlellelid Exp $
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

require_once 'creole/drivers/odbc/adapters/ODBCAdapter.php';

/**
 * CodeBase driver-specific behavior.
 *
 * @author    Dave Lawson <dlawson@masterytech.com>
 * @version   $Revision: 1.1 $
 * @package   creole.drivers.odbc
 */
class CodeBaseAdapter extends ODBCAdapter
{
    /**
     * @see ODBCAdapter::createResultSet()
     */
    public function preservesColumnCase()
    {
        return false;
    }

    /**
     * @see ODBCAdapter::createResultSet()
     */
    public function createResultSet($conn, $odbcresult, $fetchmode)
    {
        require_once 'creole/drivers/odbc/ODBCCachedResultSet.php';
        return new ODBCCachedResultSet($conn, $odbcresult, $fetchmode, true);
    }

}

?>