<xsl:stylesheet version="1.0"
                xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
                xmlns:xar="dd"
                xmlns="http://www.w3.org/TR/xhtml1/strict">

<xsl:template match="table" mode="xd_user-display-itemtype">

    <xsl:variable name="itemtype" select="@name" />
    <xsl:message>      * user-display-<xsl:value-of select="$itemtype" />.xd</xsl:message>
    <xsl:apply-templates mode="xd_user-display-itemtype-file" select="." />

</xsl:template>



<xsl:template match="table" mode="xd_user-display-itemtype-file">
<xsl:variable name="itemtype" select="@name" />

    <xsl:document xml:space="preserve" href="{$output}/xartemplates/user-display-{$itemtype}.xd" format="text" omit-xml-declaration="yes" >

    <xar:template file="header" type="module" />

    <table border="0" cellspacing="0" cellpadding="4">
    <xsl:for-each select="structure/field">
        <xsl:comment>FIELD <xsl:value-of select="@name" /></xsl:comment>
        <tr align="left" valign="middle">
            <td align="right">
                <b><xsl:element name="xar:data-label" xml:space="default">
                      <xsl:attribute name="property">$object_props['<xsl:value-of select="@name" />']</xsl:attribute>
                   </xsl:element> :</b>
            </td>
            <td align="left">
                <xsl:element name="xar:data-output" xml:space="default">
                    <xsl:attribute name="property">$object_props['<xsl:value-of select="@name" />']</xsl:attribute>
                </xsl:element>
            </td>
        </tr>
    </xsl:for-each>

    <!-- Only display hooks when necessary -->
    <xsl:if test="@hooks = 'enable'">
    <tr>
        <td colspan="2">

            <div>
                <xar:if condition="!empty($hooks)">
                <table>
                <xar:foreach in="$hooks" key="$hookmodule">
                <tr>
                    <td>#$hookmodule#</td>
                    <td>#$hooks[$hookmodule]#</td>
                </tr>
                </xar:foreach>
                </table>
                </xar:if>
            </div>
        </td>
    </tr>
    </xsl:if>
    </table>

</xsl:document>
</xsl:template>
</xsl:stylesheet>
