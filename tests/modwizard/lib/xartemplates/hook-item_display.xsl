<xsl:stylesheet version="1.0"
                xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
                xmlns:xar="dd"
                xmlns="http://www.w3.org/TR/xhtml1/strict">

<xsl:template match="xaraya_module" mode="xd_hook-item_display">

    <xsl:variable name="table" select="@name" />
    <xsl:message>      * xartemplates/hook-item_display.xd</xsl:message>

<xsl:document href="{$output}/xartemplates/hook-item_display.xd" format="text" omit-xml-declaration="yes" xml:space="preserve">

    <div style="clear: both; padding-top: 10px;">
    <span style="float: left; width: 20%; text-align: right;">
        <span class="help" title="#xarML('Add your help text.')#"><xar:mlstring><xsl:value-of select="about/name" /></xar:mlstring>:</span>
    </span>
    <span style="float: right; width: 78%; text-align: left;">
        <xar:mlstring>No options yet.</xar:mlstring>
    </span>
    </div>

</xsl:document>
</xsl:template>
</xsl:stylesheet>
