<xsl:stylesheet version="1.0"
                xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
                xmlns:xar="dd"
                xmlns="http://www.w3.org/TR/xhtml1/strict">

<xsl:template match="xaraya_module" mode="xd_hook_module_modifyconfig">

    <xsl:variable name="table" select="@name" />
    <xsl:message>      * xartemplates/hook-module_modifyconfig.xd</xsl:message>

<xsl:document href="{$output}/xartemplates/hook-module_modifyconfig.xd" format="text" omit-xml-declaration="yes" xml:space="preserve">

    <div class="xar-mod-title xar-norm-outline" style="margin-top: 1em; margin-left: 1em; margin-right: 1em; width: auto; border-style: none none dotted none;">
        <p><xar:mlstring><xsl:value-of select="about/name" /> Configuration</xar:mlstring></p>
    </div>
    <div style="margin-left: 1em; margin-right: 1em; text-align:left;">
        <p><xar:mlstring>Add the configuration options here.</xar:mlstring></p>
    </div>

</xsl:document>
</xsl:template>
</xsl:stylesheet>
