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

    xaritemtypeapi.xsl
    ==================

-->

<!-- ENTRY POINT    print out progress and call module template -->
<xsl:template match="/" mode="xaritemtypeapi" xml:space="default">
### Generating itemtype apis <xsl:apply-templates mode="xaritemtypeapi" select="xaraya_module" />
</xsl:template>



<!-- MODULE POINT

     Create a new file called xaritemtypeapi.php.

-->
<xsl:template match="xaraya_module" mode="xaritemtypeapi">
    <xsl:for-each select="database/table[ @user='true' or @admin='true' ]">

        <xsl:apply-templates mode="xaritemtypeapi" select="." />

    </xsl:for-each>
</xsl:template>


<xsl:template match="table" mode="xaritemtypeapi">

    <xsl:variable name="itemtype" select="@name" />
    for itemtype <xsl:value-of select="$itemtype" />

    <xsl:if test="@admin = 'true'">

        <xsl:document href="{$output}/xar{$itemtype}api/modify.php" format="text" omit-xml-declaration="yes" ><xsl:processing-instruction name="php">

            <xsl:call-template name="xaraya_standard_php_file_header" select=".">
                <xsl:with-param name="filename">xar<xsl:value-of select="$itemtype" />api/modify.php</xsl:with-param>
            </xsl:call-template>

            <xsl:apply-templates mode="xaritemtypeapi_modify" select="." />

            <xsl:call-template name="xaraya_standard_php_file_footer" select="." />

        </xsl:processing-instruction></xsl:document>


        <xsl:document href="{$output}/xar{$itemtype}api/update.php" format="text" omit-xml-declaration="yes" ><xsl:processing-instruction name="php">

            <xsl:call-template name="xaraya_standard_php_file_header" select=".">
                <xsl:with-param name="filename">xar<xsl:value-of select="$itemtype" />api/update.php</xsl:with-param>
            </xsl:call-template>

            <xsl:apply-templates mode="xaritemtypeapi_update" select="." />

            <xsl:call-template name="xaraya_standard_php_file_footer" select="." />

        </xsl:processing-instruction></xsl:document>


        <xsl:document href="{$output}/xar{$itemtype}api/delete.php" format="text" omit-xml-declaration="yes" ><xsl:processing-instruction name="php">

            <xsl:call-template name="xaraya_standard_php_file_header" select=".">
                <xsl:with-param name="filename">xar<xsl:value-of select="$itemtype" />api/delete.php</xsl:with-param>
            </xsl:call-template>

            <xsl:apply-templates mode="xaritemtypeapi_delete" select="." />

            <xsl:call-template name="xaraya_standard_php_file_footer" select="." />

        </xsl:processing-instruction></xsl:document>


        <xsl:document href="{$output}/xar{$itemtype}api/confirmdelete.php" format="text" omit-xml-declaration="yes" ><xsl:processing-instruction name="php">

            <xsl:call-template name="xaraya_standard_php_file_header" select=".">
                <xsl:with-param name="filename">xar<xsl:value-of select="$itemtype" />api/confirmdelete.php</xsl:with-param>
            </xsl:call-template>

            <xsl:apply-templates mode="xaritemtypeapi_confirmdelete" select="." />

            <xsl:call-template name="xaraya_standard_php_file_footer" select="." />

        </xsl:processing-instruction></xsl:document>

        <xsl:document href="{$output}/xar{$itemtype}api/new.php" format="text" omit-xml-declaration="yes" ><xsl:processing-instruction name="php">

            <xsl:call-template name="xaraya_standard_php_file_header" select=".">
                <xsl:with-param name="filename">xar<xsl:value-of select="$itemtype" />api/new.php</xsl:with-param>
            </xsl:call-template>

            <xsl:apply-templates mode="xaritemtypeapi_new" select="." />

            <xsl:call-template name="xaraya_standard_php_file_footer" select="." />

        </xsl:processing-instruction></xsl:document>


        <xsl:document href="{$output}/xar{$itemtype}api/create.php" format="text" omit-xml-declaration="yes" ><xsl:processing-instruction name="php">

            <xsl:call-template name="xaraya_standard_php_file_header" select=".">
                <xsl:with-param name="filename">xar<xsl:value-of select="$itemtype" />api/create.php</xsl:with-param>
            </xsl:call-template>

            <xsl:apply-templates mode="xaritemtypeapi_create" select="." />

            <xsl:call-template name="xaraya_standard_php_file_footer" select="." />

        </xsl:processing-instruction></xsl:document>


        <xsl:document href="{$output}/xar{$itemtype}api/config.php" format="text" omit-xml-declaration="yes" ><xsl:processing-instruction name="php">

            <xsl:call-template name="xaraya_standard_php_file_header" select=".">
                <xsl:with-param name="filename">xar<xsl:value-of select="$itemtype" />api/config.php</xsl:with-param>
            </xsl:call-template>

            <xsl:apply-templates mode="xaritemtypeapi_config" select="." />

            <xsl:call-template name="xaraya_standard_php_file_footer" select="." />

        </xsl:processing-instruction></xsl:document>

    </xsl:if>

    <xsl:if test="@user = 'true' or @admin='true'">

        <xsl:document href="{$output}/xar{$itemtype}api/display.php" format="text" omit-xml-declaration="yes" ><xsl:processing-instruction name="php">

            <xsl:call-template name="xaraya_standard_php_file_header" select=".">
                <xsl:with-param name="filename">xar<xsl:value-of select="$itemtype" />api/display.php</xsl:with-param>
            </xsl:call-template>

            <xsl:apply-templates mode="xaritemtypeapi_display" select="." />

            <xsl:call-template name="xaraya_standard_php_file_footer" select="." />

        </xsl:processing-instruction></xsl:document>


        <xsl:document href="{$output}/xar{$itemtype}api/view.php" format="text" omit-xml-declaration="yes" ><xsl:processing-instruction name="php">

            <xsl:call-template name="xaraya_standard_php_file_header" select=".">
                <xsl:with-param name="filename">xar<xsl:value-of select="$itemtype" />api/view.php</xsl:with-param>
            </xsl:call-template>

            <xsl:apply-templates mode="xaritemtypeapi_view" select="." />

            <xsl:call-template name="xaraya_standard_php_file_footer" select="." />

        </xsl:processing-instruction></xsl:document>
    </xsl:if>

