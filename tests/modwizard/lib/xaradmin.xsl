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

    xaradmin.xsl
    ===========

-->

<!-- ENTRY POINT    print out progress and call module template -->
<xsl:template match="/" mode="xaradmin" xml:space="default">
### Generating admin interfaces <xsl:apply-templates mode="xaradmin" select="xaraya_module" />
</xsl:template>



<!-- MODULE POINT

     Create a new file called xaradmin.php.

-->
<xsl:template match="xaraya_module" mode="xaradmin">

    <xsl:document href="{$output}/xaradmin/main.php" format="text" omit-xml-declaration="yes" ><xsl:processing-instruction name="php">

        <xsl:call-template name="xaraya_standard_php_file_header" select=".">
            <xsl:with-param name="filename">xaradmin/view.php</xsl:with-param>
        </xsl:call-template>

        <xsl:apply-templates mode="xaradmin_main" select="." />

        <xsl:call-template name="xaraya_standard_php_file_footer" select="." />

    </xsl:processing-instruction></xsl:document>


    <xsl:document href="{$output}/xaradmin/view.php" format="text" omit-xml-declaration="yes" ><xsl:processing-instruction name="php">

        <xsl:call-template name="xaraya_standard_php_file_header" select=".">
            <xsl:with-param name="filename">xaradmin/view.php</xsl:with-param>
        </xsl:call-template>

        <xsl:apply-templates mode="xaradmin_view" select="." />

        <xsl:call-template name="xaraya_standard_php_file_footer" select="." />

    </xsl:processing-instruction></xsl:document>


    <xsl:document href="{$output}/xaradmin/new.php" format="text" omit-xml-declaration="yes" ><xsl:processing-instruction name="php">

        <xsl:call-template name="xaraya_standard_php_file_header" select=".">
            <xsl:with-param name="filename">xaradmin/view.php</xsl:with-param>
        </xsl:call-template>

        <xsl:apply-templates mode="xaradmin_new" select="." />

        <xsl:call-template name="xaraya_standard_php_file_footer" select="." />

    </xsl:processing-instruction></xsl:document>


    <xsl:document href="{$output}/xaradmin/modify.php" format="text" omit-xml-declaration="yes" ><xsl:processing-instruction name="php">

        <xsl:call-template name="xaraya_standard_php_file_header" select=".">
            <xsl:with-param name="filename">xaradmin/view.php</xsl:with-param>
        </xsl:call-template>

        <xsl:apply-templates mode="xaradmin_modify" select="." />

        <xsl:call-template name="xaraya_standard_php_file_footer" select="." />

    </xsl:processing-instruction></xsl:document>


    <xsl:document href="{$output}/xaradmin/delete.php" format="text" omit-xml-declaration="yes" ><xsl:processing-instruction name="php">

        <xsl:call-template name="xaraya_standard_php_file_header" select=".">
            <xsl:with-param name="filename">xaradmin/view.php</xsl:with-param>
        </xsl:call-template>

        <xsl:apply-templates mode="xaradmin_delete" select="." />

        <xsl:call-template name="xaraya_standard_php_file_footer" select="." />

    </xsl:processing-instruction></xsl:document>


    <xsl:document href="{$output}/xaradmin/config.php" format="text" omit-xml-declaration="yes" ><xsl:processing-instruction name="php">

        <xsl:call-template name="xaraya_standard_php_file_header" select=".">
            <xsl:with-param name="filename">xaradmin/view.php</xsl:with-param>
        </xsl:call-template>

        <xsl:apply-templates mode="xaradmin_config" select="." />

        <xsl:call-template name="xaraya_standard_php_file_footer" select="." />

    </xsl:processing-instruction></xsl:document>


</xsl:template>

<!-- =========================================================================

     TEMPLATE xaradmin_main ( xaraya_module )

     Create the <module>_admin_main() function.

-->
<xsl:template mode="xaradmin_main" match="xaraya_module">

