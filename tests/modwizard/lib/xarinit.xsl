<!--

    XARAYA MODULE WIZARD

    COPYRIGHT:      Michael Jansen
    CONTACT:        xaraya-module-wizard@schneelocke.de
    LICENSE:        GPL

-->
<xsl:stylesheet version="1.0"
                xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
                xmlns="http://www.w3.org/TR/xhtml1/strict">

<!--

    xarinit.xsl
    ===========

-->

<!-- ENTRY POINT    print out progress and call module template -->
<xsl:template match="/" mode="xarinit" xml:space="default">
    generating xarinit.php ... <xsl:apply-templates mode="xarinit" select="xaraya_module" /> ... finished
</xsl:template>



<!-- MODULE POINT

     Create a new file called xarinit.php.

-->
<xsl:template match="xaraya_module" mode="xarinit">
<xsl:document href="{$output}/xarinit.php" format="text" omit-xml-declaration="yes" ><xsl:processing-instruction name="php">

    <!-- call template for file header -->
    <xsl:call-template name="xaraya_standard_php_file_header" select=".">
        <xsl:with-param name="filename">xarinit.php</xsl:with-param>
    </xsl:call-template>

    <!-- call template for module_init() function -->
    <xsl:apply-templates mode="xarinit_init" select="." />

    <!-- call template for module_delete() function -->
    <xsl:apply-templates mode="xarinit_delete" select="." />

    <!-- call template for module_xarupgrade() function -->
    <xsl:apply-templates mode="xarinit_upgrade" select="." />

    <!-- call template for file footer -->
    <xsl:call-template name="xaraya_standard_php_file_footer" select="." />

</xsl:processing-instruction></xsl:document>
</xsl:template>



<!-- =========================================================================

     CREATE INSTANCES AND MASKS FOR MODULE ACCESS

-->
<xsl:template mode="xarinit_init_security" match="xaraya_module">
    <xsl:variable name="module_prefix" select="registry/name" />
    // for module access
    xarRegisterMask( 'View<xsl:value-of select="$module_prefix" />' ,'All' ,'<xsl:value-of select="$module_prefix" />' ,'All' ,'All', ACCESS_OVERVIEW );
    xarRegisterMask( 'Edit<xsl:value-of select="$module_prefix" />' ,'All' ,'<xsl:value-of select="$module_prefix" />' ,'All' ,'All', ACCESS_EDIT );
    xarRegisterMask( 'Add<xsl:value-of select="$module_prefix" />' ,'All' ,'<xsl:value-of select="$module_prefix" />' ,'All' ,'All', ACCESS_ADD );
    xarRegisterMask( 'Admin<xsl:value-of select="$module_prefix" />' ,'All' ,'<xsl:value-of select="$module_prefix" />' ,'All' ,'All', ACCESS_ADMIN );

</xsl:template>


<!-- =========================================================================

    MATCH: xaraya_module                    MODE: xarinit_init_modvars

    Register the module variables configured by the user. Additional the ones
    required by some other functions. In example SupportShortURLs.

-->
<xsl:template mode="xarinit_init_modvars" select="xaraya_module">
    <xsl:variable name="module_prefix" select="registry/name" />
    /*
     * REGISTER THE MODULE VARIABLES
     */
    <xsl:for-each select="configuration/modvars/var">
    xarModSetVar(
        '<xsl:value-of select="$module_prefix" />'
        ,'<xsl:value-of select="@name" />'
        ,'<xsl:value-of select="text()" />' );
    </xsl:for-each>
</xsl:template>


<!-- =========================================================================

    MATCH: xaraya_module                    MODE: xarinit_init_blocks

    Register the blocks.

-->
<xsl:template mode="xarinit_init_blocks" match="xaraya_module">
    <xsl:variable name="module_prefix" select="registry/name" />
    /*
     * REGISTER BLOCKS
     */
    <xsl:for-each select="blocks/block">
    if (!xarModAPIFunc('blocks',
                       'admin',
                       'register_block_type',
                       array('modName'  => '<xsl:value-of select="$module_prefix" />',
                             'blockType'=> '<xsl:value-of  select="@name" />'))) return;
    </xsl:for-each>
</xsl:template>


<!-- =========================================================================

    MATCH: xaraya_module                    MODE: xarinit_init_hooks

    Register the hooks

-->
<xsl:template mode="xarinit_init_hooks" match="xaraya_module">
    <xsl:variable name="module_prefix" select="registry/name" />

    /*
     * REGISTER HOOKS
     */
<xsl:for-each select="configuration/hooks/hook">
<!-- check if the hook is required -->
<xsl:choose>
<xsl:when test="@required = 'no'">
    // Hook for module <xsl:value-of select="@module" />
    if ( xarModIsAvailable('<xsl:value-of select="@module" />' )) {
        xarModAPIFunc(
            'modules'
            ,'admin'
            ,'enablehooks'
            ,array(
                'hookModName'       => '<xsl:value-of select="@module" />'
                ,'callerModName'    => '<xsl:value-of select="../../../registry/name" />'));
    }
