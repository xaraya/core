<xsl:stylesheet version="1.0"
    xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
    xmlns:xar="dd"
    xmlns="http://www.w3.org/TR/xhtml1/strict"
    exclude-result-prefixes="xar">

<xsl:template match="xaraya_module" mode="xd_includes_navbar">
    <xsl:variable name="module_prefix" select="registry/name" />

    <xsl:message>       * xartemplates/includes/navbar.xd</xsl:message>

<xsl:document href="{$output}/xartemplates/includes/navbar.xd" format="text" omit-xml-declaration="yes" xml:space="preserve">

    <xsl:text disable-output-escaping="yes">&lt;xar:if condition="xarTplAddStyleLink('</xsl:text><xsl:value-of select="$module_prefix" /><xsl:text disable-output-escaping="yes"> ', 'navbar')"&gt;</xsl:text>
    <div>
        <div class="tabnav">
            <div class="navhelp help" title="#xarML('Click on a tab to display that itemtype' )#">
                <xar:mlstring>Itemtype</xar:mlstring>:
            </div>
            <div class="tabnav-hairline"><!-- &nbsp; --></div>
            <ul class="navlist">
                <xar:foreach in="$common_menu" value="$value">
                    <xar:if condition="empty($value['url'])">
                        <li class="active"><a href="#xarServerGetCurrentURL()#" title="#$value['title']#"> #$value['title']# </a></li>
                        <xar:else />
                        <li><a href="#$value['url']#" title="#$value['title']#"> #$value['title']# </a></li>
                    </xar:if>
                </xar:foreach>
            </ul>
            <div class="tabnav-hairline"><!-- &nbsp; --></div>
        </div>
    </div>
    <xsl:text disable-output-escaping="yes">&lt;/xar:if&gt;</xsl:text>
</xsl:document>
</xsl:template>
</xsl:stylesheet>
