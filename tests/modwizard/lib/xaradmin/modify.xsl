<!DOCTYPE xsl:stylesheet [
        <!ENTITY nbsp "&#160;">
]>
<xsl:stylesheet version="1.0"
                xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
                xmlns:html="http://www.w3.org/TR/xhtml1/strict"
                xmlns:xar="dd"
                xmlns="http://www.w3.org/TR/xhtml1/strict">


<xsl:template match="xaraya_module" mode="xaradmin_modify">

    <xsl:message>      * xaradmin/modify.php</xsl:message>

    <xsl:document href="{$output}/xaradmin/modify.php" format="text" omit-xml-declaration="yes" ><xsl:processing-instruction name="php">

        <xsl:call-template name="xaraya_standard_php_file_header" select=".">
            <xsl:with-param name="filename">xaradmin/modify.php</xsl:with-param>
        </xsl:call-template>

        <xsl:apply-templates mode="xaradmin_modify_func" select="." />

        <xsl:call-template name="xaraya_standard_php_file_footer" select="." />

    </xsl:processing-instruction></xsl:document>

</xsl:template>



<!-- =========================================================================

    MODE: xaradmin_modify                   MATCH:  xaraya_module

-->
<xsl:template match="xaraya_module" mode="xaradmin_modify_func">
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
            $data = xarModAPIFunc(
                '<xsl:value-of select="$module_prefix" />'
                ,'<xsl:value-of select="@name" />'
                ,'modify'
                ,$args );
            $itemtype_name = '<xsl:value-of select="@name" />';
            break;
    </xsl:for-each>
        default:
            // TODO // Add statusmessage
            return xarResponseRedirect(
                xarModURL(
                    '<xsl:value-of select="$module_prefix" />'
                    ,'admin'
                    ,'view' ));
    }

    return xarTplModule(
        '<xsl:value-of select="$module_prefix" />'
        ,'admin'
        ,'modify'
        ,$data
        ,$itemtype_name );
}
</xsl:template>

</xsl:stylesheet>
