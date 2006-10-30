<?xml version="1.0" encoding="utf-8"?>
<!DOCTYPE xsl:stylesheet SYSTEM "includes/transforms/xar_entities.dtd">

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
  -->

  <!-- Imports 
    Imports are like default templates for processing. They get a lower priority
    than anything in this file, and as such can be overridden without anything
    other than just defining the transform templates here. 
    
    This ALSO means that by making the BL compiler accept a custom xsl file
    which imports THIS file has the same effect as having your own customisation
    file in one place, by inserting your templates in that file. :-) :-)

    Nuf said, lets begin.
  -->
  
  <!-- The default identity transform which will just copy over anything we dont match -->
  <xsl:import href="defaults/identity.xsl"/>
  
  <!-- Parameters, we'd like as few as possible -->
  <xsl:param name="bl_dirname"/>
  <xsl:param name="bl_filename"/>
  
  <!-- Definitions -->
  
  <!-- Start of stylesheet -->
  <xsl:variable name="nl"><xsl:text>
</xsl:text></xsl:variable>

<!-- We view php as one large processing instruction of xml without the xml declaration -->
<xsl:output method="xml" omit-xml-declaration="yes" indent="yes" />
<xsl:strip-space elements="*" />
<xsl:preserve-space elements="div"/> <!-- empty div/ elements bork everything -->

<!-- We're creating a php document --> 
<xsl:template match="/">
  <xsl:apply-templates />
</xsl:template>

<!-- 
     First we do some simple stuff to get rid of 
     things we dont really care about yet
-->

<!-- xar processing instructions, ignore them for now -->
<xsl:template match="/processing-instruction('xar')"/>
<!-- xml comments are ignored -->
<xsl:template match="comment()"/>

<!-- 4.3.1 <xar:blocklayout/> 
  @todo: can we do this earlier, perhaps in the setup phase, when we are still in PHP
