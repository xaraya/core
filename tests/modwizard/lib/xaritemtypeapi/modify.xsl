<!DOCTYPE xsl:stylesheet [
        <!ENTITY nbsp "&#160;">
]>
<xsl:stylesheet version="1.0"
                xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
                xmlns:html="http://www.w3.org/TR/xhtml1/strict"
                xmlns:xar="dd"
                xmlns="http://www.w3.org/TR/xhtml1/strict">


<xsl:template match="table" mode="xaritemtypeapi_modify">

    <xsl:variable name="itemtype" select="@name" />

    <xsl:message>      * <xsl:value-of select="$itemtype" />api/modify.php</xsl:message>

    <xsl:document href="{$output}/xar{$itemtype}api/modify.php" format="text" omit-xml-declaration="yes" ><xsl:processing-instruction name="php">

        <xsl:call-template name="xaraya_standard_php_file_header" select=".">
            <xsl:with-param name="filename">xar<xsl:value-of select="$itemtype" />api/modify.php</xsl:with-param>
        </xsl:call-template>

        <xsl:apply-templates mode="xaritemtypeapi_modify_func" select="." />

        <xsl:call-template name="xaraya_standard_php_file_footer" select="." />

    </xsl:processing-instruction></xsl:document>

</xsl:template>


<!-- =========================================================================

    MODE: xaritemtypeapi_modify               MATCH:  xaraya_module

-->
<xsl:template mode="xaritemtypeapi_modify_func" match="table">
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
</xsl:stylesheet>