<xsl:variable name="module_prefix" select="registry/name" />
<xsl:if test="$gCommentsLevel >=1">
/*
 * The main ( default ) administration view.
 */
</xsl:if>
function <xsl:value-of select="$module_prefix" />_admin_main() {

    if (!xarSecurityCheck( 'Edit<xsl:value-of select="$module_prefix" />')) return;

    // Check if we should show the overview page <xsl:if test="$gCommentsLevel >=2">
    // The admin system looks for a var to be set to skip the introduction
    // page altogether.  This allows you to add sparse documentation about the
    // module, and allow the site admins to turn it on and off as they see fit. </xsl:if>
    if (xarModGetVar('adminpanels', 'overview') == 0) {

        // Yes we should
        $data = xarModAPIFunc(
            '<xsl:value-of select="$module_prefix" />'
            ,'private'
            ,'common'
            ,array(
                'title' => 'Overview'
                ,'type' => 'admin'
                ));
        return $data;
    }

    // No we shouldn't. So we redirect to the admin_view() function.
    return xarResponseRedirect(
        xarModURL(
            '<xsl:value-of select="registry/name" />'
            ,'admin'
            ,'view' ));

}
</xsl:template>


<!-- =========================================================================

     TEMPLATE xaradmin_view ( xaraya_module )

     Create the <module>_admin_view() function.

-->
<xsl:template mode="xaradmin_view" match="xaraya_module">

<xsl:variable name="module_prefix" select="registry/name" />
<xsl:if test="$gCommentsLevel >=1">
/**
 * Show a overview of all available administration options.
 *
 * This is the main page if the admin 'Disabled Module Overview' in
 * 'adminpanels - configurations - configure overview'.
 */
</xsl:if>
function <xsl:value-of select="$module_prefix" />_admin_view($args) {

    list( $itemtype ) = xarVarCleanFromInput('itemtype' );

    switch( $itemtype ) {
    <xsl:for-each select="database/table[@admin='true']">
        case <xsl:value-of select="@itemtype" />:
            return xarModAPIFunc(
                '<xsl:value-of select="$module_prefix" />'
                ,'<xsl:value-of select="@name" />'
                ,'view' );
    </xsl:for-each>

        default:
            return
                $data = xarModAPIFunc(
                    '<xsl:value-of select="$module_prefix" />'
                    ,'private'
                    ,'common'
                    ,array(
                        'title' => 'Main Page'
                        ,'type' => 'admin'
                        ));
    }
}


</xsl:template>






<!-- ========================================================================

        MODE: xaradmin_config               MATCH: xaraya_module

-->
<xsl:template mode="xaradmin_config" match="xaraya_module">
    <xsl:variable name="module_prefix" select="registry/name" />
/**
 * Administration for the <xsl:value-of select="$module_prefix" /> module.
 */
function <xsl:value-of select="$module_prefix" />_admin_config( $args ) {

    list( $cancel, $itemtype ) = xarVarCleanFromInput( 'cancel', 'itemtype' );
    extract( $args );

    // check if the user selected cancel
    if ( !empty( $cancel ) ) {

        // This function generated no output, and so now it is complete we redirect
        // the user to an appropriate page for them to carry on their work
        return xarResponseRedirect(
            xarModURL(
                '<xsl:value-of select="$module_prefix" />'
                ,'admin'
                ,'config'
                ,array(
                    'itemtype' => $itemtype )));

    }

    switch( $itemtype ) {
    <xsl:for-each select="database/table[@admin='true']">
        case <xsl:value-of select="@itemtype" />:
            return xarModAPIFunc(
                '<xsl:value-of select="$module_prefix" />'
                ,'<xsl:value-of select="@name" />'
                ,'config'
                ,$args );
    </xsl:for-each>

        default:
            return <xsl:value-of select="$module_prefix" />_adminpriv_config( $args );
    }
}

/**
 * Administration for the <xsl:value-of select="$module_prefix" /> module.
 */
