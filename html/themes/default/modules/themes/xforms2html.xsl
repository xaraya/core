<?xml version="1.0" encoding="utf-8"?>
<xsl:stylesheet version="1.0"
  xmlns:xsl="http://www.w3.org/1999/XSL/Transform" 
  xmlns:xf="http://www.w3.org/2002/xforms"
  xmlns="http://www.w3.org/1999/xhtml"
  xmlns:xar="http://xaraya.com/2004/blocklayout">
  
  <xsl:output method="xml" version="1.0" encoding="utf=8" indent="yes"/>
  <xsl:strip-space elements="*"/>

  <!-- Makes sure we do have some sort of ok-ish html document -->
  <xsl:template match="xar:template">
    <html>
      <head>
        <title>Testdocument</title>
      </head>
      <body>
        <xsl:apply-templates/>
      </body>
    </html>
  </xsl:template>
  
  <!-- Example of how an xf:input tag could be handled -->
  <xsl:template match="xf:input">
    <div>
      <xsl:attribute name="class">textInputContainer</xsl:attribute>
      <xsl:apply-templates />
      <input type="textbox" >
        <xsl:attribute name="name"><xsl:value-of select="@name"/></xsl:attribute>
      </input>
    </div>
  </xsl:template>
  
  <!-- Generic label handling -->
  <xsl:template match="xf:label">
    <label>
      <xsl:attribute name="title"><xsl:value-of select="../xf:hint"/></xsl:attribute>
      <xsl:apply-templates />
    </label>
  </xsl:template>
  
  <xsl:template match="xf:group/xf:label">
    <legend><xsl:apply-templates /></legend>
  </xsl:template>
    
  
  <!-- Hints are processed in context -->
  <xsl:template match="xf:hint"/>
  
  <!-- Simple group element implementation -->
  <xsl:template match="xf:group">
    <fieldset>
      <xsl:apply-templates />
    </fieldset>
  </xsl:template>
  
  <!-- No match? Copy it to the output -->
  <xsl:template match="node()|@*">
      <!-- Copy the current node -->
      <xsl:copy>
          <!-- Including any attributes it has and any child nodes -->
          <xsl:apply-templates select="@*|node()"/>
      </xsl:copy>
  </xsl:template>
</xsl:stylesheet>