</xsl:template>



<!-- =========================================================================

    MODE: xaritemtypeapi_view                     MATCH:  table

-->
<xsl:template mode="xaritemtypeapi_view" match="table">
    <xsl:variable name="module_prefix" select="../../registry/name" />
/**
 * // TODO // Add description
 *
 * // TODO // explain that the function is called from admin and user * interface.
 */
function <xsl:value-of select="$module_prefix" />_<xsl:value-of select="@name" />api_view( $args ) {

    if (!xarSecurityCheck( 'View<xsl:value-of select="$module_prefix" />')) return;

    // Get parameter from browser
    list( $type, $startnum, $itemid ,$itemtype ) = xarVarCleanFromInput( 'type', 'startnum', 'itemid', 'itemtype' );
    extract( $args );

    // The itemtype is a must!
    if ( empty( $itemtype ) ) {
        xarResponseRedirect(
            xarModURL(
                '<xsl:value-of select="$module_prefix" />'
                ,'user'
                ,'main' ));
    }

    $data =&amp; xarModAPIFunc(
        '<xsl:value-of select="$module_prefix" />'
        ,'private'
        ,'common'
        ,array(
            'title' => 'View <xsl:value-of select="label" />'
            ,'type' => $type
            ));

    $itemsperpage = xarModGetVar(
            '<xsl:value-of select="$module_prefix" />'
            ,'itemsperpage.' . $itemtype );

    $objects =&amp; xarModAPIFunc(
        '<xsl:value-of select="$module_prefix" />'
        ,'user'
        ,'getall'
        ,array(
             'itemtype'  => $itemtype
            ,'numitems'  => $itemsperpage
            ,'startnum'  => $startnum
            ,'sort'      => array(
                <xsl:for-each select="order/field">'<xsl:value-of select="@name" />'<xsl:if test="position() != last()">,</xsl:if>
                </xsl:for-each>)
            ,'fieldlist' => array( <xsl:for-each select="structure/field[ @overview = 'true' ]">'<xsl:value-of select="@name" />'<xsl:if test="position() != last()">,</xsl:if>
               </xsl:for-each>)
        ));
    if ( empty($objects) ) return;

    $data['objects_props']  =&amp; $objects->getProperties();
    $data['objects_values'] =&amp; $objects->items;
    $data['itemtype'] = $itemtype;
    $data['_bl_template'] = '<xsl:value-of select="@name" />';
    $data['pager'] = xarTplGetPager(
        $startnum
        ,xarModAPIFunc(
            '<xsl:value-of select="$module_prefix" />'
            ,'user'
            ,'count'
            ,array( 'itemtype' => $itemtype ))
        ,xarModURL(
            '<xsl:value-of select="$module_prefix" />'
            ,$type
            ,'view'
            ,array(
                'startnum'  => '%%'
                ,'itemtype' => $itemtype ))
        ,$itemsperpage );

    return $data;
}
</xsl:template>




