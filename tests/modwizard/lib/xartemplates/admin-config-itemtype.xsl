<xsl:stylesheet version="1.0"
                xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
                xmlns:xar="dd"
                xmlns="http://www.w3.org/TR/xhtml1/strict">

<xsl:template match="table" mode="xd_admin-config-itemtype">

    <xsl:variable name="table" select="@name" />
    <xsl:message>      * xartemplates/admin-config-<xsl:value-of select="@name" />.xd</xsl:message>

<xsl:document href="{$output}/xartemplates/admin-config-{$table}.xd" format="text" omit-xml-declaration="yes" xml:space="preserve">

    <xar:template file="header" type="module" />
    <div class="xar-mod-body">
    <div style="padding: 1px; margin: auto;" class="xar-norm-outline">

<form method="post" action="#$action#">

    <input type="hidden" name="authid" id="authid" value="#$authid#" />
    <input type="hidden" name="itemtype" id="itemtype" value="#$itemtype#" />

    <table width="100%" cellspacing="1" cellpadding="8" border="0">

        <colgroup>
            <col width="30%" />
            <col />
        </colgroup>

        <tr>
            <td><label for="itemsperpage"><xar:mlstring>Item's per page</xar:mlstring></label></td>
            <td>
                <xsl:element name="xar:data-input" xml:space="default">
                    <xsl:attribute name="size">3</xsl:attribute>
                    <xsl:attribute name="maxlength">3</xsl:attribute>
                    <xsl:attribute name="type">integerbox</xsl:attribute>
                    <xsl:attribute name="id">itemsperpage</xsl:attribute>
                    <xsl:attribute name="name">itemsperpage</xsl:attribute>
                    <xsl:attribute name="value">$itemsperpage</xsl:attribute>
                </xsl:element>
            </td>
        </tr>

        <xar:if condition="!empty($hooks)">
        <xar:foreach in="$hooks" key="$hookmodule">
        <tr>
            <td colspan="2">#$hooks[$hookmodule]#</td>
        </tr>
        </xar:foreach>
        </xar:if>
        <tr>
            <td colspan="2" align="center">
                <input type="submit" value="#xarML('Modify')#" />
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
