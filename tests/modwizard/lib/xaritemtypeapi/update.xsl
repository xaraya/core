<!DOCTYPE xsl:stylesheet [
        <!ENTITY nbsp "&#160;">
]>
<xsl:stylesheet version="1.0"
                xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
                xmlns:html="http://www.w3.org/TR/xhtml1/strict"
                xmlns:xar="dd"
                xmlns="http://www.w3.org/TR/xhtml1/strict">


<xsl:template match="table" mode="xaritemtypeapi_update">

    <xsl:variable name="itemtype" select="@name" />

    <xsl:message>      * xar<xsl:value-of select="$itemtype" />api/update.php</xsl:message>

    <xsl:document href="{$output}/xar{$itemtype}api/update.php" format="text" omit-xml-declaration="yes" ><xsl:processing-instruction name="php">

        <xsl:call-template name="xaraya_standard_php_file_header" select=".">
            <xsl:with-param name="filename">xar<xsl:value-of select="$itemtype" />api/update.php</xsl:with-param>
        </xsl:call-template>

        <xsl:apply-templates mode="xaritemtypeapi_update_func" select="." />

        <xsl:call-template name="xaraya_standard_php_file_footer" select="." />

    </xsl:processing-instruction></xsl:document>

</xsl:template>


<!-- =========================================================================

MODE: xaritemtypeapi_update               MATCH:  xaraya_module

-->
<xsl:template mode="xaritemtypeapi_update_func" match="table">
<xsl:variable name="module_prefix" select="../../registry/name" />

/**
* Update a <xsl:value-of select="@name" /> object
*/
function <xsl:value-of select="$module_prefix" />_<xsl:value-of select="@name" />api_update( $args ) 
{
    if (!xarSecurityCheck( 'Edit<xsl:value-of select="$module_prefix" />')) return;

    list( $itemid) = xarVarCleanFromInput( 'itemid' );
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

        /*
         * Compose the statusmessage
         */
        $item_title = xarModAPIFunc(
            '<xsl:value-of select="$module_prefix" />'
            ,'user'
            ,'gettitle'
            ,array(
                'object'    =>  $object
                ,'itemtype' =>  <xsl:value-of select="@itemtype" /> ));

        xarSessionSetVar(
            '<xsl:value-of select="$module_prefix" />_statusmsg'
            ,xarML( 'Modified <xsl:value-of select="label" /> [#(1)] - "#(2)"', $itemid, $item_title  ) );

        /*
         * This function generated no output, and so now it is complete we redirect
         * the user to an appropriate page for them to carry on their work
         */
        xarResponseRedirect(
            xarModURL(
                '<xsl:value-of select="$module_prefix" />'
                ,'admin'
                ,'view'
                ,array(
                    'itemtype'  => <xsl:value-of select="@itemtype" /> )));

    } else {

        // Back to modify
        return <xsl:value-of select="$module_prefix" />_<xsl:value-of select="@name" />api_modify<xsl:value-of select="@name" />($args);

    }
}
</xsl:template>

</xsl:stylesheet>
