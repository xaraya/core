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
    generating xaradmin.php ... <xsl:apply-templates mode="xaradmin" select="xaraya_module" /> ... finished
</xsl:template>



<!-- MODULE POINT

     Create a new file called xaradmin.php.

-->
<xsl:template match="xaraya_module" mode="xaradmin">
<xsl:document href="{$output}/xaradmin.php" format="text" omit-xml-declaration="yes" ><xsl:processing-instruction name="php">

    <!-- call template for file header -->
    <xsl:call-template name="xaraya_standard_php_file_header" select=".">
        <xsl:with-param name="filename">xaradmin.php</xsl:with-param>
    </xsl:call-template>

    <!-- call template for module_admin_main() function -->
    <xsl:apply-templates mode="xaradmin_main" select="." />

    <!-- call template for module_admin_view() function -->
    <xsl:apply-templates mode="xaradmin_view" select="." />

    <!-- call template for module_admin_common() function -->
    <xsl:apply-templates mode="xaradmin_common" select="." />

    <!-- call template for module_admin_new() function -->
    <xsl:apply-templates mode="xaradmin_new" select="." />

    <!-- call template for module_admin_modify() function -->
    <xsl:apply-templates mode="xaradmin_modify" select="." />

    <!-- call template for module_admin_delete() function -->
    <xsl:apply-templates mode="xaradmin_delete" select="." />

    <!-- call template for module_admin_config() function -->
    <xsl:apply-templates mode="xaradmin_config" select="." />

    <xsl:apply-templates mode="xaradminpriv_configmenu" select="." />

    <!-- call template for file footer -->
    <xsl:call-template name="xaraya_standard_php_file_footer" select="." />

</xsl:processing-instruction></xsl:document>
</xsl:template>


<!-- ========================================================================

        MODE: xaradmin_common               MATCH: xaraya_module

-->
<xsl:template mode="xaradmin_common" match="xaraya_module">
    <xsl:variable name="module_prefix" select="registry/name" />
/**
 * This function provides information to the templates which are common to all
 * pageviews.
 *
 * It provides the following informations:
 *
 *      'menu'      => Array with information about the module menu
 *      'statusmsg' => Status message if set
 */
function <xsl:value-of select="$module_prefix" />_admin_common( $title = 'Undefined' ) {

    $common = array();

    $common['menu'] = array();

    // Initialize the statusmessage
    $statusmsg = xarSessionGetVar( '<xsl:value-of select="$module_prefix" />_statusmsg' );
    if ( isset( $statusmsg ) ) {
        xarSessionDelVar( '<xsl:value-of select="$module_prefix" />_statusmsg' );
        $common['statusmsg'] = $statusmsg;
    }

    <xsl:if test="not( boolean( configuration/capabilities/setpagetitle ) ) or configuration/capabilities/setpagetitle/text() = 'yes'">
    // Set the page title
    xarTplSetPageTitle( '<xsl:value-of select="$module_prefix" /> :: ' . $title );
    </xsl:if>

    // Initialize the title
    $common['pagetitle'] = $title;
    $common['type']      = '<xsl:value-of select="about/name" /> Administration';

    return array( 'common' => $common );
}
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
        $data = <xsl:value-of select="$module_prefix" />_admin_common( 'Overview' );
        return $data;

    }

    // No we shouldn't. So we redirect to the admin_view() function.
    xarResponseRedirect(
        xarModURL(
            '<xsl:value-of select="registry/name" />'
            ,'admin'
            ,'view' ));
    return true;

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
            return <xsl:value-of select="$module_prefix" />_admin_common('Main Page'); }
}


</xsl:template>



<!-- ========================================================================

        MODE: xaradminpriv_configmenu             MATCH: xaraya_module

-->
<xsl:template mode="xaradminpriv_configmenu" match="xaraya_module">
    <xsl:variable name="module_prefix" select="registry/name" />
/**
 * Create a little submenu for the configuration screen.
 */
function <xsl:value-of select="$module_prefix" />_adminpriv_configmenu() {

    /*
     * Build the configuration submenu
     */
    $menu = array(
        array(
            'label' =>  'Config',
            'url'   =>  xarModURL(
                '<xsl:value-of select="$module_prefix" />',
                'admin',
                'config' )));

    <xsl:for-each select="database/table[@admin='true']">
    $menu[] = array(
            'label' =>  '<xsl:value-of select="label/text()" />',
            'url'   =>  xarModURL(
                '<xsl:value-of select="$module_prefix" />',
                'admin',
                'config'
                ,array( 'itemtype' => '<xsl:value-of select="@itemtype" />' )));
    </xsl:for-each>

    return $menu;

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
        xarResponseRedirect(
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

    $data = <xsl:value-of select="$module_prefix" />_admin_common( 'Module Configuration' );

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
        xarResponseRedirect(
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
    $data['common']['menu']       = <xsl:value-of select="$module_prefix" />_adminpriv_configmenu();


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
        xarResponseRedirect(
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
                    xarResponseRedirect(
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
            xarResponseRedirect(
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
        xarResponseRedirect(
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
                    xarResponseRedirect(
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
            xarResponseRedirect(
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
        xarResponseRedirect(
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
                    xarResponseRedirect(
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
            xarResponseRedirect(
                xarModURL(
                    '<xsl:value-of select="$module_prefix" />'
                    ,'admin'
                    ,'view' ));
    }
}
</xsl:template>

</xsl:stylesheet>
