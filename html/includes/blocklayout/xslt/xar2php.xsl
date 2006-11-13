<?xml version="1.0" encoding="utf-8"?>
<!DOCTYPE xsl:stylesheet [
<!ENTITY nl "&#xd;&#xa;">
]>
<xsl:stylesheet version="1.0"
                xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
                xmlns:xar="http://xaraya.com/2004/blocklayout"
                xmlns:php="http://php.net/xsl"
                exclude-result-prefixes="php xar">
  <!--
    See DEVblocklayout.txt in the repository for DEV notes.
  -->

  <!--
    Imports

    Imports are like default templates for processing. They get a lower priority
    than anything in this file, and as such can be overridden without anything
    other than just defining the transform templates here.

    This ALSO means that by making the BL compiler accept a custom xsl file
    which imports THIS file has the same effect as having your own customisation
    file in one place, by inserting your templates in that file. :-) :-)

    Nuf said, lets begin.
  -->

  <!--
    The default identity transform which will just copy over anything we dont match
  -->
  <xsl:import href="defaults/identity.xsl"/>

  <!--
    Parameters, we'd like as few as possible
  -->
  <xsl:param name="bl_dirname"/>
  <xsl:param name="bl_filename"/>


  <!--
    We produce an UTF-8 encoded XML document as output. As we compile the
    each document to a (hopefully valid) php script ultimately. We leave out
    the xml declaration, as PHP interprets that as the start of a PHP block.
  -->
  <xsl:output  method="xml" omit-xml-declaration="yes" indent="yes" encoding="UTF-8"/>


  <!--
    Spacing

    We want our output as compact as possible, so we start by stripping
    all non-significant whitespace (which will also collapse empty tags)
    and then correct for the elements which cause us trouble. In theory
    there shouldnt be any, but alas.
  -->
  <xsl:strip-space elements="*" />

  <!--
    Problematic elements

    - empty div elements bork everything, so first, leave their spacing alone
    which doesnt influence correctness, but saves a whole lot of trouble.
    - empty script element work in safari, but not in FF
    @todo this is specific for XHTML output, isolate it.
-->
  <xsl:preserve-space elements="div script"/>
<!--
    - second: if there is no child content (of whatever type), we should not
    have to do anything, but (X)HTML doesn't like an element which is empty,
    so if we wanted to be friendly we could do this:
    (if there is *just* a PI as child it will still break though)
  -->
<!--
    <xsl:template match="div">
    <xsl:copy>
      <xsl:apply-templates select="@*|node()"/>
      <xsl:if test="not(node()[not(self::comment())])">
        <xsl:comment>empty <xsl:value-of select="name()"/> tag workaround</xsl:comment>
      </xsl:if>
    </xsl:copy>
  </xsl:template>
