<xsl:stylesheet version="1.0"
                xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
                xmlns:xar="dd"
                xmlns="http://www.w3.org/TR/xhtml1/strict">

<xsl:template match="table" mode="xd_user-display-itemtype">

    <xsl:variable name="itemtype" select="@name" />
    <xsl:message>      * xartemplates/user-display-<xsl:value-of select="$itemtype" />.xd</xsl:message>
    <xsl:apply-templates mode="xd_user-display-itemtype-file" select="." />

</xsl:template>



<xsl:template match="table" mode="xd_user-display-itemtype-file">
<xsl:variable name="itemtype" select="@name" />

    <xsl:document xml:space="preserve" href="{$output}/xartemplates/user-display-{$itemtype}.xd" format="text" omit-xml-declaration="yes" >

    <xar:template file="header" type="module" />

    <xsl:for-each select="structure/field">
        <xsl:comment>FIELD <xsl:value-of select="@name" /></xsl:comment>

        <div style="clear: both; padding-top: 10px;">
        <span style="float: left; width: 20%; text-align: right;">
            <xsl:element name="xar:data-label" xml:space="default">
                <xsl:attribute name="property">$object_props['<xsl:value-of select="@name" />']</xsl:attribute>
            </xsl:element>:
        </span>
        <span style="float: right; width: 78%; text-align: left;">
            <xsl:element name="xar:data-output" xml:space="default">
                <xsl:attribute name="property">$object_props['<xsl:value-of select="@name" />']</xsl:attribute>
                <xsl:attribute name="value">$object_values['<xsl:value-of select="@name" />']</xsl:attribute>
            </xsl:element>
        </span>
        </div>
    </xsl:for-each>

    <!-- Only display hooks when necessary -->
    <br />
    <xar:if condition="!empty($hooks)">
    <xar:foreach in="$hooks" key="$hookmodule">
        #$hooks[$hookmodule]#
    </xar:foreach>
    </xar:if>

</xsl:document>
</xsl:template>
</xsl:stylesheet>
