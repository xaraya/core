<!--

    XARAYA MODULE WIZARD

    COPYRIGHT:      Michael Jansen
    CONTACT:        xaraya-module-wizard@schneelocke.de
    LICENSE:        GPL

-->
<xsl:stylesheet version="1.0"
                xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
                xmlns="http://www.w3.org/TR/xhtml1/strict">

<xsl:template match="xaraya_module" mode="xarhookapi_item_transforminput" xml:space="default">

    <xsl:message>      * item_transforminput()</xsl:message>

    <xsl:document href="{$output}/xarhookapi/item_transforminput.php" format="text" omit-xml-declaration="yes" ><xsl:processing-instruction name="php">

        <xsl:call-template name="xaraya_standard_php_file_header" select=".">
            <xsl:with-param name="filename">xarhookapi/item_transforminput</xsl:with-param>
        </xsl:call-template>

        <xsl:apply-templates select="." mode="xarhookapi_item_transforminput_func" />

        <xsl:call-template name="xaraya_standard_php_file_footer" select="." />

    </xsl:processing-instruction></xsl:document>

</xsl:template>

<!-- =========================================================================
     TEMPLATE FOR <module>_hookapi_item_transforminput()
-->
<xsl:template match="xaraya_module" mode="xarhookapi_item_transforminput_func">

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
function <xsl:value-of select="$module_prefix" />_hookapi_item_transforminput ( $args ) 
{
    extract($args);

    // Argument check
    if (!isset($extrainfo)) {
        $msg = xarML('Invalid Parameter Count in #(3), #(1)api_#(2)', 'hook', 'transforminput', '<xsl:value-of select="$module_prefix" />');
        xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM', new SystemException($msg));
        return;
    }

    if (is_array($extrainfo)) {
        $result = array();
        if (isset($extrainfo['transform']) and is_array($extrainfo['transform'])) {
            foreach ($extrainfo['transform'] as $key) {
                if (isset($extrainfo[$key])) {
                    $extrainfo[$key] = <xsl:value-of select="$module_prefix" />_transforminput($extrainfo[$key]);
                }
            }
            return $extrainfo;
        }
        foreach ($extrainfo as $key => $value) {
            $result[$key] = <xsl:value-of select="$module_prefix" />_transforminput($value);
        }
    } else {
        $result = <xsl:value-of select="$module_prefix" />_transforminput($text);
    }

    return $result;

}

function <xsl:value-of select="$module_prefix" />_transforminput( $text ) 
{
    return '[ Input Hook: ] ' . $text;

}

</xsl:template>

</xsl:stylesheet>
