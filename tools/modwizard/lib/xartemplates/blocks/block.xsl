<xsl:stylesheet version="1.0"
                xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
                xmlns:xar="dd"
                xmlns="http://www.w3.org/TR/xhtml1/strict">


<xsl:template match="block" mode="xd_blocks_block">
    <xsl:message>      * xartemplates/blocks/<xsl:value-of select="@name" />.xd</xsl:message>

    <xsl:variable name="block" select="@name" />
    <xsl:document xml:space="preserve" href="{$output}/xartemplates/blocks/{$block}.xd" format="xml" omit-xml-declaration="yes" >
<div>
Statusmsg #$content#
</div>
    </xsl:document>
</xsl:template>

</xsl:stylesheet>
