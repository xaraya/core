<!DOCTYPE xsl:stylesheet [
        <!ENTITY nbsp "&#160;">
]>
<xsl:stylesheet version="1.0"
                xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
                xmlns:html="http://www.w3.org/TR/xhtml1/strict"
                xmlns:xar="dd"
                xmlns="http://www.w3.org/TR/xhtml1/strict">


<xsl:template match="table" mode="xaritemtypeapi_new">

    <xsl:variable name="itemtype" select="@name" />

    <xsl:message>      * <xsl:value-of select="$itemtype" />api/new.php</xsl:message>

    <xsl:document href="{$output}/xar{$itemtype}api/new.php" format="text" omit-xml-declaration="yes" ><xsl:processing-instruction name="php">

        <xsl:call-template name="xaraya_standard_php_file_header" select=".">
            <xsl:with-param name="filename">xar<xsl:value-of select="$itemtype" />api/new.php</xsl:with-param>
        </xsl:call-template>

        <xsl:apply-templates mode="xaritemtypeapi_new_func" select="." />

        <xsl:call-template name="xaraya_standard_php_file_footer" select="." />

    </xsl:processing-instruction></xsl:document>

</xsl:template>

<!-- =========================================================================

    MODE: xaritemtypeapi_new             MATCH:

-->
<xsl:template mode="xaritemtypeapi_new_func" match="table">
    <xsl:variable name="module_prefix" select="../../registry/name" />

/**
 * Create a <xsl:value-of select="@name" /> object
 *
 */
function <xsl:value-of select="$module_prefix" />_<xsl:value-of select="@name" />api_new( $args ) {

    if (!xarSecurityCheck( 'Add<xsl:value-of select="$module_prefix" />')) return;

    list ( $authid ) = xarVarCleanFromInput( 'authid' );
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
    return $data;
}
</xsl:template>

</xsl:stylesheet>
