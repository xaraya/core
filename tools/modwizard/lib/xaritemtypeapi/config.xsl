<!DOCTYPE xsl:stylesheet [
        <!ENTITY nbsp "&#160;">
]>
<xsl:stylesheet version="1.0"
                xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
                xmlns:html="http://www.w3.org/TR/xhtml1/strict"
                xmlns:xar="dd"
                xmlns="http://www.w3.org/TR/xhtml1/strict">


<xsl:template match="table" mode="xaritemtypeapi_config">

    <xsl:variable name="itemtype" select="@name" />

    <xsl:message>      * xar<xsl:value-of select="$itemtype" />api/config.php</xsl:message>

    <xsl:document href="{$output}/xar{$itemtype}api/config.php" format="text" omit-xml-declaration="yes" ><xsl:processing-instruction name="php">

        <xsl:call-template name="xaraya_standard_php_file_header" select=".">
            <xsl:with-param name="filename">xar<xsl:value-of select="$itemtype" />api/config.php</xsl:with-param>
        </xsl:call-template>

        <xsl:apply-templates mode="xaritemtypeapi_config_func" select="." />

        <xsl:call-template name="xaraya_standard_php_file_footer" select="." />

    </xsl:processing-instruction></xsl:document>

</xsl:template>


<!-- ========================================================================

        MODE: xaritemtypeapi_config      MATCH: xaraya_module

-->
<xsl:template mode="xaritemtypeapi_config_func" match="table">
    <xsl:variable name="module_prefix" select="../../registry/name" />
/**
 * Administration for the <xsl:value-of select="$module_prefix" /> module.
 */
function <xsl:value-of select="$module_prefix" />_<xsl:value-of select="@name" />api_config( $args ) 
{
    $data =&amp; xarModAPIFunc(
        '<xsl:value-of select="$module_prefix" />'
        ,'private'
        ,'common'
        ,array(
            'title' => xarML( 'Settings for <xsl:value-of select="label" />' )
            ,'type' => 'admin'
            ));

    list( $authid ) = xarVarCleanFromInput( 'authid' );
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

        xarModVars::set(
            '<xsl:value-of select="$module_prefix" />'
            ,'itemsperpage.' . '<xsl:value-of select="@itemtype" />'
            ,$itemsperpage );

        /*
         * call the hook 'module:updateconfig:GUI'
         */
        $args = array(
            'module'        =>  '<xsl:value-of select="$module_prefix" />'
            ,'itemtype'     =>  <xsl:value-of select="@itemtype" /> );
        $data['hooks'] = xarModCallHooks(
            'module'
            ,'updateconfig'
            ,'<xsl:value-of select="$module_prefix" />'
            ,$args
            ,'<xsl:value-of select="$module_prefix" />' );

        /*
         * Set a status message
         */
        xarSessionSetVar(
            '<xsl:value-of select="$module_prefix" />_statusmsg'
            ,xarML( 'Updated the settings for <xsl:value-of select="label" />!' ));

        /*
         * Finished. Back to the sender!
         */
        xarResponseRedirect(
            xarModURL(
                '<xsl:value-of select="$module_prefix" />'
                ,'admin'
                ,'config'
                ,array(
                    'itemtype' => <xsl:value-of select="@itemtype" /> )));

    } // Save the changes

    /*
     * call the hook 'module:modifyconfig:GUI'
     */
    $args = array(
        'module'        =>  '<xsl:value-of select="$module_prefix" />'
        ,'itemtype'     =>   <xsl:value-of select="@itemtype" /> );
    $data['hooks'] = xarModCallHooks(
        'module'
        ,'modifyconfig'
        ,'<xsl:value-of select="$module_prefix" />'
        ,$args
        ,'<xsl:value-of select="$module_prefix" />' );

    $data['itemtype']       = <xsl:value-of select="@itemtype" />;
    $data['itemtype_label'] = <xsl:value-of select="@itemtype" />;
    $data['itemsperpage']   = xarModVars::Get(
        '<xsl:value-of select="$module_prefix" />'
        ,'itemsperpage.' . '<xsl:value-of select="@itemtype" />' );


    /*
     * Populate the rest of the template
     */
    $data['common']['menu_label'] = xarML( 'Configure' );
    $data['common']['menu']       = xarModAPIFunc(
        '<xsl:value-of select="$module_prefix" />'
        ,'private'
        ,'adminconfigmenu'
        ,<xsl:value-of select="@itemtype" /> );
    $data['action']     = xarModURL( '<xsl:value-of select="$module_prefix" />', 'admin', 'config' );
    $data['authid']     = xarSecGenAuthKey();

    return $data;
}

</xsl:template>


</xsl:stylesheet>