function <xsl:value-of select="$module_prefix" />_adminpriv_config( $args ) {

    $data = xarModAPIFunc(
        '<xsl:value-of select="$module_prefix" />'
        ,'private'
        ,'common'
        ,array(
            'title' => 'Module Configuration'
            ,'type' => 'admin'
            ));

    list( $itemtype, $authid ) = xarVarCleanFromInput( 'itemtype', 'authid' );
    extract( $args );

    if ( isset( $authid ) ) {

        /*
         * The user confirmed the form. So save the results.
         */

        if (!xarSecConfirmAuthKey()) return;

        $supportshorturls = xarVarCleanFromInput( 'supportshorturls' );

        if ( empty( $supportshorturls ) or !is_numeric( $supportshorturls ) ) {
            $supportshorturls = 0;
        }

        xarModSetVar(
            '<xsl:value-of select="$module_prefix" />'
            ,'SupportShortURLs'
            ,$supportshorturls );

        <xsl:if test="configuration/hooks/@enable = 'true'">
        /*
         * call the hook 'module:updateconfig:GUI'
         */
        $args = array(
            'module'        =>  '<xsl:value-of select="$module_prefix" />'
            ,'itemtype'     =>  0
            );
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
            ,'Updated the modules configuration!' );

        /*
         * Finished. Back to the sender!
         */
        return xarResponseRedirect(
            xarModURL(
                '<xsl:value-of select="$module_prefix" />'
                ,'admin'
                ,'config'
                ,array(
                    'itemtype' => $itemtype )));

    } // Save the changes

    <xsl:if test="configuration/hooks/@enable = 'true'">
    /*
     * call the hook 'module:modifyconfig:GUI'
     */
    $args = array(
        'module'        =>  '<xsl:value-of select="$module_prefix" />'
        ,'itemtype'     =>  0
        );
    $data['hooks'] = xarModCallHooks(
        'module'
        ,'modifyconfig'
        ,'<xsl:value-of select="$module_prefix" />'
        ,$args
        ,'<xsl:value-of select="$module_prefix" />' );

    </xsl:if>

    $data['common']['menu_label'] = 'Configure';
    $data['common']['menu']       = xarModAPIFunc(
        '<xsl:value-of select="$module_prefix" />'
        ,'private'
        ,'adminconfigmenu' );

    /*
     * Populate the rest of the template
     */
    $data['action']     = xarModURL(
        '<xsl:value-of select="$module_prefix" />'
        ,'admin'
        ,'config' );
    $data['authid']     = xarSecGenAuthKey();
    $data['supportshorturls']   = xarModGetVar(
        '<xsl:value-of select="$module_prefix" />'
        ,'SupportShortURLs' );
    return $data;

}
</xsl:template>





<!-- =========================================================================

    MODE: xaradmin_modify                   MATCH:  xaraya_module

-->
<xsl:template match="xaraya_module" mode="xaradmin_modify">
    <xsl:variable name="module_prefix" select="registry/name" />

/**
 * Standard interface for the modification of objects.
 *
 * We just forward to the appropiate <xsl:value-of select="$module_prefix" />_admin_modify&lt;table&gt;()
 * function.
 * <xsl:if test="$gCommentsLevel >= 20">
 * This function has to deal with some special events.
 *
 * dynamic data
 * ============
 *
 * The dynamic data module calls this function to modify a object. The
 * following informations are provided
 *      'itemtype'  =>  type of the object to delete
 *      'itemid'    =>  id of the item to delete
 * </xsl:if>
 */
