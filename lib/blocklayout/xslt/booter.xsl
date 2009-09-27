<?xml version="1.0" encoding="utf-8"?>
<xsl:stylesheet version="1.0"
                xmlns:xsl="http://www.w3.org/1999/XSL/Transform" >

  <xsl:param name="defaults"/> 
  <xsl:param name="bltags"/> 
  <xsl:param name="clienttags"/> 

  <xsl:template match="xsl:includedefaults">
    <xsl:call-template name="includefile">
       <xsl:with-param name="string" select="$defaults" />
    </xsl:call-template>
  </xsl:template>

  <xsl:template match="xsl:includebltags">
    <xsl:call-template name="includefile">
       <xsl:with-param name="string" select="$bltags" />
    </xsl:call-template>
  </xsl:template>

  <xsl:template match="xsl:includeclienttags">
  <xsl:text select="$clienttags" />
    <xsl:call-template name="includefile">
       <xsl:with-param name="string" select="$clienttags" />
    </xsl:call-template>
  </xsl:template>

  <xsl:template match="@*|node()">
    <xsl:copy>
      <xsl:apply-templates select="@*|node()"/>
    </xsl:copy>
  </xsl:template>

  <xsl:template name="includefile">
    <xsl:param name="string" />
    <xsl:param name="delimiter" select="','" />
    <xsl:choose>
     <xsl:when test="$delimiter and contains($string, $delimiter)">
       <xsl:variable name="tagfile" select="substring-before($string,$delimiter)"/>
       <xsl:copy-of select="document($tagfile)/xsl:stylesheet/*"/>
       <xsl:call-template name="includefile">
         <xsl:with-param name="string" select="substring-after($string,$delimiter)" />
         <xsl:with-param name="delimiter" select="$delimiter" />
       </xsl:call-template>
      </xsl:when>
      <xsl:otherwise>
       <xsl:variable name="tagfile" select="$string"/>
       <xsl:copy-of select="document($tagfile)/xsl:stylesheet/*"/>
      </xsl:otherwise>
   </xsl:choose>
  </xsl:template>
</xsl:stylesheet>