<xsl:stylesheet version="1.0"
                xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
                xmlns:xar="dd"
                xmlns="http://www.w3.org/TR/xhtml1/strict">

<xsl:template match="table" mode="xd_admin-modify-itemtype">

    <xsl:variable name="table" select="@name" />
    <xsl:message>      * xartemplates/admin-modify-<xsl:value-of select="@name" />.xd</xsl:message>

<xsl:document href="{$output}/xartemplates/admin-modify-{$table}.xd" omit-xml-declaration="yes" xml:space="preserve">

    <xar:template file="header" type="module" />
    <div class="xar-mod-body">
    <div style="padding: 1px;" class="xar-norm-outline">

<xar:if condition="!empty($preview)">
<div class="xar-norm-outline">
    #$preview#
</div>
</xar:if>


<form method="post" action="#$action#">

    <input type="hidden" name="authid" id="authid" value="#$authid#" />
    <input type="hidden" name="itemid" id="itemid" value="#$itemid#" />

    <xsl:for-each select="structure/field">
        <xsl:comment>FIELD <xsl:value-of select="@name" /></xsl:comment>

        <div style="clear: both; padding-top: 10px;">
        <span style="float: left; width: 20%; text-align: right;">
            <xsl:element name="xar:data-label" xml:space="default">
                  <xsl:attribute name="property">$object_props['<xsl:value-of select="@name" />']</xsl:attribute>
                  <xsl:attribute name="label">id</xsl:attribute>
            </xsl:element>:
        </span>
        <span style="float: right; width: 78%; text-align: left;">
            <xsl:element name="xar:data-input" xml:space="default">
                <xsl:attribute name="property">$object_props['<xsl:value-of select="@name" />']</xsl:attribute>
                <xsl:attribute name="value">$object_values['<xsl:value-of select="@name" />']</xsl:attribute>
            </xsl:element>
        </span>
        </div>
    </xsl:for-each>

    <!-- Only display hooks when necessary -->
    <xar:if condition="!empty($hooks)">
    <xar:foreach in="$hooks" key="$hookmodule">
        #$hooks[$hookmodule]#
    </xar:foreach>
    </xar:if>

    <div style="clear: both; padding-top: 10px; text-align: center; width: 100%;">
            <input type="submit"                value="#xarML('Modify')#" />
            <input type="submit" name="preview" value="#xarML('Preview')#" />
            <input type="submit" name="cancel"  value="#xarML('Cancel')#" />
    </div>

</form>
</div>
</div>
</xsl:document>
</xsl:template>

</xsl:stylesheet>