<!-- ========================================================================

        MODE: xaritemtypeapi_config      MATCH: xaraya_module

-->
<xsl:template mode="xaritemtypeapi_config" match="table">
    <xsl:variable name="module_prefix" select="../../registry/name" />
/**
 * Administration for the <xsl:value-of select="$module_prefix" /> module.
 */
function <xsl:value-of select="$module_prefix" />_<xsl:value-of select="@name" />api_config( $args ) {

    $data =&amp; xarModAPIFunc(
        '<xsl:value-of select="$module_prefix" />'
        ,'private'
        ,'common'
        ,array(
            'title' => 'View <xsl:value-of select="label" /> Configuration'
            ,'type' => 'admin'
            ));

    list( $itemtype, $authid ) = xarVarCleanFromInput( 'itemtype', 'authid' );
    extract( $args );

    if ( isset( $authid ) ) {

        /*
         * The user confirmed the form. So save the results.
         */

        if (!xarSecConfirmAuthKey()) return;

        $itemsperpage = xarVarCleanFromInput( 'itemsperpage' );

        if ( empty( $itemsperpage ) or !is_numeric( $itemsperpage ) ) {
            $itemsperpage = 10;
        }

        xarModSetVar(
            '<xsl:value-of select="$module_prefix" />'
            ,'itemsperpage.' . $itemtype
            ,$itemsperpage );

        <xsl:if test="@hooks = 'enable'">
        /*
         * call the hook 'module:updateconfig:GUI'
         */
        $args = array(
            'module'        =>  '<xsl:value-of select="$module_prefix" />'
            ,'itemtype'     =>  $itemtype );
        $data['hooks'] = xarModCallHooks(
            'module'
            ,'updateconfig'
            ,'<xsl:value-of select="$module_prefix" />'
            ,$args
            ,'<xsl:value-of select="$module_prefix" />' );
        </xsl:if>

        /*
         * Set a status message
         */
        xarSessionSetVar(
            '<xsl:value-of select="$module_prefix" />_statusmsg'
            ,'Updated the <xsl:value-of select="label" /> configuration!' );

        /*
         * Finished. Back to the sender!
         */
        xarResponseRedirect(
            xarModURL(
                '<xsl:value-of select="$module_prefix" />'
                ,'admin'
                ,'config'
                ,array(
                    'itemtype' => $itemtype )));

    } // Save the changes

    <xsl:if test="@hooks = 'enable'">
    /*
     * call the hook 'module:modifyconfig:GUI'
     */
    $args = array(
        'module'        =>  '<xsl:value-of select="$module_prefix" />'
        ,'itemtype'     =>  $itemtype );
    $data['hooks'] = xarModCallHooks(
        'module'
        ,'modifyconfig'
        ,'<xsl:value-of select="$module_prefix" />'
        ,$args
        ,'<xsl:value-of select="$module_prefix" />' );

    </xsl:if>

    $data['itemtype']       = $itemtype;
    $data['itemtype_label'] = $itemtype;
    $data['itemsperpage']   = xarModGetVar(
        '<xsl:value-of select="$module_prefix" />'
        ,'itemsperpage.' . $itemtype );


    /*
     * Populate the rest of the template
     */
    $data['common']['menu_label'] = 'Configure';
    $data['common']['menu']       = xarModAPIFunc(
        '<xsl:value-of select="$module_prefix" />'
        ,'private'
        ,'adminconfigmenu'
        ,array() );
    $data['action']     = xarModURL( '<xsl:value-of select="$module_prefix" />', 'admin', 'config' );
    $data['authid']     = xarSecGenAuthKey();
    $data['_bl_template'] = '<xsl:value-of select="@name" />';
    return $data;
}

