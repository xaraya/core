<!--

    XARAYA MODULE WIZARD

    COPYRIGHT:      Michael Jansen
    CONTACT:        xaraya-module-wizard@schneelocke.de
    LICENSE:        GPL

-->
<xsl:stylesheet version="1.0"
                xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
                xmlns="http://www.w3.org/TR/xhtml1/strict">

<xsl:include href="xarblocks/block.xsl" />
<xsl:include href="xarblocks/modify-block.xsl" />
<xsl:include href="xartemplates/blocks/block.xsl" />
<xsl:include href="xartemplates/blocks/modify-block.xsl" />

<xsl:template match="xaraya_module" mode="xarblocks" xml:space="default">

    <xsl:message>
### Generating Blocks</xsl:message>

    <xsl:for-each select="blocks/block">

        <xsl:message>   - <xsl:value-of select="@name" /></xsl:message>

        <xsl:apply-templates mode="xarblocks_block"        select="."/>
        <xsl:apply-templates mode="xarblocks_modify-block" select="." />
        <xsl:apply-templates mode="xd_blocks_block"        select="." />
        <xsl:apply-templates mode="xd_blocks_modify-block" select="." />

    </xsl:for-each>

</xsl:template>

</xsl:stylesheet>
