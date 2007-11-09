<!--

    XARAYA MODULE WIZARD

    COPYRIGHT:      Michael Jansen
    CONTACT:        xaraya-module-wizard@schneelocke.de
    LICENSE:        GPL

-->
<xsl:stylesheet version="1.0"
                xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
                xmlns="http://www.w3.org/TR/xhtml1/strict">


<xsl:template match="xaraya_module" mode="xarinit">

    <xsl:message>
### Generating xarinit.php</xsl:message>

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
    xarRegisterMask( 'Read<xsl:value-of select="$module_prefix" />' ,'All' ,'<xsl:value-of select="$module_prefix" />' ,'All' ,'All', 'ACCESS_READ' );
    xarRegisterMask( 'View<xsl:value-of select="$module_prefix" />' ,'All' ,'<xsl:value-of select="$module_prefix" />' ,'All' ,'All', 'ACCESS_OVERVIEW' );
    xarRegisterMask( 'Delete<xsl:value-of select="$module_prefix" />' ,'All' ,'<xsl:value-of select="$module_prefix" />' ,'All' ,'All', 'ACCESS_DELETE' );
    xarRegisterMask( 'Edit<xsl:value-of select="$module_prefix" />' ,'All' ,'<xsl:value-of select="$module_prefix" />' ,'All' ,'All', 'ACCESS_EDIT' );
    xarRegisterMask( 'Add<xsl:value-of select="$module_prefix" />' ,'All' ,'<xsl:value-of select="$module_prefix" />' ,'All' ,'All', 'ACCESS_ADD' );
    xarRegisterMask( 'Admin<xsl:value-of select="$module_prefix" />' ,'All' ,'<xsl:value-of select="$module_prefix" />' ,'All' ,'All', 'ACCESS_ADMIN' );

</xsl:template>


<!-- =========================================================================

    MATCH: xaraya_module                    MODE: xarinit_init_modvars

    Register the module variables configured by the user. Additional the ones
    required by some other functions. In example SupportShortURLs.