</xsl:template>



<!-- =========================================================================

MODE: xaritemtypeapi_update               MATCH:  xaraya_module

-->
<xsl:template mode="xaritemtypeapi_update" match="table">
<xsl:variable name="module_prefix" select="../../registry/name" />

/**
* Update a <xsl:value-of select="@name" /> object
*/
function <xsl:value-of select="$module_prefix" />_<xsl:value-of select="@name" />api_update( $args ) {

    if (!xarSecurityCheck( 'Edit<xsl:value-of select="$module_prefix" />')) return;

    list(
        $itemid
        ,$itemtype
        ) = xarVarCleanFromInput( 'itemid', 'itemtype' );
    extract( $args );

    // Retrieve the object
    $object =&amp; xarModAPIFunc(
        '<xsl:value-of select="$module_prefix" />'
        ,'user'
        ,'get'
        ,array(
             'itemtype'  => <xsl:value-of select="@itemtype" />
            ,'itemid'    => $itemid
        ));
    if ( empty( $object ) ) return;

    // check the input values for this object
    $isvalid = $object->checkInput();

    if ( $isvalid ) {

        /*
         * The object is valid and no preview is wished. Update it
         */
        $itemid = $object->updateItem();
        if (empty( $itemid) ) return; // throw back

        <xsl:if test="@hooks = 'enable'">
        /*
         * call the hook 'item:update:API'
         */
        $args = array(
            'module'        =>  '<xsl:value-of select="$module_prefix" />'
            ,'itemid'       =>  $itemid
            ,'itemtype'     =>  '<xsl:value-of select="@itemtype" />' );
        $data['hooks'] = xarModCallHooks(
            'item'
            ,'update'
            ,$itemid
            ,$args
            ,'<xsl:value-of select="$module_prefix" />' );
        </xsl:if>

        /*
         * Compose the statusmessage
         */
        $item_title = xarModAPIFunc(
            '<xsl:value-of select="$module_prefix" />'
            ,'user'
            ,'gettitle'
            ,array(
                'object'    =>  $object
                ,'itemtype' =>  $itemtype ));

        xarSessionSetVar(
            '<xsl:value-of select="$module_prefix" />_statusmsg'
            ,'Modified <xsl:value-of select="label" /> ' . $itemid . ' -> ' . $item_title . '.' );

        /*
         * This function generated no output, and so now it is complete we redirect
         * the user to an appropriate page for them to carry on their work
         */
        xarResponseRedirect(
            xarModURL(
                '<xsl:value-of select="$module_prefix" />'
                ,'user'
                ,'display'
                ,array(
                    'itemid'    => $itemid
                    ,'itemtype'  => <xsl:value-of select="@itemtype" /> )));

    } else {

        // Back to modify
        return <xsl:value-of select="$module_prefix" />_<xsl:value-of select="@name" />api_modify<xsl:value-of select="@name" />($args);

    }
}
</xsl:template>


<!-- =========================================================================

    MODE: xaritemtypeapi_modify               MATCH:  xaraya_module

-->
<xsl:template mode="xaritemtypeapi_modify" match="table">
    <xsl:variable name="module_prefix" select="../../registry/name" />

/**
 * Modify a <xsl:value-of select="@name" /> object
 */
