<!DOCTYPE xsl:stylesheet [
        <!ENTITY nbsp "&#160;">
]>
<xsl:stylesheet version="1.0"
                xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
                xmlns:html="http://www.w3.org/TR/xhtml1/strict"
                xmlns:xar="dd"
                xmlns="http://www.w3.org/TR/xhtml1/strict">


<xsl:template match="table" mode="xd_user-view-itemtype">

    <xsl:variable name="itemtype" select="@name" />

    <xsl:message>      * xartemplates/user-view-<xsl:value-of select="$itemtype" />.xd</xsl:message>
    <xsl:apply-templates mode="xd_user-view-itemtype-file" select="." />

</xsl:template>


<xsl:template match="table" mode="xd_user-view-itemtype-file">
<xsl:variable name="table" select="@name" />
<xsl:variable name="module_prefix" select="../../registry/name" />
<xsl:document href="{$output}/xartemplates/user-view-{$table}.xd" format="xml" omit-xml-declaration="yes" xml:space="preserve">

<xar:template file="header" type="module" />

    <!-- needed for the colspans below -->
    <xar:set name="$count">count($objects_props)</xar:set>

    <table width="100%" border="1" cellspacing="0" cellpadding="4">
    <tr>
        <xar:foreach in="$objects_props" key="$name">
            <th align="center"><xar:data-label property="$objects_props[$name]" /></th>
        </xar:foreach>

    </tr>
    <xar:foreach in="$objects_values" key="$itemid" value="$fields">
    <tr>
        <xar:set name="$test">array(<xsl:text disable-output-escaping="yes">'itemid'=> $itemid, 'itemtype' => $itemtype )</xsl:text></xar:set>

        <xsl:for-each select="structure/field[@overview = 'true']">
            <xsl:comment>Field <xsl:value-of select="@name" /></xsl:comment>
            <xsl:element name="xar:if" xml:space="preserve"><xsl:attribute name="condition">!empty($fields['<xsl:value-of select="@name" />'])</xsl:attribute><xsl:attribute name="xmlns:html">http://www.w3.org/TR/xhtml1/strict</xsl:attribute>

            <!-- the next is a trick to get a td instead of a xar:td. xmlproc insist on creating the second one. don't know why -->
            <xsl:element name="td">
                <xsl:element name="a" xml:space="default">
                    <xsl:attribute disable-output-escaping="yes" name="href">#xarModURL('<xsl:value-of select="$module_prefix" />','user','display', $test )#</xsl:attribute>
                    <xsl:element name="xar:data-output" xml:space="default">
                        <xsl:attribute name="property">$objects_props['<xsl:value-of select="@name" />']</xsl:attribute>
                        <xsl:attribute name="value">$fields['<xsl:value-of select="@name" />']</xsl:attribute>
                    </xsl:element>
                </xsl:element>
            </xsl:element>
            <xar:else />
            <!-- the next is a trick to get a td instead of a xar:td. xmlproc insist on creating the second one. don't know why -->
            <xsl:element name="td">
                <xsl:text disable-output-escaping="yes">&amp;nbsp;</xsl:text>
            </xsl:element>
            </xsl:element>
        </xsl:for-each>
    </tr>
    </xar:foreach>

    <!-- view the pager -->
    <xar:if condition="!empty($pager)">
    <tr>
        <td colspan="#$count#" align="center">
            #$pager#
        </td>
    </tr>
    </xar:if>

    </table>

</xsl:document>
</xsl:template>
</xsl:stylesheet>
