<xsl:stylesheet version="1.0"
                xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
                xmlns:xar="dd"
                xmlns="http://www.w3.org/TR/xhtml1/strict">

<!--

    xartemplates/includes/xarinit.php
    =================================

-->

<xsl:template match="/" mode="xd_blocks_block">
    <xsl:apply-templates mode="xd_blocks_block" select="xaraya_module" />
</xsl:template>


<!--

    THE FILE
    ========

-->
<xsl:template match="xaraya_module" mode="xd_blocks_block">
    <xsl:for-each select="blocks/block">
    generating xartemplates/blocks/<xsl:value-of select="@name" />.xd <xsl:apply-templates mode="xd_blocks_block" select="." /> ... finished
    </xsl:for-each>
</xsl:template>

<xsl:template match="block" mode="xd_blocks_block">
<xsl:variable name="block" select="@name" />
<xsl:document xml:space="preserve" href="{$output}/xartemplates/blocks/{$block}.xd" format="xml" omit-xml-declaration="yes" >
<div>
Statusmsg #$content#
</div>
</xsl:document>
</xsl:template>
</xsl:stylesheet>