-->

  <!--
    Start of the transform usually starts with matching the root, so do we
  -->
  <xsl:template match="/">
    <xsl:apply-templates />
  </xsl:template>

  <!--
    First we do some simple stuff to get rid of things we dont really care
    about yet
  -->
  <!-- xar processing instructions, ignore them for now -->
  <xsl:template match="/processing-instruction('xar')"/>
  <!-- xml comments are ignored -->
  <xsl:template match="comment()"/>

  <!--
    Xaraya specific tag implementations are brought in here.

    @todo: should we use import or include here?
  -->
  <!-- xar:additional-styles -->
  <xsl:include href="tags/additional-styles.xsl"/>
  <!-- xar:base-include-javascript -->
  <xsl:include href="tags/base-include-javascript.xsl"/>
  <!-- xar:base-render-javascript -->
  <xsl:include href="tags/base-render-javascript.xsl"/>
  <!-- xar:blockgroup -->
  <xsl:include href="tags/blockgroup.xsl"/>
  <!-- xar:blocklayout -->
  <xsl:include href="tags/blocklayout.xsl"/>
  <!-- xar:break -->
  <xsl:include href="tags/break.xsl"/>
  <!-- xar:comment -->
  <xsl:include href="tags/comment.xsl"/>
  <!-- xar:continue -->
  <xsl:include href="tags/continue.xsl"/>

  <!-- TODO: organize this -->
  <!-- xar:data-view/output/label etc. -->
  <xsl:include href="tags/data.xsl"/>

  <!-- xar:else -->
  <xsl:include href="tags/else.xsl"/>
  <!-- xar:elseif -->
  <xsl:include href="tags/elseif.xsl"/>
  <!-- xar:event -->
  <xsl:include href="tags/event.xsl"/>
  <!-- xar:for -->
  <xsl:include href="tags/for.xsl"/>
  <!-- xar:foreach -->
  <xsl:include href="tags/foreach.xsl"/>
  <!-- xar:if -->
  <xsl:include href="tags/if.xsl"/>
  <!-- xar:loop -->
  <xsl:include href="tags/loop.xsl"/>

  <!-- MLS functionality -->
  <xsl:include href="tags/mls.xsl"/>

  <!-- xar:module -->
  <xsl:include href="tags/module.xsl"/>
  <!-- xar:sec -->
  <xsl:include href="tags/sec.xsl"/>
  <!-- xar:set -->
  <xsl:include href="tags/set.xsl"/>
  <!-- xar:style -->
  <xsl:include href="tags/style.xsl"/>
  <!-- xar:template -->
  <xsl:include href="tags/template.xsl"/>
  <!-- xar:var -->
  <xsl:include href="tags/var.xsl" />
  <!-- xar:while -->
  <xsl:include href="tags/while.xsl"/>

<!-- text nodes in php mode -->
<xsl:template match="xar:set/text()">
    <xsl:choose>
      <xsl:when test="substring(normalize-space(.),1,1) = '#'">
        <!-- The string starts with # so, let's resolve it -->
        <xsl:call-template name="resolvePHP">
          <xsl:with-param name="expr" select="normalize-space(.)"/>
        </xsl:call-template>
      </xsl:when>
      <xsl:otherwise>
        <!-- No start with #, just copy it -->
        <xsl:copy/>
      </xsl:otherwise>
    </xsl:choose>
</xsl:template>


<!--
    Utility template for resolving text nodes. It recursively resolves
    #-pairs from left to right. Pre- and Post- hash content are treated
    as text.

    The param $expr contains the  value of a text node holding the expression
    to resolve.
    @todo leave #(1) constructs alone?
-->
<xsl:template name="resolveText" >
  <xsl:param name="expr"/>

  <!--
    <xsl:text>[EXPR]</xsl:text><xsl:value-of select="$expr"/><xsl:text>[END EXPR]</xsl:text>
  -->
  <xsl:variable name="nrOfHashes"
      select="string-length($expr) - string-length(translate($expr, '#', ''))"/>

  <xsl:choose>
    <!-- If we have zero or one hash, just output the text node -->
    <xsl:when test="$nrOfHashes &lt; 2">
        <xsl:value-of select="$expr"/>
    </xsl:when>
    <!-- Resolve left to right -->
    <xsl:otherwise>
      <!-- more than two, so in general ....#....#....#....#.... etc. -->

      <!-- [....]#....#.... : get the first part out of the way -->
      <xsl:value-of select="substring-before($expr,'#')"/>

      <!-- Resolve the part in between -->
      <!-- Left at this point: ....#[....]#.... -->
      <xsl:if test="substring-before(substring-after($expr,'#'),'#') !=''">
        <xsl:processing-instruction name="php">
          <xsl:text>echo </xsl:text>
          <xsl:call-template name="resolvePHP">
              <xsl:with-param name="expr" select="substring-before(substring-after($expr,'#'),'#')"/>
          </xsl:call-template>
          <xsl:text>;</xsl:text>
        </xsl:processing-instruction>
      </xsl:if>

      <!-- ....#....#[....#....#....etc.] -->
      <xsl:call-template name="resolveText">
        <xsl:with-param name="expr" select="substring-after(substring-after($expr,'#'),'#')"/>
      </xsl:call-template>
    </xsl:otherwise>
  </xsl:choose>