</xsl:when>
<xsl:otherwise>
    // Hook for module <xsl:value-of select="@module" />
    xarModAPIFunc(
        'modules'
        ,'admin'
        ,'enablehooks'
        ,array(
            'hookModName'       => '<xsl:value-of select="@module" />'
            ,'callerModName'    => '<xsl:value-of select="../../../registry/name" />'));
</xsl:otherwise>
</xsl:choose>
</xsl:for-each>
</xsl:template>


<!-- =========================================================================

    MATCH: database                         MODE: xarinit_init_tables

    Generate the stuff needed when the module configured database tables. We
    create the tables, the indizes and the dynamic data objects.

-->
<xsl:template mode="xarinit_init_tables" match="xaraya_module">
    <xsl:variable name="module_prefix" select="registry/name" />
    list($dbconn) = xarDBGetConn();
    $xartables = xarDBGetTables();
    xarDBLoadTableMaintenanceAPI();

    <xsl:for-each select="database/table">
    $<xsl:value-of select="@name" />table = $xartables['<xsl:value-of select="@name" />'];

    $fields = array(
    <xsl:for-each select="structure/field">
        'xar_<xsl:value-of select="@name" />'   =>  array(
            'type'          =>  '<xsl:value-of select="@type" />',
            <xsl:if test="@size">'size'          =>  '<xsl:value-of select="@size" />',</xsl:if>
            'null'          =>  <xsl:choose>
                                    <xsl:when test="not(null)">false</xsl:when>
                                    <xsl:otherwise>:<xsl:value-of select="null" /></xsl:otherwise>
                                </xsl:choose>,
            <xsl:if test="@increment = 'true'">'increment'     =>  true, </xsl:if>
            <xsl:if test="@width">'width'     =>  <xsl:value-of select="@width" />, </xsl:if>
            <xsl:if test="@width">'decimals'  =>  <xsl:value-of select="@decimals" />, </xsl:if>
            <xsl:if test="@default">'default'   =>  '<xsl:value-of select="@default" />', </xsl:if>
            <xsl:if test="@primary_key = 'true'">'primary_key'   =>  true </xsl:if>
            )<xsl:if test="position() != last()">,</xsl:if>
    </xsl:for-each>
    );

    // Create the Table - the function will return the SQL is successful or
    // raise an exception if it fails, in this case $query is empty
    $query = xarDBCreateTable($<xsl:value-of select="@name" />table,$fields);
    if (empty($query)) return; // throw back

    // Pass the Table Create DDL to adodb to create the table and send exception if unsuccessful
    $result =&amp; $dbconn->Execute($query);
    if (!$result) return;

    // INIDZES FOR THE TABLE
    $sitePrefix = xarDBGetSiteTablePrefix();

    <xsl:for-each select="index">
    // <xsl:value-of select="comment" />
    $index = array(
        'name'      => 'i_' . $sitePrefix . '<xsl:value-of select="$module_prefix" />_<xsl:value-of select="@name" />'
        ,'fields'   => array( <xsl:for-each select="field">'xar_<xsl:value-of select="@name" />'<xsl:if test="last() != position()">,</xsl:if></xsl:for-each> )
        ,'unique'   => <xsl:choose><xsl:when test="@unique = 'true'">true</xsl:when><xsl:otherwise>false</xsl:otherwise></xsl:choose>
        );
    $query = xarDBCreateIndex( $xartables['<xsl:value-of select="../@name" />'], $index );
    if (!$query) return;
    $result =&amp; $dbconn->Execute($query);
    if (!$result) return;
    </xsl:for-each>

    // MODULE WARIABLES FOR THIS TABLE
    xarModSetVar(
        '<xsl:value-of select="$module_prefix" />'
        ,'itemsperpage.<xsl:value-of select="@itemtype" />'
        ,10 );

    </xsl:for-each>

    /*
     * REGISTER THE TABLES AT DYNAMICDATA
     */
    $objectid = xarModAPIFunc(
        'dynamicdata'
        ,'util'
        ,'import'
        ,array(
            'file'  => 'modules/<xsl:value-of select="$module_prefix" />/xarobject.xml'));
    if (empty($objectid)) return;
</xsl:template>


<!-- =========================================================================

    MATCH: xaraya_module                    MODE: xarinit_init

-->
<xsl:template match="xaraya_module" mode="xarinit_init">
    <xsl:variable name="module_prefix" select="registry/name" />
/**
 * initialise the module.  This function is only ever called once during the
 * lifetime of a particular module instance
 */
