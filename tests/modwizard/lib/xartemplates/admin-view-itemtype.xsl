<xsl:stylesheet version="1.0"
                xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
                xmlns:xar="dd"
                xmlns="http://www.w3.org/TR/xhtml1/strict">

<!--

    xartemplates/includes/xarinit.php
    =================================

-->

<xsl:template match="/" mode="xd_admin-view-itemtype">
    <xsl:apply-templates mode="xd_admin-view-itemtype" select="xaraya_module" />
</xsl:template>


<!--

    THE FILE
    ========

-->
<xsl:template match="xaraya_module" mode="xd_admin-view-itemtype">

    <xsl:for-each select="database/table">
    generating xartemplates/admin-view<xsl:value-of select="@name" />.xd ...<xsl:apply-templates select="." mode="xd_admin-view-itemtype" />... finished
    </xsl:for-each>

</xsl:template>

<xsl:template match="table" mode="xd_admin-view-itemtype">
    <xsl:variable name="table" select="@name" />
<xsl:document xml:space="preserve" href="{$output}/xartemplates/admin-view-{$table}.xd" format="text" omit-xml-declaration="yes" >

<xar:template file="header" type="module" />

    <table width="100%" border="1" cellspacing="0" cellpadding="4">
    <tr>
        <xar:foreach in="$objects_props" key="$name">
            <th align="center"><xar:data-label property="$objects_props[$name]" /></th>
        </xar:foreach>
        <th align="center"><xar:mlstring>Options</xar:mlstring></th>
    </tr>
    <xar:foreach in="$objects_values" key="$itemid" value="$fields">
    <tr>
            <xar:set name="$test">array(<xsl:text disable-output-escaping="yes">'itemid' => $itemid, 'itemtype' => </xsl:text><xsl:value-of select="@itemtype" /> )</xar:set>
        <xar:foreach in="$objects_props" key="$name">
            <xar:if condition="!empty($fields[$name])">
            <td><xar:data-output property="$objects_props[$name]" value="$fields[$name]" /></td>
            <xar:else />
            <td> </td>
            </xar:if>
        </xar:foreach>
        <td>
            <xsl:element name="a" xml:space="default">
                <xsl:attribute disable-output-escaping="yes" name="href">#xarModURL('<xsl:value-of select="../../registry/name" />','user','display', $test )#</xsl:attribute>
                <xar:mlstring>View</xar:mlstring>
            </xsl:element> |
            <xsl:element name="a" xml:space="default">
                <xsl:attribute name="href">#xarModURL('<xsl:value-of select="../../registry/name" />','admin','modify',$test)#</xsl:attribute>
                <xar:mlstring>Modify</xar:mlstring>
            </xsl:element> |
            <xsl:element name="a" xml:space="default">
                <xsl:attribute name="href">#xarModURL('<xsl:value-of select="../../registry/name" />','admin','delete',$test)#</xsl:attribute>
                <xar:mlstring>Delete</xar:mlstring>
            </xsl:element>
        </td>
    </tr>
    </xar:foreach>
    <tr>
        <xar:foreach in="$objects_props" key="$name">
            <td> </td>
        </xar:foreach>
        <td>
                <xar:set name="$test">array( <xsl:text disable-output-escaping="yes">'itemtype' => </xsl:text><xsl:value-of select="@itemtype" /> )</xar:set>
            <xsl:element name="a" xml:space="default">
                <xsl:attribute name="href">#xarModURL('<xsl:value-of select="../../registry/name" />','admin','new',$test )#</xsl:attribute>
                <xar:mlstring>New</xar:mlstring>
            </xsl:element>
        </td>
    </tr>
    <xar:if condition="!empty( $pager )">
    <tr>
        <xar:set name="$count">count($objects_props) + 1</xar:set>
        <td colspan="#$count#" align="center">
            #$pager#
        </td>
    </tr>
    </xar:if>
    </table>

</xsl:document>
</xsl:template>
</xsl:stylesheet>
