<!DOCTYPE xsl:stylesheet [
        <!ENTITY nbsp "&#160;">
]>
<xsl:stylesheet version="1.0"
                xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
                xmlns:html="http://www.w3.org/TR/xhtml1/strict"
                xmlns:xar="dd"
                xmlns="http://www.w3.org/TR/xhtml1/strict">


<xsl:template match="xaraya_module" mode="xaradmin_delete">

    <xsl:message>      * xaradmin/delete.php</xsl:message>

    <xsl:document href="{$output}/xaradmin/delete.php" format="text" omit-xml-declaration="yes" ><xsl:processing-instruction name="php">

        <xsl:call-template name="xaraya_standard_php_file_header" select=".">
            <xsl:with-param name="filename">xaradmin/delete.php</xsl:with-param>
        </xsl:call-template>

        <xsl:apply-templates mode="xaradmin_delete_func" select="." />

        <xsl:call-template name="xaraya_standard_php_file_footer" select="." />

    </xsl:processing-instruction></xsl:document>

</xsl:template>


<!-- =========================================================================

    MODE: xaradmin_delete                   MATCH:  xaraya_module

-->
<xsl:template match="xaraya_module" mode="xaradmin_delete_func">
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
function <xsl:value-of select="$module_prefix" />_admin_delete( $args ) 
{
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
                    xarModAPIFunc(
                        '<xsl:value-of select="$module_prefix" />'
                        ,'<xsl:value-of select="@name" />'
                        ,'delete'
                        ,$args );
                    break;
            </xsl:for-each>

                default:
                    xarSessionSetVar(
                        '<xsl:value-of select="$module_prefix" />_statusmsg'
                        ,xarML( 'Unknown itemtype #(1). Redirected you to the main page!', $itemid ) );
                    return xarResponseRedirect(
                        xarModURL(
                            '<xsl:value-of select="$module_prefix" />'
                            ,'admin'
                            ,'main' ));
            }

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
    }


    switch( $itemtype ) {
    <xsl:for-each select="database/table[@admin='true']">
        case <xsl:value-of select="@itemtype" />:
            $data = xarModAPIFunc(
                '<xsl:value-of select="$module_prefix" />'
                ,'<xsl:value-of select="@name" />'
                ,'confirmdelete'
                , $args );
            $itemtype_name = '<xsl:value-of select="@name" />';
            break;
    </xsl:for-each>
        default:
            xarSessionSetVar(
                '<xsl:value-of select="$module_prefix" />_statusmsg'
                ,xarML( 'Unknown itemtype #(1). Redirected you to the main page!', $itemid ) );
            return xarResponseRedirect(
                xarModURL(
                    '<xsl:value-of select="$module_prefix" />'
                    ,'admin'
                    ,'view' ));
    }

    return xarTplModule(
        '<xsl:value-of select="$module_prefix" />'
        ,'admin'
        ,'delete'
        ,$data
        ,$itemtype_name );

}
</xsl:template>


</xsl:stylesheet>
