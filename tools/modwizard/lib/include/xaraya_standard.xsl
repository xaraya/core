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

    HELPER FUNCTIONS FOR FILE LAYOUT
    ================================

        o PHP File Header

-->

<!-- HEADER FOR A PHP FILE

        most information ist taken from the xaraya_module/about section.

        PARAMETER

            name            filename
            comment         a comment

-->
<xsl:template name="xaraya_standard_php_file_header" match="xaraya_module">

    <xsl:param name="filename" />
    <xsl:if test="not($filename)">
        <xsl:message terminate="yes">
            xaraya_standard_php_file_header() : parameter filename missing
        </xsl:message>
    </xsl:if>

/**
 * <xsl:value-of select="about/name" />
 *
 * @copyright   <xsl:value-of select="about/copyright" />
 * @license     <xsl:value-of select="about/license" />
 * @author      <xsl:value-of select="about/author/name" />
 * @link        <xsl:value-of select="about/author/link" />
 *
 * @package     Xaraya eXtensible Management System
 * @subpackage  <xsl:value-of select="about/name" />
 * @version     $Id$
 *
 */
</xsl:template>



<!-- FOOTER FOR A PHP FILE

        most information ist taken from the xaraya_module/about section.

        PARAMETER

            NONE

-->
<xsl:template name="xaraya_standard_php_file_footer" match="xaraya_module">
/*
 * END OF FILE
 */
</xsl:template>
</xsl:stylesheet>
