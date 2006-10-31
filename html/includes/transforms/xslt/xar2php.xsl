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
    Issues to be researched:
    
    - [DONE] how to cross the border? i.e. how do parameters from the module get
         passed into the xslt processor (see parameter section below and xsltransformer.php )
    - how do we create a suiteable test suite (make a compilation of the core templates?)
    - can we make a stub inserting some random values for the template vars, so we can compare somehow
    - is merging with other output namespaces just a question of copying output (xhtml in our case)
    - how do we handle #$var# constructs?
      * ideally i want to handle it through separation of the template in two sections, data and presentation, 
        both in the xml domain: 
      * one way of doing that is to make #$var# ~ &var; but this is a pain to handle for XSLT, 
        since it assumes entities to be known/declared at transform time, which is clearly not the case
      * another separation mechanism is to create a "data" section (xml fragment) to go with the template: like
        <tpldata>
          <vars>
            <var name="var">value</var>
              ...
          </vars>
        </tpldata>
        or something like that, generated dynamically, From then on we can reach each var by using XPath expressions like
        /tpldata/vars/var[@name='varname']
        which sounds sort of attractive because it is almost exactly like the array stuff, but then XML compliant. It also
        means that we need to translate each and every template to this syntax.
    - the xarBLCompiler.php does some processing here and there, which of these need to stay in php, which
      of them can be done by xsl? We can take them on a case by case basis, since php functions can be called reasonably
      easy from within the transform, but each case is a weakness in portability
    - it really doesnt make sense anymore now to go through the hoops of registering custom tags etc. One
    xsl snippet for a custom tag, generating the right code is a lot easier. Note: this would also invalidate the whole
    GUI where tags are shown on screen and can be manually entered into the database, which is of questionable use anyway, apart
    from a debugging perspective.
    - go over all xar: tags attributes and decide how resolving should be handled
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
    We view php as one large processing instruction of xml without the xml 
    declaration 
  -->
  <xsl:output method="xml" omit-xml-declaration="yes" indent="yes" />
    
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
    
    - empty div/ elements bork everything 
  -->
  <xsl:preserve-space elements="div"/> 

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
  <!-- xar:blockgroup -->
  <xsl:include href="tags/blockgroup.xsl"/>
  <!-- xar:blocklayout -->
  <xsl:include href="tags/blocklayout.xsl"/>
  <!-- xar:break -->
  <xsl:include href="tags/break.xsl"/>
  <!-- xar:comment -->
  <xsl:include href="tags/comment.xsl"/>
  <!-- xar:else -->
  <xsl:include href="tags/else.xsl"/>
  <!-- xar:elseif -->
  <xsl:include href="tags/elseif.xsl"/>
  <!-- xar:for -->
  <xsl:include href="tags/for.xsl"/>
  <!-- xar:foreach -->
  <xsl:include href="tags/foreach.xsl"/>
  <!-- xar:if -->
  <xsl:include href="tags/if.xsl"/>
  <!-- xar:loop -->
  <xsl:include href="tags/loop.xsl"/>
  <!-- xar:ml -->
  <xsl:include href="tags/ml.xsl"/>
  <!-- xar:mlstring -->
  <xsl:include href="tags/mlstring.xsl"/>
  <!-- xar:mlvar -->
  <xsl:include href="tags/mlvar.xsl"/>
  <!-- xar:set -->
  <xsl:include href="tags/set.xsl"/>
  <!-- xar:style -->
  <xsl:include href="tags/style.xsl"/>
  <!-- xar:template -->
  <xsl:include href="tags/template.xsl"/>
  <!-- xar:var -->
  <xsl:include href="tags/var.xsl" />


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
        <!-- This is the point where we can do automatic translation 
             of textnodes without requiring xar:mlstring 
        -->
        <xsl:copy/>
      </xsl:otherwise>
    </xsl:choose>
</xsl:template>

<!--
    Text nodes
    - if it contains a #, it  might need resolving
    - if not, just output it.
-->
<xsl:template name="resolveText" >
  <xsl:param name="expr"/>
  <xsl:param name="nrOfHashes"
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
      <xsl:processing-instruction name="php">
        <xsl:text>echo </xsl:text>
        <xsl:call-template name="resolvePHP">
            <xsl:with-param name="expr" select="substring-before(substring-after($expr,'#'),'#')"/>
        </xsl:call-template>
        <xsl:text>;</xsl:text>
      </xsl:processing-instruction>
      
      <!-- ....#....#[....#....#....etc.] -->
      <xsl:call-template name="resolveText">
        <xsl:with-param name="expr" select="substring-after(substring-after($expr,'#'),'#')"/>
      </xsl:call-template>
    </xsl:otherwise>
  </xsl:choose>
</xsl:template>

<xsl:template match="text()">
  <xsl:call-template name="resolveText">
    <xsl:with-param name="expr" select="."/>
  </xsl:call-template>
</xsl:template>

<!-- xar:module -->
<xsl:template match="xar:module">
  <xsl:processing-instruction name="php">
    <xsl:choose>
      <xsl:when test="string-length(@module) = 0">
        <!-- Obviously this sucks -->
        <xsl:text>echo $_bl_mainModuleOutput;</xsl:text>
      </xsl:when>
      <xsl:otherwise>
        <!-- module attribute has a value -->
        <xsl:text>echo xarModFunc('</xsl:text>
        <xsl:value-of select="@module"/><xsl:text>','</xsl:text>
        <xsl:choose>
          <xsl:when test="string-length(@type) = 0">
            <xsl:text>user</xsl:text>
          </xsl:when>
          <xsl:otherwise>
            <xsl:value-of select="@type"/>
          </xsl:otherwise>
        </xsl:choose>
        <xsl:text>','</xsl:text>
        <xsl:choose>
          <xsl:when test="string-length(@func) = 0">
            <xsl:text>main</xsl:text>
          </xsl:when>
          <xsl:otherwise>
            <xsl:value-of select="@func"/>
          </xsl:otherwise>
        </xsl:choose>
        <!-- Add all other attributes -->
        <xsl:text>',array(</xsl:text>
        <xsl:for-each select="@*">
          <xsl:text>'</xsl:text><xsl:value-of select="name()"/><xsl:text>'</xsl:text>
          <xsl:text disable-output-escaping="yes">=&gt;'</xsl:text>
          <xsl:value-of select="."/><xsl:text>',</xsl:text>
        </xsl:for-each>
        <xsl:text>));</xsl:text>
      </xsl:otherwise>
    </xsl:choose>
  </xsl:processing-instruction>
</xsl:template>

<!-- Expression resolving in nodes-->
<xsl:template name="resolvePHP">
  <xsl:param name="expr"/>
  <xsl:value-of 
        select="php:functionString('BlockLayoutXSLTProcessor::phpexpression',string($expr))"
        disable-output-escaping="yes"/>
</xsl:template>

</xsl:stylesheet>
