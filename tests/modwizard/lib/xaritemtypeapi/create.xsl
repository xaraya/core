<!DOCTYPE xsl:stylesheet [
        <!ENTITY nbsp "&#160;">
]>
<xsl:stylesheet version="1.0"
                xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
                xmlns:html="http://www.w3.org/TR/xhtml1/strict"
                xmlns:xar="dd"
                xmlns="http://www.w3.org/TR/xhtml1/strict">


<xsl:template match="table" mode="xaritemtypeapi_create">

    <xsl:variable name="itemtype" select="@name" />

    <xsl:message>      * <xsl:value-of select="$itemtype" />api/create.php</xsl:message>

    <xsl:document href="{$output}/xar{$itemtype}api/create.php" format="text" omit-xml-declaration="yes" ><xsl:processing-instruction name="php">

        <xsl:call-template name="xaraya_standard_php_file_header" select=".">
            <xsl:with-param name="filename">xar<xsl:value-of select="$itemtype" />api/create.php</xsl:with-param>
        </xsl:call-template>

        <xsl:apply-templates mode="xaritemtypeapi_create_func" select="." />

        <xsl:call-template name="xaraya_standard_php_file_footer" select="." />

    </xsl:processing-instruction></xsl:document>

</xsl:template>

<!-- =========================================================================

    MODE: admin_createtable             MATCH:  table

-->
<xsl:template mode="xaritemtypeapi_create_func" match="table">
    <xsl:variable name="module_prefix" select="../../registry/name" />

/**
 * Create a <xsl:value-of select="@name" /> object
 *
 */
function <xsl:value-of select="$module_prefix" />_<xsl:value-of select="@name" />api_create( $args ) {

    if (!xarSecurityCheck( 'Add<xsl:value-of select="$module_prefix" />')) return;

    list ( $itemtype ) = xarVarCleanFromInput( 'itemtype' );
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

    // check the input values for this object
    $isvalid = $object->checkInput();

    if ( $isvalid ) {

        /*
         * The object is valid . Create it
         */
        $itemid = $object->createItem();
        if (empty( $itemid) ) return; // throw back

        <xsl:if test="@hooks = 'enable'">// The 'api:create' hook is called from dynamic data during createItem() !</xsl:if>

        $item_title = xarModAPIFunc(
            '<xsl:value-of select="$module_prefix" />'
            ,'user'
            ,'gettitle'
            ,array(
                'object'    =>  $object
                ,'itemtype' =>  $itemtype ));

        xarSessionSetVar(
            '<xsl:value-of select="$module_prefix" />_statusmsg'
            ,'Created <xsl:value-of select="label" /> ' . $itemid .' -> '.  $item_title .'.' );

        // This function generated no output, and so now it is complete we redirect
        // the user to an appropriate page for them to carry on their work
        xarResponseRedirect(
            xarModURL(
                '<xsl:value-of select="$module_prefix" />'
                ,'admin'
                ,'new'
                ,array(
                    'itemtype' => <xsl:value-of select="@itemtype" /> )));

    } else {

        // Back to new
        return <xsl:value-of select="$module_prefix" />_<xsl:value-of select="@name" />api_new( $args );

    }


}
</xsl:template>

</xsl:stylesheet>
