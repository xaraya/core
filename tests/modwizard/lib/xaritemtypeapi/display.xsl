<!DOCTYPE xsl:stylesheet [
        <!ENTITY nbsp "&#160;">
]>
<xsl:stylesheet version="1.0"
                xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
                xmlns:html="http://www.w3.org/TR/xhtml1/strict"
                xmlns:xar="dd"
                xmlns="http://www.w3.org/TR/xhtml1/strict">


<xsl:template match="table" mode="xaritemtypeapi_display">

    <xsl:variable name="itemtype" select="@name" />

    <xsl:message>      * <xsl:value-of select="$itemtype" />api/display.php</xsl:message>

    <xsl:document href="{$output}/xar{$itemtype}api/display.php" format="text" omit-xml-declaration="yes" ><xsl:processing-instruction name="php">

        <xsl:call-template name="xaraya_standard_php_file_header" select=".">
            <xsl:with-param name="filename">xar<xsl:value-of select="$itemtype" />api/display.php</xsl:with-param>
        </xsl:call-template>

        <xsl:apply-templates mode="xaritemtypeapi_display_func" select="." />

        <xsl:call-template name="xaraya_standard_php_file_footer" select="." />

    </xsl:processing-instruction></xsl:document>

</xsl:template>


<!-- =========================================================================

    MODE: xaritemtypeapi_display               MATCH:  table

-->
<xsl:template mode="xaritemtypeapi_display_func" match="table">
    <xsl:variable name="module_prefix" select="../../registry/name" />
/**
 * // TODO // add description
 */
function <xsl:value-of select="$module_prefix" />_<xsl:value-of select="@name" />api_display( $args ) {

    // Security check
    if (!xarSecurityCheck( 'View<xsl:value-of select="$module_prefix" />')) return;

    // Get parameter from browser
    list( $itemid ,$itemtype ) = xarVarCleanFromInput( 'itemid', 'itemtype' );
    extract( $args );

    // Overload it with the arguments from the admin interface ( if provided )
    $data = array();
    if ( isset( $object ) ) {

        // We need a itemtype to render things properly.
        if ( empty( $itemtype ) ) return 'please provide a itemtype';

    } else {

        // Load the object and provide all tasks which should only be done
        // when we are not rendering a preview ( Menu, Hooks ... )

        // We are called from a browser. To load a object we need a itemtype
        // and a itemid. If there is itemtype let's go to the main page.
        if ( empty( $itemtype ) ) {
            xarResponseRedirect(
                xarModURL(
                    '<xsl:value-of select="$module_prefix" />'
                    ,'user'
                    ,'main' ));
        }

        // If there is no itemid let's go to the itemtypes overview page.
        if ( empty( $itemid ) ) {
            xarResponseRedirect(
                xarModURL(
                    '<xsl:value-of select="$module_prefix" />'
                    ,'user'
                    ,'view'
                    ,array(
                        'itemtype'  =>  $itemtype )));
        }

        // Retrieve the object
        $object =&amp; xarModAPIFunc(
            '<xsl:value-of select="$module_prefix" />'
            ,'user'
            ,'get'
            ,array(
                 'itemtype'  => $itemtype
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
                'title' => '<xsl:value-of select="label" />' . $item_title
                ,'type' => 'user'
                ));

        <xsl:if test="@hooks = 'enable'">
        /*
         * Call the hook 'item:display:GUI'.
         *
         * The returnurl is for hooked modules which provides a action and
         * want to come back afterwards.
         */
        $args = array(
            'module'        =>  '<xsl:value-of select="$module_prefix" />'
            ,'itemid'       =>  $itemid
            ,'itemtype'     =>  $itemtype
            ,'returnurl'    =>  xarModURL(
                '<xsl:value-of select="$module_prefix" />'
                ,'user'
                ,'display'
                ,array(
                    'itemid'       =>  $itemid
                    ,'itemtype'    =>  $itemtype
                    ))
            );
        $hooks = xarModCallHooks(
            'item'
            ,'display'
            ,$itemid
            ,$args
            ,'<xsl:value-of select="$module_prefix" />' );
        if ( !isset( $hooks ) ) { return; }
        $data['hooks'] = $hooks;
        </xsl:if>
    }

    $data['object_props'] =&amp; $object->getProperties();
    $data['_bl_template'] = '<xsl:value-of select="@name" />';
    $data['itemtype'] = $itemtype;
    $data['itemid']   = $itemid;
    return $data;

   return $data;
}
</xsl:template>

</xsl:stylesheet>