-->
<xsl:template mode="xarinit_init_modvars" match="xaraya_module">
    <xsl:variable name="module_prefix" select="registry/name" />
    /*
     * REGISTER THE MODULE VARIABLES
     */
    <xsl:for-each select="configuration/modvars/var">
    xarModVars::Set(
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

    MATCH: xaraya_module                    MODE: xarinit_register_module_hooks

    Register the hooks

-->
<xsl:template mode="xarinit_register_module_hooks" match="xaraya_module">
    <xsl:variable name="module_prefix" select="registry/name" />

    if (!xarModRegisterHook(
            'module'
            ,'modifyconfig'
            ,'GUI'
            ,'<xsl:value-of select="$module_prefix" />'
            ,'hook'
            ,'module_modifyconfig' ))
        {
        return false;
        }
    if (!xarModRegisterHook(
            'module'
            ,'remove'
            ,'API'
            ,'<xsl:value-of select="$module_prefix" />'
            ,'hook'
            ,'module_remove' ))
        {
        return false;
        }
    if (!xarModRegisterHook(
            'module'
            ,'updateconfig'
            ,'API'
            ,'<xsl:value-of select="$module_prefix" />'
            ,'hook'
            ,'module_updateconfig' ))
        {
        return false;
        }
</xsl:template>



<!-- =========================================================================

    MATCH: xaraya_module                    MODE: xarinit_register_item_hooks

    Register the hooks

-->
<xsl:template mode="xarinit_register_item_hooks" match="xaraya_module">
    <xsl:variable name="module_prefix" select="registry/name" />

    if (!xarModRegisterHook(
            'item'
            ,'display'
            ,'GUI'
            ,'<xsl:value-of select="$module_prefix" />'
            ,'hook'
            ,'item_display' ))
        {
        return false;
        }
    if (!xarModRegisterHook(
            'item'
            ,'new'
            ,'GUI'
            ,'<xsl:value-of select="$module_prefix" />'
            ,'hook'
            ,'item_new' ))
        {
        return false;
        }
    if (!xarModRegisterHook(
            'item'
            ,'delete'
            ,'API'
            ,'<xsl:value-of select="$module_prefix" />'
            ,'hook'
            ,'item_delete' ))
        {
        return false;
        }
    if (!xarModRegisterHook(
            'item'
            ,'update'
            ,'API'
            ,'<xsl:value-of select="$module_prefix" />'
            ,'hook'
            ,'item_update' ))
        {
        return false;
        }
    if (!xarModRegisterHook(
            'item'
            ,'create'
            ,'API'
            ,'<xsl:value-of select="$module_prefix" />'
            ,'hook'
            ,'item_create' ))
        {
        return false;
        }

    if (!xarModRegisterHook(
            'item'
            ,'modify'
            ,'GUI'
            ,'<xsl:value-of select="$module_prefix" />'
            ,'hook'
            ,'item_modify' ))
        {
        return false;
        }
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
    // Hook for module <xsl:value-of select="@module" />
    xarModAPIFunc(
        'modules'
        ,'admin'
        ,'enablehooks'
        ,array(
            'hookModName'       => '<xsl:value-of select="@module" />'
            ,'callerModName'    => '<xsl:value-of select="../../../registry/name" />'));
</xsl:for-each>
</xsl:template>


<!-- =========================================================================

    MATCH: xaraya_module                    MODE: xarinit_register_transform_hooks

    Register the hooks

-->
<xsl:template mode="xarinit_register_transform_hooks" match="xaraya_module">
    <xsl:variable name="module_prefix" select="registry/name" />

    if (!xarModRegisterHook(
            'item'
            ,'transform'
            ,'API'
            ,'<xsl:value-of select="$module_prefix" />'
            ,'hook'
            ,'item_transformoutput' ))
        {
        return false;
        }
    if (!xarModRegisterHook(
            'item'
            ,'transform-input'
            ,'API'
            ,'<xsl:value-of select="$module_prefix" />'
            ,'hook'
            ,'item_transforminput' ))
        {
        return false;
        }
</xsl:template>

<!-- =========================================================================

    MATCH: xaraya_module                    MODE: xarinit_register_waiting_content_hook

    Register the hooks

-->
<xsl:template mode="xarinit_register_waiting_content_hook" match="xaraya_module">
    <xsl:variable name="module_prefix" select="registry/name" />

    if (!xarModRegisterHook(
            'item'
            ,'waitingcontent'
            ,'GUI'
            ,'<xsl:value-of select="$module_prefix" />'
            ,'hook'
            ,'waitingcontent' ))
        {
        return false;
        }
</xsl:template>


<!-- =========================================================================

    MATCH: xaraya_module                    MODE: xarinit_register_search_hook

    Register the hooks

-->
<xsl:template mode="xarinit_register_search_hook" match="xaraya_module">
    <xsl:variable name="module_prefix" select="registry/name" />

    if (!xarModRegisterHook(
            'item'
            ,'search'
            ,'GUI'
            ,'<xsl:value-of select="$module_prefix" />'
            ,'hook'
            ,'search' ))
        {
        return false;
        }
</xsl:template>

<!-- =========================================================================

    MATCH: database                         MODE: xarinit_init_tables

    Generate the stuff needed when the module configured database tables. We
    create the tables, the indizes and the dynamic data objects.

-->
<xsl:template mode="xarinit_init_tables" match="xaraya_module">
    <xsl:variable name="module_prefix" select="registry/name" />
    $dbconn =&amp; xarDBGetConn();
    $xartables = xarDBGetTables();
    xarDBLoadTableMaintenanceAPI();

    <xsl:for-each select="database/table">
    $<xsl:value-of select="@name" />table = $xartables['<xsl:value-of select="@name" />'];

    $fields = array(
    <xsl:for-each select="structure/field">
        '<xsl:value-of select="@name" />'   =>  array(
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
            <xsl:if test="@unsigned = 'true'">'unsigned'   =>  true, </xsl:if>
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

    // INIDCES FOR THE TABLE

    <xsl:for-each select="index">
    // <xsl:value-of select="comment" />
    $index = array(
        'name'      => 'i_' . $xarDB::getPrefix() . '<xsl:value-of select="$module_prefix" />_<xsl:value-of select="@name" />'
        ,'fields'   => array( <xsl:for-each select="field">'<xsl:value-of select="@name" />'<xsl:if test="last() != position()">,</xsl:if></xsl:for-each> )
        ,'unique'   => <xsl:choose><xsl:when test="@unique = 'true'">true</xsl:when><xsl:otherwise>false</xsl:otherwise></xsl:choose>
        );
    $query = xarDBCreateIndex( $xartables['<xsl:value-of select="../@name" />'], $index );
    if (!$query) return;
    $result =&amp; $dbconn->Execute($query);
    if (!$result) return;
    </xsl:for-each>

    // MODULE WARIABLES FOR THIS TABLE
    xarModVars::set(
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
    xarModVars::set(
        '<xsl:value-of select="$module_prefix" />'
        ,'SupportShortURLs'
        ,0 );
    </xsl:if>

    <xsl:if test="boolean( blocks/block )">
        <xsl:apply-templates mode="xarinit_init_blocks" select="." />
    </xsl:if>

    <!-- Install the hooks -->
    <xsl:if test="boolean( configuration/hooks/hook )">
        <xsl:apply-templates mode="xarinit_init_hooks" select="." />
    </xsl:if>

    <!-- register module hooks -->
    <xsl:if test="configuration/capabilities/item_hooks/text() = 'yes'
               or configuration/capabilities/transform_hooks/text() = 'yes'" >

    <xsl:message>       * register module hooks</xsl:message>
    <xsl:apply-templates mode="xarinit_register_module_hooks" select="." />
    </xsl:if>

    <!-- Register transform hooks -->
    <xsl:if test="configuration/capabilities/transform_hooks/text() = 'yes'">

    <xsl:message>       * register transform hooks</xsl:message>
    <xsl:apply-templates mode="xarinit_register_transform_hooks" select="." />
    </xsl:if>

    <!-- Register item hooks -->
    <xsl:if test="configuration/capabilities/item_hooks/text() = 'yes'">

    <xsl:message>       * register item hooks</xsl:message>
    <xsl:apply-templates mode="xarinit_register_item_hooks" select="." />
    </xsl:if>

    <!-- Register search hook -->
    <xsl:if test="configuration/capabilities/search_hook/text() = 'yes'">

    <xsl:message>       * register search hook</xsl:message>
    <xsl:apply-templates mode="xarinit_register_search_hook" select="." />
    </xsl:if>

    <!-- Register waiting content hook -->
    <xsl:if test="configuration/capabilities/waiting_content_hook/text() = 'yes'">

    <xsl:message>       * register waiting content hook</xsl:message>
    <xsl:apply-templates mode="xarinit_register_waiting_content_hook" select="." />
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
    $dbconn =&amp; xarDBGetConn();
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
    if ( !xarModVars::delete_all( '<xsl:value-of select="$module_prefix" />' ) )
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
