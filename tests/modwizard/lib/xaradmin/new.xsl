<!DOCTYPE xsl:stylesheet [
        <!ENTITY nbsp "&#160;">
]>
<xsl:stylesheet version="1.0"
                xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
                xmlns:html="http://www.w3.org/TR/xhtml1/strict"
                xmlns:xar="dd"
                xmlns="http://www.w3.org/TR/xhtml1/strict">


<xsl:template match="xaraya_module" mode="xaradmin_new">

    <xsl:message>      * xaradmin/new.php</xsl:message>

    <xsl:document href="{$output}/xaradmin/new.php" format="text" omit-xml-declaration="yes" ><xsl:processing-instruction name="php">

        <xsl:call-template name="xaraya_standard_php_file_header" select=".">
            <xsl:with-param name="filename">xaradmin/new.php</xsl:with-param>
        </xsl:call-template>

        <xsl:apply-templates mode="xaradmin_new_func" select="." />

        <xsl:call-template name="xaraya_standard_php_file_footer" select="." />

    </xsl:processing-instruction></xsl:document>

</xsl:template>



<!-- =========================================================================

    MODE: xaradmin_new                      MATCH:  xaraya_module

-->
<xsl:template match="xaraya_module" mode="xaradmin_new_func">
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