function <xsl:value-of select="$module_prefix" />_admin_modify( $args ) {

    list( $itemtype, $itemid, $cancel, $authid, $preview ) =
        xarVarCleanFromInput('itemtype', 'itemid', 'cancel', 'authid', 'preview' );
    extract( $args );

    /*
     * Return to the itemtype's view page if
     *  -> If the user decided to cancel the action
     *  -> There is no itemid to modify
     *  -> There is no itemtype ( will go to main view )
     */
    if ( !empty( $cancel ) or empty( $itemid ) or empty( $itemtype ) ) {

        // This function generated no output, and so now it is complete we redirect
        // the user to an appropriate page for them to carry on their work
        return xarResponseRedirect(
            xarModURL(
                '<xsl:value-of select="$module_prefix" />'
                ,'admin'
                ,'view'
                ,array(
                    'itemtype' => $itemtype )));

    }

    // check if authid is set.
    if ( isset( $authid ) ) {

        // Confirm the authorization key
        if (!xarSecConfirmAuthKey()) return;

        // Check if a preview is wished
        if ( !isset( $preview ) ) {

            switch( $itemtype ) {
            <xsl:for-each select="database/table[@admin='true']">
                case <xsl:value-of select="@itemtype" />:
                    return xarModAPIFunc(
                        '<xsl:value-of select="$module_prefix" />'
                        ,'<xsl:value-of select="@name" />'
                        ,'update'
                        ,$args );
            </xsl:for-each>
                default:
                    // TODO // Add statusmessage
                    return xarResponseRedirect(
                        xarModURL(
                            '<xsl:value-of select="$module_prefix" />'
                            ,'admin'
                            ,'view' ));
            }
        }
    }

    switch( $itemtype ) {
    <xsl:for-each select="database/table[@admin='true']">
        case <xsl:value-of select="@itemtype" />:
            return xarModAPIFunc(
                '<xsl:value-of select="$module_prefix" />'
                ,'<xsl:value-of select="@name" />'
                ,'modify'
                ,$args );
    </xsl:for-each>
        default:
            // TODO // Add statusmessage
            return xarResponseRedirect(
                xarModURL(
                    '<xsl:value-of select="$module_prefix" />'
                    ,'admin'
                    ,'view' ));
    }

}
</xsl:template>




<!-- =========================================================================

    MODE: xaradmin_delete                   MATCH:  xaraya_module

-->
<xsl:template match="xaraya_module" mode="xaradmin_delete">
    <xsl:variable name="module_prefix" select="registry/name" />

/**
 * Standard interface for the deletion of objects.
 *
 * We just forward to the appropiate <xsl:value-of select="$module_prefix" />_adminapi_delete&lt;table&gt;()
 * function.
 * <xsl:if test="$gCommentsLevel >= 20">
 * This function has to deal with some special events.
 *
 * dynamic data
 * ============
 *
 * The dynamic data module calls this function to delete a object. The
 * following informations are provided
 *      'itemtype'  =>  type of the object to delete
 *      'itemid'    =>  id of the item to delete
 * </xsl:if>
 */
function <xsl:value-of select="$module_prefix" />_admin_delete( $args ) {

    list( $authid, $confirm, $itemtype, $cancel, $itemid ) =
        xarVarCleanFromInput( 'authid', 'confirm', 'itemtype', 'cancel', 'itemid' );
    extract( $args );

    /*
     * Return to the itemtype's view page if
     *  -> If the user decided to cancel the action
     *  -> There is no itemid to delete
     *  -> There os no itemtype ( will go to main view )
     */
    if ( !empty( $cancel ) or empty( $itemid ) or empty( $itemtype ) ) {

        // This function generated no output, and so now it is complete we redirect
        // the user to an appropriate page for them to carry on their work
        return xarResponseRedirect(
            xarModURL(
                '<xsl:value-of select="$module_prefix" />'
                ,'admin'
                ,'view'
                ,array(
                    'itemtype' => $itemtype )));

    }

    // These function is called under different contexts.
    // 1. first time ( authid is not set )
    // 2. confirm    ( authid is set )
    if ( isset( $authid ) ) {

        // Confirm the authorization key
        if (!xarSecConfirmAuthKey()) return;

        // Check if the user selected Delete
        if ( isset( $confirm ) ) {

            switch( $itemtype ) {
            <xsl:for-each select="database/table[@admin='true']">
                case <xsl:value-of select="@itemtype" />:
                    return xarModAPIFunc(
                        '<xsl:value-of select="$module_prefix" />'
                        ,'<xsl:value-of select="@name" />'
                        ,'delete'
                        ,$args );
            </xsl:for-each>

                default:
                    // TODO // Add statusmessage
                    return xarResponseRedirect(
                        xarModURL(
                            '<xsl:value-of select="$module_prefix" />'
                            ,'admin'
                            ,'view' ));
            }

        }
    }

    switch( $itemtype ) {
    <xsl:for-each select="database/table[@admin='true']">
        case <xsl:value-of select="@itemtype" />:
            return xarModAPIFunc(
                '<xsl:value-of select="$module_prefix" />'
                ,'<xsl:value-of select="@name" />'
                ,'confirmdelete'
                , $args );
    </xsl:for-each>
        default:
            // TODO // Add statusmessage
            return xarResponseRedirect(
                xarModURL(
                    '<xsl:value-of select="$module_prefix" />'
                    ,'admin'
                    ,'view' ));
    }
}
</xsl:template>




