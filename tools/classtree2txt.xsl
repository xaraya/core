<?xml version="1.0" encoding="utf-8"?>
<!--

ASCII XML Tree Viewer 1.0 (13 Feb 2001)
An XPath/XSLT visualisation tool for XML documents

Written by Jeni Tennison and Mike J. Brown
No license; use freely, but please credit the authors if republishing elsewhere.

Use this stylesheet to produce an ASCII art representation of an XML document's
node tree, as exposed by the XML parser and interpreted by the XSLT processor.
Note that the parser may not expose comments to the XSLT processor.

Heavily stripped for specific xaraya usage by Marcel van der Boom

-->
<xsl:stylesheet xmlns:xsl="http://www.w3.org/1999/XSL/Transform" version="1.0">
<xsl:output method="text" />
<xsl:strip-space elements="*"/>

<xsl:template match="/root">
    <xsl:apply-templates select="." mode="ascii-art" />
</xsl:template>

<xsl:template match="/root" mode="ascii-art">
    <xsl:apply-templates mode="ascii-art" />
</xsl:template>

<xsl:template match="*" mode="ascii-art">
    <xsl:call-template name="ascii-art-hierarchy" />
    <xsl:text />\___<xsl:value-of select="@xml:id" /><xsl:text />
    <xsl:text>&#xA;</xsl:text>
    <xsl:apply-templates mode="ascii-art" />
</xsl:template>

<xsl:template name="ascii-art-hierarchy">
    <xsl:for-each select="ancestor::*">
        <xsl:choose>
            <xsl:when test="following-sibling::node()">  |   </xsl:when>
            <xsl:otherwise><xsl:text>      </xsl:text></xsl:otherwise>
        </xsl:choose>
    </xsl:for-each>
    <xsl:choose>
        <xsl:when test="parent::node() and ../child::node() and following-sibling::node()">  |</xsl:when>
        <xsl:otherwise><xsl:text>   </xsl:text></xsl:otherwise>
    </xsl:choose>
</xsl:template>

</xsl:stylesheet>