-->
<xsl:template match="xar:blocklayout">
  <xsl:processing-instruction name="php">
      <xsl:text>
        $_bl_locale  = xarMLSGetCurrentLocale();
        $_bl_charset = xarMLSGetCharsetFromLocale($_bl_locale);
        header("Content-Type:</xsl:text>
      <xsl:value-of select="@content"/>
      <xsl:text>; charset = $_bl_charset");</xsl:text>
  </xsl:processing-instruction>
  
  <!-- Generate the doctype 
    @todo: can we do this earlier?
  -->
  <xsl:text disable-output-escaping="yes">&lt;!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd"&gt;
  </xsl:text>
  <xsl:apply-templates />
</xsl:template>

<!-- 4.3.5 <xar:comment/> -->
<xsl:template match="xar:comment">
  <xsl:comment>
    <xsl:call-template name="resolveText">
      <xsl:with-param name="expr" select="."/>
    </xsl:call-template>
  </xsl:comment>
</xsl:template>

<!-- xar:set -->
<xsl:template name="xar-set" match="xar:set">
  <xsl:processing-instruction name="php">
    <xsl:text>$</xsl:text>
    <xsl:value-of select="@name"/>
    <xsl:text>=</xsl:text>
    
    <xsl:apply-templates/>

    <xsl:text>;
      $_bl_data['</xsl:text>
    <xsl:value-of select="@name"/>
    <xsl:text>']=$</xsl:text>
    <xsl:value-of select="@name"/>
    <xsl:text>;</xsl:text>
    <xsl:value-of select="$nl"/>
  </xsl:processing-instruction>
</xsl:template>

<!-- xar:blockgroup -->
<xsl:template name="xar-blockgroup" match="xar:blockgroup">
  <xsl:processing-instruction name="php">
    <xsl:text>echo </xsl:text>
    <xsl:call-template name="blockgroup_code"/>
  </xsl:processing-instruction>
</xsl:template>

<xsl:template match="xar:set/xar:blockgroup">
  <xsl:call-template name="blockgroup_code"/>
</xsl:template>

<xsl:template name="blockgroup_code">
  <xsl:choose>
    <xsl:when test="child::node()">
      <xsl:text>$_bl_blockgroup_template = '></xsl:text>
      <xsl:value-of select="@template"/>
      <xsl:text>';</xsl:text>
      <xsl:value-of select="$nl"/>
      
      <xsl:apply-templates />
      
      <xsl:text>unset($_bl_blockgroup_template);</xsl:text>
    </xsl:when>
    <xsl:when test="not(child::node())">
      <xsl:text>xarBlock_renderGroup('</xsl:text><xsl:value-of select="@name"/>
      <xsl:text>');</xsl:text>
      <xsl:value-of select="$nl"/>
    
      <xsl:apply-templates />
    </xsl:when>
    <xsl:value-of select="$nl"/>
  </xsl:choose>
</xsl:template>

<!-- xar:var -->
<xsl:template name="xar-var" match="xar:var">
  <xsl:processing-instruction name="php">
    <xsl:text>echo </xsl:text>
    <xsl:choose>
      <xsl:when test="@scope = 'module'">
        <xsl:text>xarModVars::get('</xsl:text><xsl:value-of select="@module"/><xsl:text>', '</xsl:text>
        <xsl:value-of select="@name"/><xsl:text>')</xsl:text>
      </xsl:when>
      <xsl:when test="@scope = 'local' or not(@scope)">
        <xsl:text>$</xsl:text><xsl:value-of select="@name"/>
      </xsl:when>
    </xsl:choose>
    <xsl:text>;</xsl:text>
  </xsl:processing-instruction>
</xsl:template>

<xsl:template match="xar:set/xar:var">
    <xsl:choose>
      <xsl:when test="@scope = 'module'">
        <xsl:text>xarModVars::get('</xsl:text><xsl:value-of select="@module"/><xsl:text>', '</xsl:text>
        <xsl:value-of select="@name"/><xsl:text>')</xsl:text>
      </xsl:when>
      <xsl:when test="@scope = 'local' or not(@scope)">
        <xsl:text>$</xsl:text><xsl:value-of select="@name"/>
      </xsl:when>
    </xsl:choose>
</xsl:template>

<!-- xar:template -->
<xsl:template name="xar-template" match="xar:template">
  <xsl:choose>
    <!-- If the template tag does not contain anything, treate as in 1.x -->
    <xsl:when test="not(node())">
      <xsl:processing-instruction name="php">
        <xsl:text>echo </xsl:text>
        <xsl:choose>
          <xsl:when test="@type='theme'">
            <xsl:text>xarTpl_includeThemeTemplate('</xsl:text>
            <xsl:value-of select="@file"/>
            <xsl:text>',$_bl_data);</xsl:text>
          </xsl:when>
          <xsl:when test="@type='system'">
            <!-- The name is to be interpreted relative to the file we're parsing now -->
            <xsl:text>xarTplFile('</xsl:text>
            <xsl:value-of select="$bl_dirname"/><xsl:text>/</xsl:text><xsl:value-of select="@file"/>
            <xsl:text>',$_bl_data );</xsl:text>
          </xsl:when>
          <xsl:otherwise>
            <xsl:text>xarTpl_includeModuleTemplate(</xsl:text>
            <xsl:choose>
              <xsl:when test="@module != ''">
                <xsl:text>'</xsl:text>
                <xsl:value-of select="@module"/>
                <xsl:text>'</xsl:text>
              </xsl:when>
              <xsl:otherwise>
                <xsl:text>$_bl_module_name</xsl:text>
              </xsl:otherwise>
            </xsl:choose>
            <xsl:text>, '</xsl:text>
            <xsl:value-of select="@file"/>
            <xsl:text>',$_bl_data);</xsl:text>
          </xsl:otherwise>
        </xsl:choose>
      </xsl:processing-instruction>
    </xsl:when>
    <xsl:otherwise>
      <!-- It's the root tag of a template file, no need to do anything yet -->
      <xsl:apply-templates/>
    </xsl:otherwise>
  </xsl:choose>
</xsl:template>

<!-- xar:if -->
<xsl:template match="xar:if">
  <xsl:processing-instruction name="php">
    <xsl:text>if(</xsl:text>
    <xsl:value-of 
        select="php:functionString('BlockLayoutXSLTProcessor::phpexpression',string(@condition))"
        disable-output-escaping="yes"/>
    <xsl:text>) {</xsl:text>
    <xsl:value-of select="$nl"/>
  </xsl:processing-instruction>
  
  <xsl:apply-templates/>
  
  <xsl:processing-instruction name="php">
    <xsl:text>}</xsl:text>
    <xsl:value-of select="$nl"/>
  </xsl:processing-instruction>
</xsl:template>

<!-- xar:elseif -->
<xsl:template match="xar:if/xar:elseif">
  <xsl:processing-instruction name="php">
    <xsl:text>} elseif(</xsl:text>
    <xsl:value-of 
        select="php:functionString('BlockLayoutXSLTProcessor::phpexpression',string(@condition))"
        disable-output-escaping="yes"/>
    <xsl:text>) {</xsl:text>
    <xsl:value-of select="$nl"/>
  </xsl:processing-instruction>
</xsl:template>

<!-- xar:else -->
<xsl:template match="xar:if/xar:else">
  <xsl:processing-instruction name="php">
    <xsl:text>} else {</xsl:text>
  </xsl:processing-instruction>
</xsl:template>

<!-- xar:foreach -->
<xsl:template match="xar:foreach">
  <xsl:processing-instruction name="php">
    <xsl:text>foreach(</xsl:text>
    <xsl:choose>
      <xsl:when test="@key!='' and @value!='' ">
        <xsl:value-of select="@in"/><xsl:text> as </xsl:text><xsl:value-of select="@key"/>
        <xsl:text disable-output-escaping="yes"> =&gt; </xsl:text><xsl:value-of select="@value"/>
      </xsl:when>
      <xsl:when test="@value!=''">
        <xsl:value-of select="@in"/><xsl:text> as </xsl:text><xsl:value-of select="@value"/>
      </xsl:when>
      <xsl:when test="@key!=''">
        <xsl:text>array_keys(</xsl:text>
        <xsl:value-of select="@in"/>
        <xsl:text>) as </xsl:text>
        <xsl:value-of select="@key"/>
      </xsl:when>
    </xsl:choose>
    <xsl:text>) {
    </xsl:text>
  </xsl:processing-instruction>

  <xsl:apply-templates/>

  <xsl:processing-instruction name="php">
    <xsl:text>}</xsl:text>
  </xsl:processing-instruction>
</xsl:template>

<!-- xar:mlstring -->
<xsl:template match="xar:mlstring">
  <xsl:processing-instruction name="php">
    <xsl:text>echo xarML('</xsl:text>
    <xsl:value-of 
        select="php:functionString('BlockLayoutXSLTProcessor::escape',string(.))"
        disable-output-escaping="yes" />
    <xsl:text>');</xsl:text>
  </xsl:processing-instruction>
</xsl:template>

<xsl:template match="xar:set/xar:mlstring">
    <xsl:text>xarML('</xsl:text>
    <xsl:value-of 
        select="php:functionString('BlockLayoutXSLTProcessor::escape',string(.))"
        disable-output-escaping="yes" />
    <xsl:text>');</xsl:text>
</xsl:template>

<!-- xar:ml -->
<xsl:template match="xar:ml">
  <xsl:apply-templates/>
</xsl:template>

<!-- xar:mlvar -->
<xsl:template match="xar:mlvar"/>

<!-- xar:style -->
<xsl:template match="xar:style">
  <xsl:processing-instruction name="php">
    <xsl:text>xarModAPIFunc('themes','user','register',array(</xsl:text>
    <xsl:if test="@file != ''">
      <xsl:text disable-output-escaping="yes">'file' =&gt;'</xsl:text><xsl:value-of select="@file"/><xsl:text>',</xsl:text>
    </xsl:if>
    <xsl:if test="@scope != ''">
      <xsl:text disable-output-escaping="yes">'scope' =&gt;'</xsl:text><xsl:value-of select="@scope"/><xsl:text>',</xsl:text>
    </xsl:if>
    <xsl:if test="@type != ''">
      <xsl:text disable-output-escaping="yes">'type' =&gt;'</xsl:text><xsl:value-of select="@type"/><xsl:text>',</xsl:text>
    </xsl:if>
    <xsl:if test="@media != ''">
      <xsl:text disable-output-escaping="yes">'media' =&gt;'</xsl:text><xsl:value-of select="@media"/><xsl:text>',</xsl:text>
    </xsl:if>
    <xsl:if test="@alternate != ''">
      <xsl:text disable-output-escaping="yes">'alternate' =&gt;'</xsl:text><xsl:value-of select="@alternate"/><xsl:text>',</xsl:text>
    </xsl:if>
    <xsl:if test="@title != ''">
      <xsl:text disable-output-escaping="yes">'title' =&gt;'</xsl:text><xsl:value-of select="@title"/><xsl:text>',</xsl:text>
    </xsl:if>
    <xsl:if test="@method != ''">
      <xsl:text disable-output-escaping="yes">'method' =&gt;'</xsl:text><xsl:value-of select="@method"/><xsl:text>',</xsl:text>
    </xsl:if>
    <xsl:if test="@condition != ''">
      <xsl:text disable-output-escaping="yes">'condition' =&gt;'</xsl:text><xsl:value-of select="@condition"/><xsl:text>',</xsl:text>
    </xsl:if>
    <xsl:text>));</xsl:text>
  </xsl:processing-instruction>
</xsl:template>

<!-- xar:additional-styles -->
<xsl:template match="xar:additional-styles">
  <xsl:processing-instruction name="php">
    <xsl:text disable-output-escaping="yes">echo xarModAPIFunc('themes','user','deliver',array('method' =&gt; 'render','base' =&gt; 'theme'));</xsl:text>
  </xsl:processing-instruction>
</xsl:template>

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