function <xsl:value-of select="$module_prefix" />_<xsl:value-of select="@name" />api_modify( $args ) {

    if (!xarSecurityCheck( 'Edit<xsl:value-of select="$module_prefix" />')) return;

    list(
        $itemid
        ,$authid
        ,$itemtype
        ) = xarVarCleanFromInput( 'itemid', 'authid', 'itemtype' );
    extract( $args );

    // Retrieve the object
    $object =&amp; xarModAPIFunc(
        '<xsl:value-of select="$module_prefix" />'
        ,'user'
        ,'get'
        ,array(
             'itemtype'  => <xsl:value-of select="@itemtype" />
            ,'itemid'    => $itemid
        ));
    if ( empty( $object ) ) return;

    $item_title = xarModAPIFunc(
        '<xsl:value-of select="$module_prefix" />'
        ,'user'
        ,'gettitle'
        ,array(
            'object'    =>  $object
            ,'itemtype' =>  $itemtype ));
    $data = xarModAPIFunc(
        '<xsl:value-of select="$module_prefix" />'
        ,'private'
        ,'common'
        ,array(
            'title' => 'Modify <xsl:value-of select="label" /> ' . $item_title
            ,'type' => 'admin'
            ));

    // check if authid is set.
    if ( isset( $authid ) ) {

        // check the input values for this object
        $isvalid = $object->checkInput();

        /*
         * We create the preview with the <xsl:value-of select="$module_prefix" />_userapi_view<xsl:value-of select="@name" />()
         * function.
         */
        $preview = xarModFunc(
            '<xsl:value-of select="../../registry/name" />'
            ,'user'
            ,'display'
            ,array(
                'itemtype'  => '<xsl:value-of select="@itemtype" />'
                ,'object'   => $object ));
        if ( !isset( $preview ) ) return;
        $data['preview'] = $preview;

    }

    <xsl:if test="@hooks = 'enable'">
    /*
     * call the hook 'item:modify:GUI'
     */
    $args = array(
        'module'        =>  '<xsl:value-of select="$module_prefix" />'
        ,'itemid'       =>  $itemid
        ,'itemtype'     =>  '<xsl:value-of select="@itemtype" />' );
    $data['hooks'] = xarModCallHooks(
        'item'
        ,'modify'
        ,$itemid
        ,$args
        ,'<xsl:value-of select="$module_prefix" />' );
    </xsl:if>

    /*
     * Compose the data for the template
     */
    $data['object'] = $object;
    $data['itemid'] = $itemid;
    $data['action'] = xarModURL(
        '<xsl:value-of select="$module_prefix" />'
        ,'admin'
        ,'modify'
        ,array(
            'itemtype'  => <xsl:value-of select="@itemtype" />
            ,'itemid'   => $itemid ));
    $data['authid'] = xarSecGenAuthKey();
    $data['_bl_template'] = '<xsl:value-of select="@name" />';

    return $data;
}
</xsl:template>




<!-- =========================================================================

    MODE: xaritemtypeapi_confirmdelete              MATCH:

-->
<xsl:template mode="xaritemtypeapi_confirmdelete" match="table">
    <xsl:variable name="module_prefix" select="../../registry/name" />
/**
 * Confirm the deletion of a <xsl:value-of select="@name" /> object.
 *
 */
