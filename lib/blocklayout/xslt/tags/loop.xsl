<?xml version="1.0" encoding="utf-8"?>
<!DOCTYPE xsl:stylesheet [
<!ENTITY nl "&#xd;&#xa;">
]>
<xsl:stylesheet version="1.0" 
    xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
    xmlns:xar="http://xaraya.com/2004/blocklayout"   
    xmlns:php="http://php.net/xsl" 
    exclude-result-prefixes="php xar">

<xsl:template match="xar:loop">
  <!-- Create a unique name for this loop -->
  <xsl:variable name="loopName">
    <xsl:text>$loop_</xsl:text>
    <xsl:choose>
      <xsl:when test="not(@id)">
        <xsl:value-of select="generate-id()"/>
      </xsl:when>
      <xsl:otherwise>
        <xsl:value-of select="@id"/>
      </xsl:otherwise>
    </xsl:choose>
  </xsl:variable>

  <!-- Get back the uniqueName of the parent loop if we're nesting -->
  <!-- 
    CHECKME: what if this is divided over multiple templates, where one
    template is already transformed? will there be an id then? Probably not.
    However, chances are pretty good that if the templates are separate the inner
    loop wont use the outer loop's stuff.
    We could add a piece of code like isset($loop) ?
  -->
  <xsl:variable name="parentID">
    <xsl:value-of select="generate-id(ancestor::xar:loop)"/>
  </xsl:variable>
  
  <!-- Start the transformation -->
  <xsl:processing-instruction name="php">
    <!-- See if we need to serialize a previous loop -->
    <xsl:choose>
      <xsl:when test="string-length($parentID) &gt; 0">
        <xsl:text>$loop_</xsl:text>
        <xsl:value-of select="$parentID"/>
        <xsl:text>_save = serialize($loop);</xsl:text>
      </xsl:when>
      <xsl:otherwise>
        <xsl:text>$loop = (object) null;</xsl:text>
      </xsl:otherwise>        
    </xsl:choose>
    
    <!-- Loop initialization -->
    <xsl:value-of select="$loopName"/><xsl:text> = (object) null;</xsl:text>
    <xsl:text>$loop-&gt;index=-1;&nl;</xsl:text>
    
    <!-- Start the loop -->
    <xsl:text>foreach(</xsl:text>
    <xsl:call-template name="resolvePHP">
      <xsl:with-param name="expr" select="@name"/>
    </xsl:call-template>
    <xsl:text> as </xsl:text>
    <xsl:value-of select="$loopName"/><xsl:text>-&gt;key =&gt; </xsl:text>
    <xsl:value-of select="$loopName"/><xsl:text>-&gt;item) {&nl;</xsl:text>
    
    <!-- Loop body, set index, key, item and number (bleh)-->
    <xsl:value-of select="$loopName"/><xsl:text>-&gt;index++;&nl;</xsl:text>
    <xsl:text>$loop-&gt;index = </xsl:text><xsl:value-of select="$loopName"/><xsl:text>-&gt;index;&nl;</xsl:text>
    <xsl:text>$loop-&gt;key   = </xsl:text><xsl:value-of select="$loopName"/><xsl:text>-&gt;key;&nl;</xsl:text>
    <xsl:text>$loop-&gt;item  =&amp; </xsl:text><xsl:value-of select="$loopName"/><xsl:text>-&gt;item;&nl;</xsl:text>
    
    <!-- If the loop had an id, use it, otherwise generate one -->
    <xsl:text>$loop-&gt;</xsl:text>
    <xsl:choose>
      <xsl:when test="not(@id)">
        <xsl:value-of select="generate-id()"/>
      </xsl:when>
      <xsl:otherwise>
        <xsl:value-of select="@id"/>
      </xsl:otherwise>
    </xsl:choose>
    <xsl:text>=&amp; </xsl:text><xsl:value-of select="$loopName"/><xsl:text>;&nl;</xsl:text> 
  </xsl:processing-instruction>
  
  <!-- Apply what's inside the loop -->
  <xsl:apply-templates />
  
  <!-- Loop finalization -->
  <xsl:processing-instruction name="php">
    <xsl:if test="string-length($parentID) &gt; 0">
      <!-- Restore outer loop -->
      <xsl:text>$loop = unserialize($loop_</xsl:text>
      <xsl:value-of select="$parentID"/>
      <xsl:text>_save);&nl;</xsl:text>
    </xsl:if>
    <xsl:text>}</xsl:text>
  </xsl:processing-instruction>
</xsl:template>

</xsl:stylesheet>
