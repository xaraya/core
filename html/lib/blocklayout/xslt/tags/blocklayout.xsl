<?xml version="1.0" encoding="utf-8"?>
<!DOCTYPE xsl:stylesheet [
<!ENTITY nl "&#xd;&#xa;">
]>
<xsl:stylesheet version="1.0"
    xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
    xmlns:xar="http://xaraya.com/2004/blocklayout"
    xmlns:php="http://php.net/xsl"
    exclude-result-prefixes="php xar">

  <xsl:template match="xar:blocklayout">
    <xsl:processing-instruction name="php">
      <xsl:text>$_bl_locale  = xarMLSGetCurrentLocale();&nl;</xsl:text>
      <xsl:text>$_bl_charset = xarMLSGetCharsetFromLocale($_bl_locale);&nl;</xsl:text>
      <xsl:text>header("Content-Type:</xsl:text>
      <xsl:value-of select="@content"/>
      <xsl:text>; charset = $_bl_charset");&nl;</xsl:text>
    </xsl:processing-instruction>

    <!-- Generate the doctype
      If we pass a dtd attribute, use that.
      Otherwise get the default value
    -->
    <xsl:text disable-output-escaping="yes">&lt;!DOCTYPE </xsl:text>
    <xsl:choose>
      <xsl:when test="not(@dtd)">
        <xsl:call-template name="dtdlist">
          <xsl:with-param name="dtd" select="$bl_doctype"/>
        </xsl:call-template>
      </xsl:when>
      <xsl:otherwise>
        <xsl:call-template name="dtdlist">
          <xsl:with-param name="dtd" select="@dtd"/>
        </xsl:call-template>
      </xsl:otherwise>
    </xsl:choose>
    <xsl:text>&nl;</xsl:text>
    <xsl:apply-templates />
  </xsl:template>

  <xsl:template name="dtdlist">
    <xsl:param name="dtd" select="@dtd"/>

     <!-- http://www.w3.org/QA/2002/04/valid-dtd-list.html -->
     <xsl:choose>
      <xsl:when test="$dtd = 'html2'">
        <xsl:text disable-output-escaping="yes">html PUBLIC "-//IETF//DTD HTML 2.0//EN"&gt;</xsl:text>
      </xsl:when>
      <xsl:when test="$dtd = 'html32'">
        <xsl:text disable-output-escaping="yes">HTML PUBLIC "-//W3C//DTD HTML 3.2 Final//EN"&gt;</xsl:text>
      </xsl:when>
      <xsl:when test="$dtd = 'html401-strict'">
        <xsl:text disable-output-escaping="yes">HTML PUBLIC "-//W3C//DTD HTML 4.01//EN"  "http://www.w3.org/TR/html4/strict.dtd"&gt;</xsl:text>
      </xsl:when>
      <xsl:when test="$dtd = 'html401-transitional'">
        <xsl:text disable-output-escaping="yes">HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN"  "http://www.w3.org/TR/html4/loose.dtd"&gt;</xsl:text>
      </xsl:when>
      <xsl:when test="$dtd = 'html401-frameset'">
        <xsl:text disable-output-escaping="yes">HTML PUBLIC "-//W3C//DTD HTML 4.01 Frameset//EN"  "http://www.w3.org/TR/html4/frameset.dtd"&gt;</xsl:text>
      </xsl:when>
      <xsl:when test="$dtd = 'xhtml1-strict'">
        <xsl:text disable-output-escaping="yes">html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd"&gt;</xsl:text>
      </xsl:when>
      <xsl:when test="$dtd = 'xhtml1-transitional'">
        <xsl:text disable-output-escaping="yes">html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"  "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd"&gt;</xsl:text>
      </xsl:when>
      <xsl:when test="$dtd = 'xhtml1-frameset'">
        <xsl:text disable-output-escaping="yes">html PUBLIC "-//W3C//DTD XHTML 1.0 Frameset//EN"  "http://www.w3.org/TR/xhtml1/DTD/xhtml1-frameset.dtd"&gt;</xsl:text>
      </xsl:when>
      <xsl:when test="$dtd = 'xhtml11'">
       <xsl:text disable-output-escaping="yes">html PUBLIC "-//W3C//DTD XHTML 1.1//EN" "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd"&gt;</xsl:text>
      </xsl:when>
      <xsl:when test="$dtd = 'mathml101'">
        <xsl:text disable-output-escaping="yes">math SYSTEM "http://www.w3.org/Math/DTD/mathml1/mathml.dtd"&gt;</xsl:text>
      </xsl:when>
      <xsl:when test="$dtd = 'mathml2'">
        <xsl:text disable-output-escaping="yes">math PUBLIC "-//W3C//DTD MathML 2.0//EN" "http://www.w3.org/TR/MathML2/dtd/mathml2.dtd"&gt;</xsl:text>
      </xsl:when>
      <xsl:when test="$dtd = 'svg10'">
        <xsl:text disable-output-escaping="yes">svg PUBLIC "-//W3C//DTD SVG 1.0//EN" "http://www.w3.org/TR/2001/REC-SVG-20010904/DTD/svg10.dtd"&gt;</xsl:text>
      </xsl:when>
      <xsl:when test="$dtd = 'svg11'">
        <xsl:text disable-output-escaping="yes">svg PUBLIC "-//W3C//DTD SVG 1.1//EN" "http://www.w3.org/Graphics/SVG/1.1/DTD/svg11.dtd"&gt;</xsl:text>
      </xsl:when>
      <xsl:when test="$dtd = 'svg11-basic'">
        <xsl:text disable-output-escaping="yes">svg PUBLIC "-//W3C//DTD SVG 1.1 Basic//EN" "http://www.w3.org/Graphics/SVG/1.1/DTD/svg11-basic.dtd"&gt;</xsl:text>
      </xsl:when>
      <xsl:when test="$dtd = 'svg11-tiny'">
        <xsl:text disable-output-escaping="yes">svg PUBLIC "-//W3C//DTD SVG 1.1 Tiny//EN" "http://www.w3.org/Graphics/SVG/1.1/DTD/svg11-tiny.dtd"&gt;</xsl:text>
      </xsl:when>
      <xsl:when test="$dtd = 'xhtml-math-svg'">
        <xsl:text disable-output-escaping="yes">html PUBLIC "-//W3C//DTD XHTML 1.1 plus MathML 2.0 plus SVG 1.1//EN" "http://www.w3.org/2002/04/xhtml-math-svg/xhtml-math-svg.dtd"&gt;</xsl:text>
      </xsl:when>
      <xsl:when test="$dtd = 'svg-xhtml-math'">
        <xsl:text disable-output-escaping="yes">svg:svg PUBLIC  "-//W3C//DTD XHTML 1.1 plus MathML 2.0 plus SVG 1.1//EN" "http://www.w3.org/2002/04/xhtml-math-svg/xhtml-math-svg.dtd"&gt;</xsl:text>
      </xsl:when>
      <!-- <chris/> the rss 0.91 dtd formerly at http://my.netscape.com/publish/formats/rss-0.91.dtd 
           was dropped in 2007, we're using the archive version from web.archive.org, 
           it may be more desirable/reliable to copy and serve this locally -->
      <xsl:when test="$dtd = 'rss'">
        <xsl:text disable-output-escaping="yes">rss PUBLIC "-//Netscape Communications//DTD RSS 0.91//EN"   "http://web.archive.org/web/20030601160443/http://my.netscape.com/publish/formats/rss-0.91.dtd"&gt;</xsl:text>
      </xsl:when>
      <!-- html5 [NOT a standard yet] -->
      <xsl:when test="$dtd = 'html5'">
        <xsl:text disable-output-escaping="yes">html&gt;</xsl:text>
      </xsl:when>
    </xsl:choose>
  </xsl:template>

</xsl:stylesheet>
