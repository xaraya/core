<!DOCTYPE xsl:stylesheet [
        <!ENTITY nbsp "&#160;">
]>
<xsl:stylesheet version="1.0"
                xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
                xmlns:html="http://www.w3.org/TR/xhtml1/strict"
                xmlns:xar="dd"
                xmlns="http://www.w3.org/TR/xhtml1/strict">


<xsl:template match="xaraya_module" mode="xaradmin_config">

    <xsl:message>      * xaradmin/config.php</xsl:message>

    <xsl:document href="{$output}/xaradmin/config.php" format="text" omit-xml-declaration="yes" ><xsl:processing-instruction name="php">

        <xsl:call-template name="xaraya_standard_php_file_header" select=".">
            <xsl:with-param name="filename">xaradmin/config.php</xsl:with-param>
        </xsl:call-template>

        <xsl:apply-templates mode="xaradmin_config_func" select="." />

        <xsl:call-template name="xaraya_standard_php_file_footer" select="." />

    </xsl:processing-instruction></xsl:document>

</xsl:template>



<!-- ========================================================================

        MODE: xaradmin_config               MATCH: xaraya_module

-->
<xsl:template mode="xaradmin_config_func" match="xaraya_module">
    <xsl:variable name="module_prefix" select="registry/name" />
/**
 * Administration for the <xsl:value-of select="$module_prefix" /> module.
 */
function <xsl:value-of select="$module_prefix" />_admin_config( $args ) 
{
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
            $data = xarModAPIFunc(
                '<xsl:value-of select="$module_prefix" />'
                ,'<xsl:value-of select="@name" />'
                ,'config'
                ,$args );
            $itemtype_name = '<xsl:value-of select="@name" />';
            break;
    </xsl:for-each>

        default:
            return <xsl:value-of select="$module_prefix" />_adminpriv_config( $args );
    }

    return xarTplModule(
        '<xsl:value-of select="$module_prefix" />'
        ,'admin'
        ,'config'
        ,$data
        ,$itemtype_name );
}

/**
 * Administration for the <xsl:value-of select="$module_prefix" /> module.
 */
function <xsl:value-of select="$module_prefix" />_adminpriv_config( $args ) 
{
    $data = xarModAPIFunc(
        '<xsl:value-of select="$module_prefix" />'
        ,'private'
        ,'common'
        ,array(
            'title' => xarML( 'Global Settings' )
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
            ,xarML( 'Updated the global module settings!' ) );

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

    $data['common']['menu_label'] = xarML( 'Configure' );
    $data['common']['menu']       = xarModAPIFunc(
        '<xsl:value-of select="$module_prefix" />'
        ,'private'
        ,'adminconfigmenu'
        ,0 );

    /*
     * Populate the rest of the template
     */
    $data['action']     = xarModURL(
        '<xsl:value-of select="$module_prefix" />'
        ,'admin'
        ,'config' );
    $data['authid']     = xarSecGenAuthKey();
    $data['supportshorturls']   = xarModVars::Get(
        '<xsl:value-of select="$module_prefix" />'
        ,'SupportShortURLs' );
    return $data;

}
</xsl:template>

</xsl:stylesheet>