function <xsl:value-of select="$module_prefix" />_<xsl:value-of select="@name" />api_confirmdelete( $args ) {

    if (!xarSecurityCheck( 'Delete<xsl:value-of select="$module_prefix" />')) return;

    list ( $itemid,  $itemtype ) = xarVarCleanFromInput( 'itemid', 'itemtype' );
    extract($args);

    // Retrieve the object
    $object =&amp; xarModAPIFunc(
        '<xsl:value-of select="$module_prefix" />'
        ,'user'
        ,'get'
        ,array(
             'itemtype'  => <xsl:value-of select="@itemtype" />
            ,'itemid'    => $itemid
        ));
    if ( empty( $object ) ) return;

    $item_title = xarModAPIFunc(
        '<xsl:value-of select="$module_prefix" />'
        ,'user'
        ,'gettitle'
        ,array(
            'object'    =>  $object
            ,'itemtype' =>  $itemtype ));
    $data = xarModAPIFunc(
        '<xsl:value-of select="$module_prefix" />'
        ,'private'
        ,'common'
        ,array(
            'title' => 'Delete <xsl:value-of select="label" /> ' .$item_title
            ,'type' => 'admin'
            ));

    /*
     * Compose the data for the template
     */
    $data['object'] = $object;
    $data['itemid'] = $itemid;
    $data['action'] = xarModURL(
        '<xsl:value-of select="$module_prefix" />'
        ,'admin'
        ,'delete'
        ,array(
            'itemtype'  => <xsl:value-of select="@itemtype" />
            ,'itemid'   => $itemid ));
    $data['authid'] = xarSecGenAuthKey();
    $data['_bl_template'] = '<xsl:value-of select="@name" />';

    return $data;

}
</xsl:template>


<!-- =========================================================================

    MODE: xaritemtypeapi_delete              MATCH:

-->
<xsl:template mode="xaritemtypeapi_delete" match="table">
    <xsl:variable name="module_prefix" select="../../registry/name" />
/**
 * Delete a <xsl:value-of select="@name" /> object.
 *
 */
function <xsl:value-of select="$module_prefix" />_<xsl:value-of select="@name" />api_delete( $args ) {

    if (!xarSecurityCheck( 'Delete<xsl:value-of select="$module_prefix" />')) return;

    list ( $itemid, $itemtype ) = xarVarCleanFromInput( 'itemid', 'itemtype' );
    extract($args);

    // Retrieve the object
    $object =&amp; xarModAPIFunc(
        '<xsl:value-of select="$module_prefix" />'
        ,'user'
        ,'get'
        ,array(
             'itemtype'  => <xsl:value-of select="@itemtype" />
            ,'itemid'    => $itemid
        ));
    if ( empty( $object ) ) return;

    $item_title = xarModAPIFunc(
        '<xsl:value-of select="$module_prefix" />'
        ,'user'
        ,'gettitle'
        ,array(
            'object'    =>  $object
            ,'itemtype' =>  $itemtype ));

    /*
     * The user confirmed the deletion so let's go.
     */
    $itemid = $object->deleteItem();
    if ( empty( $itemid ) ) return;

    <xsl:if test="@hooks = 'enable'">// The 'api:delete' hook is called from dynamic data during createItem() !</xsl:if>
    /*
     * Set the status message
     */
    xarSessionSetVar(
        '<xsl:value-of select="$module_prefix" />_statusmsg'
        ,'Deleted  <xsl:value-of select="label" /> '. $itemid .' -> '. $item_title .'!' );

    // This function generated no output, and so now it is complete we redirect
    // the user to an appropriate page for them to carry on their work
    xarResponseRedirect(
        xarModURL(
            '<xsl:value-of select="$module_prefix" />'
            ,'admin'
            ,'view'
            ,array(
                'itemtype' => <xsl:value-of select="@itemtype" /> )));

}
</xsl:template>



<!-- =========================================================================

    MODE: xaritemtypeapi_new             MATCH:

-->
<xsl:template mode="xaritemtypeapi_new" match="table">
    <xsl:variable name="module_prefix" select="../../registry/name" />

/**
 * Create a <xsl:value-of select="@name" /> object
 *
 */
