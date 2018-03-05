<?xml version="1.0" encoding="utf-8"?>
<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform" >

  <!-- we are outputting text -->
  <xsl:output method="text" />

  <!-- remove all the whitespace -->
  <xsl:strip-space elements="*"/>

  <!--
      We probably want to specify parameters at some point like:
      - vendor      - generate ddl compatible with $vendor backend
      - version     - generate ddl compatible with $vendor-$version backend
      - drop4create - drop tables before creating them
      - createdb    - create the database too
      - tableprefix - self explanatory
      - etc.
  -->
  <xsl:param name="vendor"  />
  <xsl:param name="version" />
  <xsl:param name="dbcreate"/>
  <xsl:param name="drop4create"/>

  <!-- Variables, xslt style -->
  <xsl:variable name="CR">
    <xsl:text>
    </xsl:text>
  </xsl:variable>

  <!-- File header -->
  <xsl:template name="topheader">
    <xsl:param name="dbname"/>
    <xsl:param name="remarks"/>
/* ---------------------------------------------------------------------------
 * Model generated from: TODO
 * Name                : <xsl:value-of select="$dbname"/>
 * Vendor              : <xsl:value-of select="$vendor"/>
 * Date                : TODO
 * Remarks:            :
 *   <xsl:value-of select="$remarks"/>
 */
  </xsl:template>

<!-- Context sensitive header, reacts on name and element-name -->
<xsl:template name="dynheader">
/* ---------------------------------------------------------------------------
 * <xsl:value-of select="local-name()"/>: <xsl:value-of select="@name" />
 */<xsl:value-of select="$CR"/>
</xsl:template>

  <!-- Easy TODO inclusion -->
  <xsl:template name="TODO">
    <xsl:text>/* TODO: Template for: </xsl:text>
    <xsl:value-of select="local-name()"/>
    <xsl:text> </xsl:text>
    <xsl:value-of select="@name"/>
    <xsl:text> handling (vendor: </xsl:text>
    <xsl:value-of select="$vendor"/>
    <xsl:text>) */
  </xsl:text>
  </xsl:template>

  <!-- Default create database statement -->
  <xsl:template match="schema">
    <xsl:if test="$action = 'display'">
      <xsl:call-template name="dynheader"/>
    </xsl:if>
    <xsl:if test="$dbcreate">
      <xsl:text>CREATE DATABASE </xsl:text><xsl:value-of select="@name"/>;
    </xsl:if>
  <xsl:apply-templates/>
  </xsl:template>

  <!--  @todo make this a generic template? -->
  <xsl:key name="columnid" match="table/column" use="@id"/>
  <xsl:template name="columnrefscsv">
    <xsl:for-each select="column">
      <xsl:value-of select="key('columnid',@ref)/@name"/>
      <xsl:if test="position() != last()"><xsl:text>,</xsl:text></xsl:if>
    </xsl:for-each>
  </xsl:template>

  <!-- Index base create is pretty portable
       @todo put these back together?
  -->
  <xsl:template match="table/constraints/index">
    <xsl:text>CREATE INDEX </xsl:text>
    <xsl:value-of select="@name"/> ON 
    <xsl:if test="$tableprefix != ''">
      <xsl:value-of select="$tableprefix"/>
      <xsl:text>_</xsl:text>
    </xsl:if>
    <xsl:value-of select="../../@name"/> (<xsl:call-template name="columnrefscsv"/>);
  </xsl:template>

  <xsl:template match="table/constraints/unique">
    <xsl:text>CREATE UNIQUE INDEX </xsl:text>
    <xsl:value-of select="@name"/> ON 
    <xsl:if test="$tableprefix != ''">
      <xsl:value-of select="$tableprefix"/>
      <xsl:text>_</xsl:text>
    </xsl:if>
    <xsl:value-of select="../../@name"/> (<xsl:call-template name="columnrefscsv"/>);
  </xsl:template>

  <!-- Primary key creation -->
  <xsl:template match="table/constraints/primary">
    <xsl:text>ALTER TABLE </xsl:text>
    <xsl:if test="$tableprefix != ''">
      <xsl:value-of select="$tableprefix"/>
      <xsl:text>_</xsl:text>
    </xsl:if>
    <xsl:value-of select="../../@name"/>
    <xsl:for-each select="column">
      <xsl:for-each select="key('columnid',@ref)">
        <xsl:if test="@auto = 'true'">
          <xsl:text> CHANGE COLUMN </xsl:text>
          <xsl:value-of select="@name"/><xsl:text> </xsl:text>
          <xsl:value-of select="@name"/><xsl:text> </xsl:text>
          <xsl:call-template name="columnattributes">
            <xsl:with-param name="ignoreauto">false</xsl:with-param>
          </xsl:call-template>
          <xsl:text>, </xsl:text>
        </xsl:if>
      </xsl:for-each>
    </xsl:for-each>
    <xsl:text> ADD PRIMARY KEY (</xsl:text><xsl:call-template name="columnrefscsv"/>);
  </xsl:template>

  <xsl:template match="schema/description"/> <!-- @todo : find out if this has a useful thing -->
  <xsl:template match="index/description"/> <!-- @todo : find out if this has a useful thing -->
</xsl:stylesheet>