function <xsl:value-of select="$module_prefix" />_init()
{

    <!-- Create the stuff for out database tables. -->
    <xsl:if test="boolean( database/table )">
        <xsl:apply-templates select="." mode="xarinit_init_tables" />
    </xsl:if>

    <!-- Register the modvars -->
    <xsl:if test="boolean( configuration/modvars/var )">
        <xsl:apply-templates mode="xarinit_init_modvars" select="." />
    </xsl:if>

    <!-- // FUNC // ShortURLSupport

         create the following modvar only if the user enabled short url
         support

    -->
    <xsl:if test="not( boolean( configuration/capabilities/supportshorturls ) )
                  or configuration/capabilities/supportshorturls/text() = 'yes'">
    /*
     * Module Variable for ShortURLSupport!
     */
    xarModSetVar(
        '<xsl:value-of select="$module_prefix" />'
        ,'SupportShortURLs'
        ,0 );
    </xsl:if>

    <xsl:if test="boolean( blocks/block )">
        <xsl:apply-templates mode="xarinit_init_blocks" select="." />
    </xsl:if>

    <!-- Register the modvars -->
    <xsl:if test="boolean( configuration/hooks/hook )">
        <xsl:apply-templates mode="xarinit_init_hooks" select="." />
    </xsl:if>


    /*
     * REGISTER MASKS
     */
    <xsl:apply-templates select="." mode="xarinit_init_security" />

    // Initialisation successful
    return true;
}
</xsl:template>



<!-- =========================================================================

    MATCH: xaraya_module                    MODE: xarinit_upgrade

-->
<xsl:template match="xaraya_module" mode="xarinit_upgrade">
    <xsl:variable name="module_prefix" select="registry/name" />

/**
 * upgrade the module from an older version.
 * This function can be called multiple times
 */
function <xsl:value-of select="$module_prefix" />_upgrade($oldversion)
{
    // Upgrade dependent on old version number
    switch($oldversion) {

        // TODO // IMPLEMENT YOUR UPGRADES

        default:
            // TODO // throw appropriate exception
            return false;
    }

    // Update successful
    return true;
}
</xsl:template>


<!-- =========================================================================

    MATCH: xaraya_module                    MODE: xarinit_delete_tables

-->
<xsl:template match="xaraya_module" mode="xarinit_delete_tables">
    <xsl:variable name="module_prefix" select="registry/name" />
    /*
     * REMOVE THE DATABASE TABLES AND DD OBJECTS
     */
    list($dbconn) = xarDBGetConn();
    $xartables = xarDBGetTables();

    // adodb does not provide the functionality to abstract table creates
    // across multiple databases.  Xaraya offers the xarDropeTable function
    // contained in the following file to provide this functionality.
    xarDBLoadTableMaintenanceAPI();

    <xsl:for-each select="database/table">
    // drop table <xsl:value-of select="@name" /> .Generate the SQL to drop
    // the table using the API.
    $query = xarDBDropTable($xartables['<xsl:value-of select="@name" />']);
    if (empty($query)) return; // throw back

    // Drop the table and send exception if returns false.
    $result =&amp; $dbconn->Execute($query);
    // TODO // CHECK
    if (!$result) return;

    // remove the table from dynamic data
    $objectinfo = xarModAPIFunc(
        'dynamicdata'
        ,'user'
        ,'getobjectinfo'
        ,array(
            'modid'     => xarModGetIDFromName('<xsl:value-of select="$module_prefix" />' )
            ,'itemtype' => <xsl:value-of select="@itemtype" /> ));

    if (!isset($objectinfo) || empty($objectinfo['objectid'])) {
        return;
    }
    $objectid = $objectinfo['objectid'];

    if (!empty($objectid)) {
        xarModAPIFunc('dynamicdata','admin','deleteobject',array('objectid' => $objectid));
    }
    </xsl:for-each>

</xsl:template>


<!-- =========================================================================

    MATCH: xaraya_module                    MODE: xarinit_delete_blocks

-->
<xsl:template match="xaraya_module" mode="xarinit_delete_blocks">
    <xsl:variable name="module_prefix" select="registry/name" />
    /*
     * UNREGISTER BLOCKS
     */
    <xsl:for-each select="blocks/block">
    if (!xarModAPIFunc('blocks',
                       'admin',
                       'unregister_block_type',
                       array('modName'  => '<xsl:value-of select="$module_prefix" />',
                             'blockType'=> '<xsl:value-of  select="@name" />'))) return;
    </xsl:for-each>
</xsl:template>


<!-- =========================================================================

    MATCH: xaraya_module                    MODE: xarinit_delete

-->
<xsl:template match="xaraya_module" mode="xarinit_delete">
    <xsl:variable name="module_prefix" select="registry/name" />
/**
 * Remove the module instance from the xaraya installation.
 *
 * This function is only ever called once during the lifetime of a particular
 * module instance.
 */
function <xsl:value-of select="$module_prefix" />_delete()
{
    /*
     * REMOVE MODULE VARS
     */
    if ( !xarModDelAllVars( '<xsl:value-of select="$module_prefix" />' ) )
        return;

    <!-- Delete the blocks -->
    <xsl:if test="boolean( blocks/block )"> <xsl:apply-templates select="." mode="xarinit_delete_blocks" /> </xsl:if>

    /*
     * REMOVE MASKS AND INSTANCES
     */
    xarRemoveMasks( '<xsl:value-of select="$module_prefix" />' );
    xarRemoveInstances( '<xsl:value-of select="$module_prefix" />' );

    <!-- Create the stuff for out database tables. -->
    <xsl:if test="boolean( database/table )"> <xsl:apply-templates select="." mode="xarinit_delete_tables" /> </xsl:if>

    // Deletion successful
    return true;
}

</xsl:template>
</xsl:stylesheet>
