<xsl:stylesheet version="1.0"
                xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
                xmlns:xar="dd"
                xmlns="http://www.w3.org/TR/xhtml1/strict">

<xsl:template match="xaraya_module" mode="xd_includes_header">

    <xsl:message>       * xartemplates/includes/header.xd</xsl:message>

<xsl:document href="{$output}/xartemplates/includes/header.xd" format="text" omit-xml-declaration="yes" xml:space="preserve">

<xar:if condition="!empty($common)">

    <xar:if condition="isset( $common['type']) and $common['type'] = 'admin'">
    <div class="xar-mod-head"><span class="xar-mod-title"><xar:mlstring>Administration - <xsl:value-of select="about/name" /></xar:mlstring></span></div>
    </xar:if>

    <div class="xar-mod-body">

        <h2>#$common['pagetitle']#</h2>

        <xar:if condition="count( $common['menu']) > 0">
            <xar:set name="$common_menu">#$common['menu']#</xar:set>
            <xar:template file="navbar" type="module" />
        </xar:if>

    </div>

    <xar:if condition="!empty($common['statusmsg'])">
    <div class="xar-mod-message" style="margin: 20px 10px 20px 20px; background-color: ##EEEEEE; text-align: center;">
        #$common['statusmsg']#
    </div>
    </xar:if>

</xar:if>
</xsl:document>
</xsl:template>
</xsl:stylesheet>