</xsl:template>

<!--
  For all text nodes, resolve expressions within
-->
<!--
  <xsl:template match="text()">
  <xsl:call-template name="resolveText">
    <xsl:with-param name="expr" select="."/>
  </xsl:call-template>
</xsl:template>
-->
<!-- For now, dont resolve inline CSS -->
<xsl:template match="style/text()">
  <xsl:apply-imports />
</xsl:template>

<!-- Expression resolving in nodes-->
<xsl:template name="resolvePHP">
  <xsl:param name="expr"/>
  <xsl:value-of
        select="php:functionString('BlockLayoutXSLTProcessor::phpexpression',string($expr))"
        disable-output-escaping="yes"/>
</xsl:template>

<!--
  Utility template to be able to generate output when something is wrong, but
  not terribly:
  - Any xar tag we dont match, we highlight in the output
  - When a typo was made for example in attributes or something like that
  @todo: best way i could come up with to do this doctype agnostic, anything better?
-->
<xsl:template name="oops">
  <xsl:param name="label" select="'UNKNOWN ERROR'"/>
  <!-- Insert a CDATA section preceded by a 'weird' symbol -->
  <!-- x2707 is the 'radiation symbol' if it displays, you're config is good,
  otherwise you'll have to settle for a ? or an empty square or something like that -->
  <xsl:text disable-output-escaping="yes">&#x2707;
&lt;![CDATA[</xsl:text>
    <xsl:value-of select="$label"/>
    <xsl:text>: --- </xsl:text>
    <xsl:value-of select="name()"/>
    <xsl:text> </xsl:text>
    <xsl:for-each select="@*">
      <xsl:value-of select="name()"/>
      <xsl:text>="</xsl:text>
      <xsl:value-of select="."/>
      <xsl:text>" </xsl:text>
    </xsl:for-each>
    <xsl:text>---</xsl:text>
  <xsl:text disable-output-escaping="yes"> ]]&gt; </xsl:text>
</xsl:template>

<xsl:template match="xar:*">
  <xsl:param name="label" select="'MISSING TAG IMPLEMENTATION'"/>
  <!-- Insert a CDATA section preceded by a 'weird' symbol -->
  <!-- x2707 is the 'radiation symbol' if it displays, you're config is good,
  otherwise you'll have to settle for a ? or an empty square or something like that -->
  <xsl:text disable-output-escaping="yes">&#x2707;
&lt;![CDATA[</xsl:text>
    <xsl:value-of select="$label"/>
    <xsl:text>: </xsl:text>
    <xsl:apply-imports />
  <xsl:text disable-output-escaping="yes"> ]]&gt; </xsl:text>
</xsl:template>

<!--
  Utility template to replace a string with another.

  The default is to replace ' with \', but
  by specifying the parameters, other replacements can be performed
  as well

  @param string source contains the source string in which replacements are needed
  @param string from   contains what needs to be replaced
  @param string to     contains what will be the replacement.
-->
<xsl:template name="replace" >
  <!-- Specifiy the parameters -->
  <xsl:param name="source"/>
  <xsl:param name="from" select="&quot;'&quot;"/>
  <xsl:param name="to"   select="&quot;\'&quot;"/>

  <!-- Make it safe when there is no such character -->
  <xsl:choose>
    <xsl:when test="contains($source,$from)">
      <xsl:value-of select="substring-before($source,$from)"/>
      <xsl:value-of select="$to"/>
      <!-- Recurse -->
      <xsl:call-template name="replace">
        <xsl:with-param name="source" select="substring-after($source,$from)"/>
        <xsl:with-param name="from"   select="$from"/>
        <xsl:with-param name="to"     select="$to"/>
      </xsl:call-template>
    </xsl:when>
    <xsl:otherwise>
      <xsl:value-of select="$source"/>
    </xsl:otherwise>
  </xsl:choose>
</xsl:template>

</xsl:stylesheet>
