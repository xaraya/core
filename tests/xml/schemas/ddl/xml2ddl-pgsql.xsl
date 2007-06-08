<?xml version="1.0"?>
<!--
  XSLT to create a DDL fragment which represents the same
  information as the ddl XML
-->
<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform" >
  <!--
      Import common templates, we use import instead of include so the
      imported templates get a lower priority than the ones in this file,
      giving the ability here to override the imports
  -->
  <xsl:import href="xml2ddl-base.xsl"/>

<!-- Things to do before we start handling elements -->
<xsl:template match="/">
  <xsl:call-template name="topheader">
    <xsl:with-param name="dbname"><xsl:value-of select="/database/@name"/></xsl:with-param>
    <xsl:with-param name="remarks">
    - reference: http://www.postgresql.org/docs/8.1/interactive/index.html
    - assuming for now we want to drop before create
    </xsl:with-param>
  </xsl:call-template>
  <xsl:apply-templates/>
</xsl:template>

<xsl:template match="table">
  <xsl:call-template name="dynheader"/>
  <xsl:text>/* TODO: Dropping a table without exist checking is an error in Postgres, but common to use */</xsl:text>
  <xsl:value-of select="$CR"/>
  <xsl:text>CREATE TABLE </xsl:text>
  <xsl:value-of select="@name"/>
  <xsl:text>(</xsl:text>
  <xsl:value-of select="$CR"/>
  <xsl:apply-templates select="primary/column | column"/>
  <xsl:text>);</xsl:text>
  <xsl:value-of select="$CR"/>
  <xsl:apply-templates select="primary"/>
  <xsl:apply-templates select="index"/>
  <!-- TODO: we use different sequence name for auto inc columns:
    not: tablename_colname_seq, but tablename_seq
  -->
</xsl:template>

<xsl:template match="table/description">
  <xsl:text>COMMENT ON TABLE </xsl:text>
  <xsl:value-of select="../@name"/>
  <xsl:text> IS '</xsl:text>
  <xsl:value-of select="."/>
  <xsl:text>';</xsl:text>
  <xsl:value-of select="$CR"/>
</xsl:template>

<xsl:template match="table/column/description">
  <xsl:text>COMMENT ON COLUMN </xsl:text>
  <xsl:value-of select="../../@name"/><xsl:text>.</xsl:text><xsl:value-of select="../@name"/><xsl:text> IS '</xsl:text>
  <xsl:value-of select="."/>
  <xsl:text>';</xsl:text>
  <xsl:value-of select="$CR"/>
</xsl:template>

<xsl:template match="table/column">
  <xsl:text>  </xsl:text>
  <xsl:value-of select="@name"/><xsl:text> </xsl:text>
  <xsl:choose>
    <xsl:when test="@type = 'LONGVARCHAR'">
      <xsl:text>TEXT</xsl:text>
    </xsl:when>
    <xsl:when test="@autoincrement = 'true'">
      <xsl:text>SERIAL</xsl:text>
    </xsl:when>
    <xsl:otherwise>
      <xsl:value-of select="@type"/>
    </xsl:otherwise>
  </xsl:choose>
  <xsl:if test="@size != ''">
    <xsl:text>(</xsl:text>
    <xsl:value-of select="@size"/>
    <xsl:text>) </xsl:text>
  </xsl:if>
  <xsl:if test="@required ='true'"><xsl:text> NOT NULL</xsl:text></xsl:if>
  <xsl:if test="@default != ''">
    <xsl:text> DEFAULT '</xsl:text>
    <xsl:value-of select="@default"/>
    <xsl:text>'</xsl:text>
  </xsl:if>
  <xsl:if test="position() != last()">
    <xsl:text>,</xsl:text>
  </xsl:if>
  <xsl:value-of select="$CR"/>
</xsl:template>

</xsl:stylesheet>