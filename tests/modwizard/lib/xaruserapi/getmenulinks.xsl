<!DOCTYPE xsl:stylesheet [
        <!ENTITY nbsp "&#160;">
]>
<xsl:stylesheet version="1.0"
                xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
                xmlns:html="http://www.w3.org/TR/xhtml1/strict"
                xmlns:xar="dd"
                xmlns="http://www.w3.org/TR/xhtml1/strict">


<xsl:template match="xaraya_module" mode="xaruserapi_getmenulinks">

    <xsl:message>      * xaruserapi/getmenulinks.php</xsl:message>

    <xsl:document href="{$output}/xaruserapi/getmenulinks.php" format="text" omit-xml-declaration="yes" ><xsl:processing-instruction name="php">

        <xsl:call-template name="xaraya_standard_php_file_header" select=".">
            <xsl:with-param name="filename">xaruserapi/getmenulinks.php</xsl:with-param>
        </xsl:call-template>

        <xsl:apply-templates mode="xaruserapi_getmenulinks_func" select="." />

        <xsl:call-template name="xaraya_standard_php_file_footer" select="." />

    </xsl:processing-instruction></xsl:document>

</xsl:template>

<!-- =========================================================================
     TEMPLATE FOR <module>_userapi_getmenulinks()
-->
<xsl:template match="xaraya_module" mode="xaruserapi_getmenulinks_func">

<xsl:variable name="module_prefix" select="registry/name" />
<xsl:if test="$gCommentsLevel >= 1">
/**
 * Utility function to pass individual menu items to the main menu.
 *
 * This function is invoked by the core to retrieve the items for the
 * usermenu.
 *
 * @returns array
 * @return  array containing the menulinks for the main menu items
 */
</xsl:if>
function <xsl:value-of select="$module_prefix" />_userapi_getmenulinks ( $args ) {

    <xsl:if test="$gCommentsLevel >= 2">
    // First we need to do a security check to ensure that we only return menu items
    // that we are suppose to see.  It will be important to add for each menu item that
    // you want to filter.  No sense in someone seeing a menu link that they have no access
    // to edit.  Notice that we are checking to see that the user has permissions, and
    // not that he/she doesn't.
    </xsl:if>

    if (xarSecurityCheck('View<xsl:value-of select="$module_prefix" />')) {
        <xsl:if test="$gCommentsLevel >= 2">
        // The main menu will look for this array and return it for a tree
        // view of the module. We are just looking for three items in the
        // array, the url, which we need to use the xarModURL function, the
        // title of the link, which will display a tool tip for the module
        // url, in order to keep the label short, and finally the exact label
        // for the function that we are displaying.
        </xsl:if>
        <xsl:for-each select="database/table[@user='true']">
        $menulinks[] = array(
            'url'       => xarModURL(
                '<xsl:value-of select="$module_prefix" />'
                ,'user'
                ,'view'
                ,array(
                    'itemtype' => <xsl:value-of select="@itemtype" /> ))
            ,'title'    => 'Look at the <xsl:value-of select="label" />'
            ,'label'    => 'View <xsl:value-of select="label" />' );
        </xsl:for-each>

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
