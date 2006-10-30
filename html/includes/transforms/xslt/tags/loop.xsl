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
  <xsl:processing-instruction name="php">
    <xsl:text disable-output-escaping="yes">
      $loop_1=(object) null; $loop_1-&gt;index=-1;$loop_1-&gt;number=1;
      foreach(</xsl:text>
    <xsl:value-of select="@name"/>
    <xsl:text disable-output-escaping="yes"> as $loop_1-&gt;key =&gt; $loop_1-&gt;item ) {
      $loop=(object) null; $loop_1-&gt;index++;
      $loop-&gt;index = $loop_1-&gt;index;
      $loop-&gt;key = $loop_1-&gt;key;
      $loop-&gt;item =&amp; $loop_1-&gt;item;
      $loop-&gt;number = $loop_1-&gt;number;</xsl:text>
  </xsl:processing-instruction>

  <xsl:apply-templates/>

  <xsl:processing-instruction name="php">
    <xsl:text>}</xsl:text>
  </xsl:processing-instruction>
</xsl:template>
</xsl:stylesheet>
