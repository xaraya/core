<?xml version="1.0" encoding="utf-8"?>
<!DOCTYPE xsl:stylesheet [
<!ENTITY nl "&#xd;&#xa;">
]>
<xsl:stylesheet version="1.0" 
    xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
    xmlns:xar="http://xaraya.com/2004/blocklayout"   
    xmlns:php="http://php.net/xsl" 
    exclude-result-prefixes="php xar">

  <xsl:template match="xar:img">
    <xsl:choose>
      <xsl:when test="@file">
        <xsl:variable name="image-url">
            <xsl:call-template name="get_image"/>
        </xsl:variable>
        <xsl:choose>
          <!-- just return the url to the image -->
          <xsl:when test="@render = 'false'">
            <xsl:processing-instruction name="php">
              <xsl:text>echo </xsl:text>
              <xsl:copy-of select="$image-url"/>
            </xsl:processing-instruction>
          </xsl:when>
          <!-- return an html img tag -->
          <xsl:otherwise>
            <xsl:element name="img">
              <!-- attach src attribute using image url as value -->
              <xsl:attribute name="src">
                <!-- can't use processing instruction as an attribute value
                     so, we'll cheat... -->
                <xsl:text>#</xsl:text>
                <xsl:copy-of select="$image-url"/>
                <xsl:text>#</xsl:text>
              </xsl:attribute>
              <!-- attach any other attributes passed to the tag -->  
              <xsl:call-template name="atts2element">
                <xsl:with-param 
                  name="nodeset" 
                  select="@*[name() != 'module' and name() != 'file' and name() != 'scope' and name() != 'property' and name() != 'render']"/>
              </xsl:call-template>
            </xsl:element>            
          </xsl:otherwise>
        </xsl:choose>
      </xsl:when>
    </xsl:choose>
  </xsl:template>

  <!-- when called inside xar:set only ever return the url --> 
  <xsl:template match="xar:set/xar:img">
    <xsl:call-template name="get_image"/>
  </xsl:template>  

  <!-- wrapper for the getimage api function -->
  <xsl:template name="get_image">
    <xsl:text>trim(xarMod::apiFunc('themes','user','getimage',</xsl:text>
      <xsl:call-template name="atts2args">
        <xsl:with-param 
          name="nodeset"
          select="@*[name() != 'alt' and name() != 'title' and name() != 'height' and name() != 'width' and  name() != 'class' and name() != 'id' and name() != 'style' and name() != 'onabort' and name() != 'onclick' and name() != 'ondblclick' and name() != 'onmousedown' and  name() != 'onmousemove' and name() != 'onmouseout' and name() != 'onmouseover' and name() != 'onmouseup' and name() != 'onkeydown' and name() != 'onkeypress' and name() != 'onkeyup']"/>
      </xsl:call-template>
    <xsl:text>));</xsl:text>
  </xsl:template>

  <!-- attach attributes passed from tag to the element being returned -->
  <xsl:template name="atts2element">
    <xsl:param name="nodeset"/>
    <xsl:if test="$nodeset">
      <xsl:for-each select="$nodeset">
        <xsl:attribute name="{name()}">
          <xsl:choose>
            <xsl:when test="starts-with(normalize-space(.),'$') or not(string(number(.))='NaN')">
              <!-- can't use processing instruction as an attribute value
                   so, we'll cheat... -->
              <xsl:text>#</xsl:text>
              <xsl:value-of select="."/>
              <xsl:text>#</xsl:text>
            </xsl:when>
            <xsl:otherwise>
              <xsl:value-of select="."/>
            </xsl:otherwise>
          </xsl:choose>
        </xsl:attribute>
      </xsl:for-each>
    </xsl:if>
  </xsl:template>

</xsl:stylesheet>