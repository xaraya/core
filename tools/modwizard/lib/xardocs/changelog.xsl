<!--

    XARAYA MODULE WIZARD

    COPYRIGHT:      Michael Jansen
    CONTACT:        xaraya-module-wizard@schneelocke.de
    LICENSE:        GPL

-->
<xsl:stylesheet version="1.0"
                xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
                xmlns="http://www.w3.org/TR/xhtml1/strict">

<xsl:template match="xaraya_module" mode="xardocs_changelog">

    <xsl:message>       * xardocs/changelog.txt</xsl:message>

<xsl:document href="{$output}/xardocs/changelog.txt" format="text" omit-xml-declaration="yes" >

Add your changelog here

</xsl:document>
</xsl:template>

</xsl:stylesheet>