function <xsl:value-of select="$module_prefix" />_<xsl:value-of select="@name" />api_new( $args ) {

    if (!xarSecurityCheck( 'Add<xsl:value-of select="$module_prefix" />')) return;

    list ( $authid, $itemtype ) = xarVarCleanFromInput( 'authid', 'itemtype' );
    extract( $args );

    // Retrieve the object via the dynamicdata module api.
    $object = xarModAPIFunc(
        'dynamicdata'
        ,'user'
        ,'getobject'
        ,array(
            'module'     => '<xsl:value-of select="$module_prefix" />'
            ,'itemtype'  => <xsl:value-of select="@itemtype" />
        ));
    if ( empty($object) ) return;

    /*
     * Initialize the data array();
     */
    $data = xarModAPIFunc(
        '<xsl:value-of select="$module_prefix" />'
        ,'private'
        ,'common'
        ,array(
            'title' => 'New <xsl:value-of select="label" /> '
            ,'type' => 'user'
            ));

    // These function is called under different contexts.
    // 1. first time ( authid is not set )
    // 2. preview    ( authid is set )
    // 3. Submit with errors ( authid is set )
    if ( isset( $authid ) ) {

        // check the input values for this object
        $isvalid = $object->checkInput();

        /*
         * We create the preview with the <xsl:value-of select="$module_prefix" />_userapi_view<xsl:value-of select="@name" />()
         * function.
         */
        $preview = xarModFunc(
            '<xsl:value-of select="../../registry/name" />'
            ,'user'
            ,'display'
            ,array(
                'itemtype'  =>  '<xsl:value-of select="@itemtype" />'
                ,'object'   => $object ));
        if ( !isset( $preview ) ) return;
        $data['preview'] = $preview;

    }

    <xsl:if test="@hooks = 'enable'">
    /*
     * call the hook 'module:modifyconfig:GUI'
     */
    $args = array(
        'module'        =>  '<xsl:value-of select="$module_prefix" />'
        ,'itemid'       =>  NULL
        ,'itemtype'     =>  '<xsl:value-of select="@itemtype" />' );
    $data['hooks'] = xarModCallHooks(
        'item'
        ,'new'
        ,NULL
        ,$args
        ,'<xsl:value-of select="$module_prefix" />' );
    </xsl:if>

    /*
     * Compose the data for the template
     */
    $data['object'] = $object;
    $data['action'] = xarModURL(
        '<xsl:value-of select="$module_prefix" />'
        ,'admin'
        ,'new'
        ,array(
            'itemtype'  => <xsl:value-of select="@itemtype" /> ));
    $data['authid'] = xarSecGenAuthKey();
    $data['_bl_template'] = '<xsl:value-of select="@name" />';

    return $data;
}
</xsl:template>



<!-- =========================================================================

    MODE: admin_createtable             MATCH:  table

-->
<xsl:template mode="xaritemtypeapi_create" match="table">
    <xsl:variable name="module_prefix" select="../../registry/name" />

/**
 * Create a <xsl:value-of select="@name" /> object
 *
 */
function <xsl:value-of select="$module_prefix" />_<xsl:value-of select="@name" />api_create( $args ) {

    if (!xarSecurityCheck( 'Add<xsl:value-of select="$module_prefix" />')) return;

    list ( $itemtype ) = xarVarCleanFromInput( 'itemtype' );
    extract( $args );

    // Retrieve the object via the dynamicdata module api.
    $object = xarModAPIFunc(
        'dynamicdata'
        ,'user'
        ,'getobject'
        ,array(
            'module'     => '<xsl:value-of select="$module_prefix" />'
            ,'itemtype'  => <xsl:value-of select="@itemtype" />
        ));
    if ( empty($object) ) return;

    // check the input values for this object
    $isvalid = $object->checkInput();

    if ( $isvalid ) {

        /*
         * The object is valid . Create it
         */
        $itemid = $object->createItem();
        if (empty( $itemid) ) return; // throw back

        <xsl:if test="@hooks = 'enable'">// The 'api:create' hook is called from dynamic data during createItem() !</xsl:if>

        $item_title = xarModAPIFunc(
            '<xsl:value-of select="$module_prefix" />'
            ,'user'
            ,'gettitle'
            ,array(
                'object'    =>  $object
                ,'itemtype' =>  $itemtype ));

        xarSessionSetVar(
            '<xsl:value-of select="$module_prefix" />_statusmsg'
            ,'Created <xsl:value-of select="label" /> ' . $itemid .' -> '.  $item_title .'.' );

        // This function generated no output, and so now it is complete we redirect
        // the user to an appropriate page for them to carry on their work
        xarResponseRedirect(
            xarModURL(
                '<xsl:value-of select="$module_prefix" />'
                ,'admin'
                ,'new'
                ,array(
                    'itemtype' => <xsl:value-of select="@itemtype" /> )));

    } else {

        // Back to new
        return <xsl:value-of select="$module_prefix" />_<xsl:value-of select="@name" />api_new( $args );

    }


}
</xsl:template>



