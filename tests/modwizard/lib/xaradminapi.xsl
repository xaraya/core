<!--

    XARAYA MODULE WIZARD

    COPYRIGHT:      Michael Jansen
    CONTACT:        xaraya-module-wizard@schneelocke.de
    LICENSE:        GPL

-->
<xsl:stylesheet version="1.0"
                xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
                xmlns="http://www.w3.org/TR/xhtml1/strict">

<!--

    xaradminapi.xsl
    ===========

-->

<!-- ENTRY POINT    print out progress and call module template -->
<xsl:template match="/" mode="xaradminapi" xml:space="default">
    generating xaradminapi.php ... <xsl:apply-templates mode="xaradminapi" select="xaraya_module" /> ... finished
</xsl:template>



<!-- MODULE POINT

     Create a new file called xaradminapi.php.

-->
<xsl:template match="xaraya_module" mode="xaradminapi">
<xsl:document href="{$output}/xaradminapi.php" format="text" omit-xml-declaration="yes" ><xsl:processing-instruction name="php">

    <!-- call template for file header -->
    <xsl:call-template name="xaraya_standard_php_file_header" select=".">
        <xsl:with-param name="filename">xaradminapi.php</xsl:with-param>
    </xsl:call-template>

    <xsl:apply-templates select="." mode="xaradminapi_getmenulinks" />

    <xsl:for-each select="database/table">

        <!-- NOTHING TO DO -->

    </xsl:for-each>

    <!-- call template for file footer -->
    <xsl:call-template name="xaraya_standard_php_file_footer" select="." />

</xsl:processing-instruction></xsl:document>
</xsl:template>


<!-- ========================================================================

        MODE: xaradminapi_getmenulinks      MATCH: xaraya_module

-->
<xsl:template match="xaraya_module" mode="xaradminapi_getmenulinks">

<xsl:variable name="module_prefix" select="registry/name" />
<xsl:if test="$gCommentsLevel >= 1">
/**
 * Pass individual menu items to the main menu
 *
 * @returns array
 * @return  array containing the menulinks for the main menu items
 */
</xsl:if>
function <xsl:value-of select="$module_prefix" />_adminapi_getmenulinks ( $args ) {

    if (xarSecurityCheck('View<xsl:value-of select="$module_prefix" />')) {

        $menulinks[] = array(
            'url'       => xarModURL(
                '<xsl:value-of select="$module_prefix" />'
                ,'admin'
                ,'main' )
            ,'title'    => 'Show informations'
            ,'label'    => 'Overview' );

        $menulinks[] = array(
            'url'       => xarModURL( '<xsl:value-of select="$module_prefix" />', 'admin', 'view')
            ,'title'    => 'Show the main page'
            ,'label'    => 'Main Page' );

        <xsl:if test="$gCommentsLevel >= 2">
        // The main menu will look for this array and return it for a tree
        // view of the module. We are just looking for three items in the
        // array, the url, which we need to use the xarModURL function, the
        // title of the link, which will display a tool tip for the module
        // url, in order to keep the label short, and finally the exact label
        // for the function that we are displaying.
        </xsl:if>
        <xsl:for-each select="database/table[@admin='true']">
        $menulinks[] = array(
            'url'       => xarModURL(
                '<xsl:value-of select="$module_prefix" />'
                ,'admin'
                ,'view'
                ,array(
                    'itemtype'  => <xsl:value-of select="@itemtype" /> ))
            ,'title'    => 'Administration view of <xsl:value-of select="label" />'
            ,'label'    => 'View <xsl:value-of select="label" />' );
        </xsl:for-each>

        $menulinks[] = array(
            'url'       => xarModURL(
                '<xsl:value-of select="$module_prefix" />'
                ,'admin'
                ,'config' )
            ,'title'    => 'Modify the configuration'
            ,'label'    => 'Modify Config' );

    }


    if (empty($menulinks)){
        $menulinks = '';
    }

    // The final thing that we need to do in this function is return the values back
    // to the main menu for display.
    return $menulinks;

}
</xsl:template>

</xsl:stylesheet>
