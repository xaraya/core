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

    xaruser.xsl
    ===========

-->

<!-- ENTRY POINT    print out progress and call module template -->
<xsl:template match="/" mode="xaruser" xml:space="default">
    generating xaruser.php ... <xsl:apply-templates mode="xaruser" select="xaraya_module" /> ... finished
</xsl:template>



<!-- MODULE POINT

     Create a new file called xaruser.php.

-->
<xsl:template match="xaraya_module" mode="xaruser">
<xsl:document href="{$output}/xaruser.php" format="text" omit-xml-declaration="yes" ><xsl:processing-instruction name="php">

    <!-- call template for file header -->
    <xsl:call-template name="xaraya_standard_php_file_header" select=".">
        <xsl:with-param name="filename">xaruser.php</xsl:with-param>
    </xsl:call-template>

    <!-- call template for module_admin_main() function -->
    <xsl:apply-templates mode="xaruser_main" select="." />

    <!-- call template for module_user_common() function -->
    <xsl:apply-templates mode="xaruser_common" select="." />

    <!-- call template for module_user_display() function -->
    <xsl:apply-templates mode="xaruser_display" select="." />

    <!-- call template for module_user_view() function -->
    <xsl:apply-templates mode="xaruser_view" select="." />

    <!-- call template for file footer -->
    <xsl:call-template name="xaraya_standard_php_file_footer" select="." />

</xsl:processing-instruction></xsl:document>
</xsl:template>


<!-- FUNCTION module_admin_main() -->
<xsl:template mode="xaruser_main" match="xaraya_module">
    <xsl:variable name="module_prefix" select="registry/name" />
function <xsl:value-of select="$module_prefix" />_user_main() {

    // Security Check <xsl:if test="$gCommentsLevel >=2">
    // It is important to do this as early as possible to avoid potential
    // security holes or just too much wasted processing.  For the main
    // function we want to check that the user has at least edit privilege for
    // some item within this component, or else they won't be able to do
    // anything and so we refuse access altogether.  The lowest level of
    // access for administration depends on the particular module, but it is
    // generally either 'edit' or 'delete'. </xsl:if>
    if (!xarSecurityCheck( 'View<xsl:value-of select="$module_prefix" />')) return;

    $data = <xsl:value-of select="$module_prefix" />_user_common( 'Splash Page' );

    return $data;
}
</xsl:template>


<!-- ========================================================================

        MODE: xaruser_common               MATCH: xaraya_module

-->
<xsl:template mode="xaruser_common" match="xaraya_module">
    <xsl:variable name="module_prefix" select="registry/name" />
/**
 * This function provides information to the templates which are common to all
 * pageviews.
 *
 * It provides the following informations:
 *
 *      'menu'      => Array with information about the module menu
 *      'statusmsg' => Status message if set
 */
function <xsl:value-of select="$module_prefix" />_user_common( $title = 'Undefined' ) {

    $common = array();

    $common['menu'] = array();

    // Initialize the statusmessage
    $statusmsg = xarSessionGetVar( '<xsl:value-of select="$module_prefix" />_statusmsg' );
    if ( isset( $statusmsg ) ) {
        xarSessionDelVar( '<xsl:value-of select="$module_prefix" />_statusmsg' );
        $common['statusmsg'] = $statusmsg;
    }

    <xsl:if test="not( boolean( configuration/capabilities/setpagetitle ) ) or configuration/capabilities/setpagetitle/text() = 'yes'">
    // Set the page title
    xarTplSetPageTitle( '<xsl:value-of select="$module_prefix" /> :: ' . $title );
    </xsl:if>

    // Initilaize the title
    $common['pagetitle'] = $title;

    return array( 'common' => $common );
}
</xsl:template>


<!-- =========================================================================

    MODE: xaruser_view                      MATCH:  xaraya_module

-->
<xsl:template mode="xaruser_view" match="xaraya_module">
    <xsl:variable name="module_prefix" select="registry/name" />
/**
 * Standard interface for view item lists
 *
 * This is a generic display() function for DD handled itemtype's. The
 * itemtype specific parts are separated to a function
 * userpriv_viewtable().
 *
 */
function <xsl:value-of select="$module_prefix" />_user_view( $args ) {

    $itemtype = xarVarCleanFromInput( 'itemtype' );
    extract( $args );

    switch( $itemtype ) {
    <xsl:for-each select="database/table[@user='true']">
        case <xsl:value-of select="@itemtype" />:
            return xarModAPIFunc(
                '<xsl:value-of select="$module_prefix" />'
                ,'<xsl:value-of select="@name" />'
                ,'view'
                , $args );
    </xsl:for-each>

        default:
            xarSessionSetVar(
                '<xsl:value-of select="$module_prefix" />_statusmsg'
                ,'Error: Itemtype not specified or invalid. Redirected you to main page!' );

            xarResponseRedirect(
                xarModURL(
                    '<xsl:value-of select="$module_prefix" />'
                    ,'user'
                    ,'main' ));
    }

}
</xsl:template>



<!-- =========================================================================

    MODE: xaruser_display                   MATCH:  xaraya_module

-->
<xsl:template mode="xaruser_display" match="xaraya_module">
    <xsl:variable name="module_prefix" select="registry/name" />
/**
 * Standard interface for displaying objects
 *
 * This is a generic display() function for DD handled itemtype's. The
 * itemtype specific parts are separated to a function
 * userpriv_displaytable().
 *
 * <xsl:if test="$gCommentsLevel >= 20">
 * This function has to deal with some special events.
 *
 * dynamic data
 * ============
 *
 * The dynamic data module calls this function to display a object. The
 * following informations are provided
 *      'itemtype'  =>  type of the object to delete
 *      'itemid'    =>  id of the item to delete
 *
 * preview
 * =======
 *
 * The admin interface calls this function to render a preview. It has to
 * provide the followwing informations.
 *
 *      'object'    =>  the object we should render
 *      'itemtype'  =>  ala the object is not able to tell me his type ...
 *
 * </xsl:if>
 */
function <xsl:value-of select="$module_prefix" />_user_display( $args ) {

    $itemtype = xarVarCleanFromInput( 'itemtype' );
    extract( $args );

    switch( $itemtype ) {
    <xsl:for-each select="database/table[@user='true' or @admin='true']">
        case <xsl:value-of select="@itemtype" />:
            return xarModAPIFunc(
                '<xsl:value-of select="$module_prefix" />'
                ,'<xsl:value-of select="@name" />'
                ,'display'
                , $args );
    </xsl:for-each>

        default:
            xarSessionSetVar(
                '<xsl:value-of select="$module_prefix" />_statusmsg'
                ,'Error: Itemtype not specified or invalid. Redirected you to main page!' );

            xarResponseRedirect(
                xarModURL(
                    '<xsl:value-of select="$module_prefix" />'
                    ,'user'
                    ,'main' ));
    }
}
</xsl:template>


</xsl:stylesheet>