<!-- =========================================================================

    MODE: xaritemtypeapi_display               MATCH:  table

-->
<xsl:template mode="xaritemtypeapi_display" match="table">
    <xsl:variable name="module_prefix" select="../../registry/name" />
/**
 * // TODO // add description
 */
function <xsl:value-of select="$module_prefix" />_<xsl:value-of select="@name" />api_display( $args ) {

    // Security check
    if (!xarSecurityCheck( 'View<xsl:value-of select="$module_prefix" />')) return;

    // Get parameter from browser
    list( $itemid ,$itemtype ) = xarVarCleanFromInput( 'itemid', 'itemtype' );
    extract( $args );

    // Overload it with the arguments from the admin interface ( if provided )
    $data = array();
    if ( isset( $object ) ) {

        // We need a itemtype to render things properly.
        if ( empty( $itemtype ) ) return 'please provide a itemtype';

    } else {

        // Load the object and provide all tasks which should only be done
        // when we are not rendering a preview ( Menu, Hooks ... )

        // We are called from a browser. To load a object we need a itemtype
        // and a itemid. If there is itemtype let's go to the main page.
        if ( empty( $itemtype ) ) {
            xarResponseRedirect(
                xarModURL(
                    '<xsl:value-of select="$module_prefix" />'
                    ,'user'
                    ,'main' ));
        }

        // If there is no itemid let's go to the itemtypes overview page.
        if ( empty( $itemid ) ) {
            xarResponseRedirect(
                xarModURL(
                    '<xsl:value-of select="$module_prefix" />'
                    ,'user'
                    ,'view'
                    ,array(
                        'itemtype'  =>  $itemtype )));
        }

        // Retrieve the object
        $object =&amp; xarModAPIFunc(
            '<xsl:value-of select="$module_prefix" />'
            ,'user'
            ,'get'
            ,array(
                 'itemtype'  => $itemtype
                ,'itemid'    => $itemid
            ));
        if ( empty( $object ) ) return;

        $item_title = xarModAPIFunc(
            '<xsl:value-of select="$module_prefix" />'
            ,'user'
            ,'gettitle'
            ,array(
                'object'    =>  $object
                ,'itemtype' =>  $itemtype ));
        $data = xarModAPIFunc(
            '<xsl:value-of select="$module_prefix" />'
            ,'private'
            ,'common'
            ,array(
                'title' => '<xsl:value-of select="label" />' . $item_title
                ,'type' => 'user'
                ));

        <xsl:if test="@hooks = 'enable'">
        /*
         * Call the hook 'item:display:GUI'.
         *
         * The returnurl is for hooked modules which provides a action and
         * want to come back afterwards.
         */
        $args = array(
            'module'        =>  '<xsl:value-of select="$module_prefix" />'
            ,'itemid'       =>  $itemid
            ,'itemtype'     =>  $itemtype
            ,'returnurl'    =>  xarModURL(
                '<xsl:value-of select="$module_prefix" />'
                ,'user'
                ,'display'
                ,array(
                    'itemid'       =>  $itemid
                    ,'itemtype'    =>  $itemtype
                    ))
            );
        $hooks = xarModCallHooks(
            'item'
            ,'display'
            ,$itemid
            ,$args
            ,'<xsl:value-of select="$module_prefix" />' );
        if ( !isset( $hooks ) ) { return; }
        $data['hooks'] = $hooks;
        </xsl:if>
    }

    $data['object_props'] =&amp; $object->getProperties();
    $data['_bl_template'] = '<xsl:value-of select="@name" />';
    $data['itemtype'] = $itemtype;
    $data['itemid']   = $itemid;
    return $data;

   return $data;
}
</xsl:template>


</xsl:stylesheet>
