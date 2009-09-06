<xsl:stylesheet version="1.0"
                xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
                xmlns:xar="dd"
                xmlns="http://www.w3.org/TR/xhtml1/strict">

<xsl:template match="xaraya_module" mode="xd_admin-config">

    <xsl:message>      * xartemplates/admin-config.xd</xsl:message>

<xsl:document href="{$output}/xartemplates/admin-config.xd" format="text" omit-xml-declaration="yes" xml:space="preserve">

    <xar:template file="header" type="module" />
    <div class="xar-mod-body">
    <div style="padding: 1px;" class="xar-norm-outline">

<form method="post" action="#$action#">

    <input type="hidden" name="authid" id="authid" value="#$authid#" />

    <table width="100%" cellspacing="0" cellpadding="8" border="0">

        <colgroup>
            <col width="30%" />
            <col />
        </colgroup>

    <xsl:if test="not( boolean( configuration/capabilities/enable_short_urls ) )
                  or configuration/capabilities/enable_short_urls/text() = 'yes'">

        <tr>
            <td><label for="enable_short_urls">Short URL Support</label></td>
            <td>
                <xsl:element name="xar:data-input" xml:space="default">
                    <xsl:attribute name="id">enable_short_urls</xsl:attribute>
                    <xsl:attribute name="type">checkbox</xsl:attribute>
                    <xsl:attribute name="name">enable_short_urls</xsl:attribute>
                    <xsl:attribute name="value">$enable_short_urls</xsl:attribute>
                </xsl:element>
            </td>
        </tr>

    </xsl:if>

    <xsl:if test="configuration/hooks/@enable = 'true'">
        <xar:if condition="!empty($hooks)">
        <xar:foreach in="$hooks" key="$hookmodule">
        <tr>
            <td colspan="2">#$hooks[$hookmodule]#</td>
        </tr>
        </xar:foreach>
        </xar:if>
    </xsl:if>

        <tr>
            <td colspan="2" align="center">
                <input type="submit"                value="#xarML('Modify')#" />
                <input type="submit" name="cancel"  value="#xarML('Cancel')#" />
            </td>
        </tr>

    </table>
</form>
</div>
</div>

</xsl:document>
</xsl:template>
</xsl:stylesheet>