<!-- =========================================================================

    MODE: xaradmin_new                      MATCH:  xaraya_module

-->
<xsl:template match="xaraya_module" mode="xaradmin_new">
    <xsl:variable name="module_prefix" select="registry/name" />

/**
 * Standard interface for the creation of objects.
 *
 * We just forward to the appropiate <xsl:value-of select="$module_prefix" />_adminapi_create&lt;table&gt;()
 * function.
 * <xsl:if test="$gCommentsLevel >= 20">
 * This function has to deal with some special events.
 *
 * dynamic data
 * ============
 *
 * The dynamic data module calls this function to create a object. The
 * following informations are provided in then.
 *      'itemtype'  =>  type of the object to create
 * </xsl:if>
 */
function <xsl:value-of select="$module_prefix" />_admin_new( $args ) {

    list( $authid, $preview, $itemtype, $cancel ) =
        xarVarCleanFromInput( 'authid', 'preview', 'itemtype', 'cancel' );
    extract( $args );

    /*
     * Return to the itemtype's view page if
     *  -> If the user decided to cancel the action
     *  -> There is no itemtype ( will go to main view )
     */
    if ( !empty( $cancel ) or empty( $itemtype ) ) {

        // This function generated no output, and so now it is complete we redirect
        // the user to an appropriate page for them to carry on their work
        return xarResponseRedirect(
            xarModURL(
                '<xsl:value-of select="$module_prefix" />'
                ,'admin'
                ,'view'
                ,array(
                    'itemtype' => $itemtype )));

    }

    // These function is called under different contexts.
    // 1. first time ( authid is not set )
    // 2. preview    ( authid is set, preview is set )
    // 3. Submit     ( authid is set )
    if ( isset( $authid ) ) {

        // Confirm the authorization key
        if (!xarSecConfirmAuthKey()) return;

        if ( empty($preview) ) {

            switch( $itemtype ) {
            <xsl:for-each select="database/table[@admin='true']">
                case <xsl:value-of select="@itemtype" />:
                    return xarModAPIFunc(
                        '<xsl:value-of select="$module_prefix" />'
                        ,'<xsl:value-of select="@name" />'
                        ,'create'
                        ,$args );
            </xsl:for-each>
                default:
                    // TODO // Add statusmessage
                    return xarResponseRedirect(
                        xarModURL(
                            '<xsl:value-of select="$module_prefix" />'
                            ,'admin'
                            ,'view' ));
            }
        }

    }

    switch( $itemtype ) {
    <xsl:for-each select="database/table[@admin='true']">
        case <xsl:value-of select="@itemtype" />:
            return xarModAPIFunc(
                '<xsl:value-of select="$module_prefix" />'
                ,'<xsl:value-of select="@name" />'
                ,'new'
                ,$args );
    </xsl:for-each>
        default:
            // TODO // Add statusmessage
            return xarResponseRedirect(
                xarModURL(
                    '<xsl:value-of select="$module_prefix" />'
                    ,'admin'
                    ,'view' ));
    }
}
</xsl:template>

</xsl:stylesheet>
