<?xml version="1.0" encoding="utf-8"?>
<!DOCTYPE xsl:stylesheet [
<!ENTITY nl "&#xd;&#xa;">
]>
<xsl:stylesheet version="1.0"
    xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
    xmlns:xar="http://xaraya.com/2004/blocklayout"
    xmlns:php="http://php.net/xsl"
    exclude-result-prefixes="php xar">

<xsl:template match="xar:data-view">
  <xsl:processing-instruction name="php">
    <xsl:choose>
      <!-- No object or objectname? Generate ourselves then -->
      <xsl:when test="not(@object) and not(@objectname)">
        <xsl:text>echo xarMod::apiFunc('dynamicdata','user','showview',</xsl:text>
        <!-- Dump the attributes in an array for the function call -->
        <xsl:call-template name="atts2args">
          <xsl:with-param name="nodeset" select="@*"/>
        </xsl:call-template>
        <xsl:text>);</xsl:text>
      </xsl:when>
      <xsl:otherwise>
        <xsl:choose>
          <xsl:when test="@object != ''">
            <!-- Use the object attribute -->
            <xsl:text>echo </xsl:text><xsl:value-of select="@object"/>
          </xsl:when>
          <xsl:when test="@objectname != ''">
            <!-- This a string. we assume it's an object name -->
            <xsl:text>sys::import('modules.dynamicdata.class.objects.master');</xsl:text>
            <xsl:text>$__</xsl:text>
            <xsl:value-of select="@objectname"/>
            <xsl:text>=DataObjectMaster::getObjectList(array('name'=>'</xsl:text>
            <xsl:value-of select="@objectname"/>
            <xsl:text>'));</xsl:text>
            <xsl:text>$__</xsl:text>
            <xsl:value-of select="@objectname"/>
            <xsl:text>-&gt;getItems(</xsl:text>
            <xsl:call-template name="atts2args">
              <xsl:with-param name="nodeset" select="@*[name() != 'objectname']"/>
            </xsl:call-template>
            <xsl:text>);</xsl:text>
            <xsl:text>echo </xsl:text>
            <xsl:text>$__</xsl:text>
            <xsl:value-of select="@objectname"/>
          </xsl:when>
        </xsl:choose>
        <xsl:text>-&gt;showView(</xsl:text>
        <!-- Dump the attributes in an array for the function call, but skip the object and objectname attributes -->
        <xsl:call-template name="atts2args">
          <xsl:with-param name="nodeset" select="@*[name() != 'object' and name() != 'objectname']"/>
        </xsl:call-template>
        <xsl:text>);</xsl:text>
      </xsl:otherwise>
    </xsl:choose>
  </xsl:processing-instruction>
</xsl:template>

<xsl:template match="xar:data-form">
  <xsl:processing-instruction name="php">
      <xsl:choose>
        <xsl:when test="not(@object)">
          <!-- No object passed in -->
          <xsl:text>echo xarMod::apiFunc('dynamicdata','admin','showform',</xsl:text>
          <xsl:choose>
            <xsl:when test="not(@definition)">
              <!-- No direct definition, use the attributes -->
              <xsl:call-template name="atts2args">
                <xsl:with-param name="nodeset" select="@*"/>
              </xsl:call-template>
            </xsl:when>
            <xsl:otherwise>
              <xsl:value-of select="@definition"/>
            </xsl:otherwise>
          </xsl:choose>
          <xsl:text>);</xsl:text>
        </xsl:when>
        <xsl:otherwise>
          <!-- Use the object attribute -->
          <xsl:text>echo </xsl:text><xsl:value-of select="@object"/>
          <xsl:text>-&gt;showForm(</xsl:text>
          <xsl:call-template name="atts2args">
            <xsl:with-param name="nodeset" select="@*[name() != 'object']"/>
          </xsl:call-template>
          <xsl:text>);</xsl:text>
        </xsl:otherwise>
      </xsl:choose>
  </xsl:processing-instruction>
</xsl:template>

<xsl:template match="xar:data-display">
  <xsl:processing-instruction name="php">
      <xsl:choose>
        <xsl:when test="not(@object)">
          <!-- No object passed in -->
          <xsl:text>echo xarMod::apiFunc('dynamicdata','user','showdisplay',</xsl:text>
          <xsl:choose>
            <xsl:when test="not(@definition)">
              <!-- No direct definition, use the attributes -->
              <xsl:call-template name="atts2args">
                <xsl:with-param name="nodeset" select="@*"/>
              </xsl:call-template>
            </xsl:when>
            <xsl:otherwise>
              <xsl:value-of select="@definition"/>
            </xsl:otherwise>
          </xsl:choose>
          <xsl:text>);</xsl:text>
        </xsl:when>
        <xsl:otherwise>
          <xsl:choose>
            <xsl:when test="substring(@object,1,1) = '$'">
              <!-- This a variable. we assume it's an object -->
              <!-- Use the object attribute -->
              <xsl:text>echo </xsl:text><xsl:value-of select="@object"/>
            </xsl:when>
            <xsl:otherwise>
              <!-- This a string. we assume it's an object name -->
              <xsl:text>sys::import('modules.dynamicdata.class.objects.master');</xsl:text>
              <xsl:text>$__</xsl:text>
              <xsl:value-of select="@object"/>
              <xsl:text>=DataObjectMaster::getObject(array('name'=>'</xsl:text>
              <xsl:value-of select="@object"/>
              <xsl:text>'));</xsl:text>
              <xsl:text>echo </xsl:text>
              <xsl:text>$__</xsl:text>
              <xsl:value-of select="@object"/>
            </xsl:otherwise>
          </xsl:choose>
          <xsl:text>-&gt;showDisplay(</xsl:text>
          <xsl:call-template name="atts2args">
            <xsl:with-param name="nodeset" select="@*[name() != 'object']"/>
          </xsl:call-template>
          <xsl:text>);</xsl:text>
        </xsl:otherwise>
      </xsl:choose>
  </xsl:processing-instruction>
</xsl:template>

<xsl:template match="xar:data-getitem">
  <xsl:processing-instruction name="php">
    <xsl:choose>
      <xsl:when test="not(@object) and not(@objectname)">
        <!-- No object, gotta make one -->
        <xsl:choose>
          <!-- Old-style tag with name instead of properties -->
          <xsl:when test="not(@properties)">
            <xsl:text>$object = xarMod::apiFunc('dynamicdata','user','getitem',</xsl:text>
            <xsl:text>array_merge(array('getobject'=&gt;1),</xsl:text>
            <xsl:call-template name="atts2args">
              <xsl:with-param name="nodeset" select="@*[name() != 'name']"/>
            </xsl:call-template>
            <xsl:text>));</xsl:text>
            <xsl:text>$object-&gt;getItem(</xsl:text>
            <xsl:call-template name="atts2args">
              <xsl:with-param name="nodeset" select="@*[name() != 'name']"/>
            </xsl:call-template>
            <xsl:text>);</xsl:text>
            <!-- the name attribute holds a variable name, not good, but it is like that -->
            <xsl:value-of select="@name"/><xsl:text>= $object-&gt;getProperties(</xsl:text>
              <xsl:call-template name="atts2args">
                <xsl:with-param name="nodeset" select="@*[name() != 'name' and name()!='object']"/>
              </xsl:call-template>
            <xsl:text>);</xsl:text>
          </xsl:when>
          <xsl:otherwise>
            <xsl:text>$object = xarMod::apiFunc('dynamicdata','user','getitem',</xsl:text>
            <xsl:text>array_merge(array('getobject'=&gt;1),</xsl:text>
            <xsl:call-template name="atts2args">
              <xsl:with-param name="nodeset" select="@*[name() != 'properties']"/>
            </xsl:call-template>
            <xsl:text>));</xsl:text>
            <xsl:text>$object-&gt;getItem(</xsl:text>
            <xsl:call-template name="atts2args">
              <xsl:with-param name="nodeset" select="@*[name() != 'properties']"/>
            </xsl:call-template>
            <xsl:text>);</xsl:text>
            <!-- the name attribute holds a variable name, not good, but it is like that -->
            <xsl:text>$</xsl:text>
            <xsl:value-of select="@properties"/><xsl:text>= $object-&gt;getProperties(</xsl:text>
              <xsl:call-template name="atts2args">
                <xsl:with-param name="nodeset" select="@*[name() != 'properties' and name()!='object']"/>
              </xsl:call-template>
            <xsl:text>);</xsl:text>
          </xsl:otherwise>
        </xsl:choose>
      </xsl:when>
      <xsl:otherwise>
        <xsl:choose>
          <xsl:when test="substring(@object,1,1) = '$'">
            <!-- This a variable. we assume it's an object -->
            <xsl:value-of select="@object"/><xsl:text>-&gt;getItem(</xsl:text>
            <xsl:call-template name="atts2args">
              <xsl:with-param name="nodeset" select="@*[name() != 'properties']"/>
            </xsl:call-template>
            <xsl:text>);</xsl:text>
            <xsl:value-of select="@properties"/><xsl:text>=</xsl:text>
            <xsl:value-of select="@object"/><xsl:text>->getProperties(</xsl:text>
              <xsl:call-template name="atts2args">
                <xsl:with-param name="nodeset" select="@*[name() != 'properties' and name()!='object']"/>
              </xsl:call-template>
            <xsl:text>);</xsl:text>
          </xsl:when>
          <xsl:otherwise>
            <!-- This a string. we assume it's an object name -->
            <xsl:text>sys::import('modules.dynamicdata.class.objects.master');</xsl:text>
            <xsl:text>$__</xsl:text>
            <xsl:value-of select="@objectname"/>
            <xsl:text> = DataObjectMaster::getObject(array('name' => '</xsl:text>
            <xsl:value-of select="@objectname"/>
            <xsl:text>'));</xsl:text>
            <xsl:text>$__</xsl:text>
            <xsl:value-of select="@objectname"/>
            <xsl:text>->getItem(</xsl:text>
            <xsl:call-template name="atts2args">
              <xsl:with-param name="nodeset" select="@*[name() != 'properties' and name()!='objectname']"/>
            </xsl:call-template>
            <xsl:text>);</xsl:text>
            <xsl:value-of select="@properties"/>
            <xsl:text> = </xsl:text>
            <xsl:text>$__</xsl:text>
            <xsl:value-of select="@objectname"/>
            <xsl:text>->getProperties(</xsl:text>
              <xsl:call-template name="atts2args">
                <xsl:with-param name="nodeset" select="@*[name() != 'properties' and name()!='objectname']"/>
              </xsl:call-template>
            <xsl:text>);</xsl:text>
          </xsl:otherwise>
        </xsl:choose>
      </xsl:otherwise>
    </xsl:choose>
  </xsl:processing-instruction>
</xsl:template>

<xsl:template match="xar:data-getitems">
    <xsl:processing-instruction name="php">
      <xsl:choose>
        <xsl:when test="not(@object) and not(@objectname)">
            <!-- No object gotta make one -->
            <xsl:choose>
              <!-- Old-style tag with name instead of properties -->
              <xsl:when test="not(@properties)">
                <xsl:text>list(</xsl:text>
                <xsl:value-of select="@name"/>
                <xsl:text>,</xsl:text>
                <xsl:value-of select="@value"/>
                <xsl:text>) = xarMod::apiFunc('dynamicdata','user','getitemsforview',</xsl:text>
                <xsl:call-template name="atts2args">
                  <xsl:with-param name="nodeset" select="@*[name() != 'name' and name()!='value']"/>
                </xsl:call-template>
                <xsl:text>);</xsl:text>
              </xsl:when>
              <xsl:otherwise>
                <xsl:text>list(</xsl:text>
                <xsl:text>$</xsl:text>
                <xsl:value-of select="@properties"/>
                <xsl:text>,$</xsl:text>
                <xsl:value-of select="@values"/>
                <xsl:text>) = xarMod::apiFunc('dynamicdata','user','getitemsforview',</xsl:text>
                <xsl:call-template name="atts2args">
                  <xsl:with-param name="nodeset" select="@*[name() != 'properties' and name()!='values']"/>
                </xsl:call-template>
                <xsl:text>);</xsl:text>
              </xsl:otherwise>
            </xsl:choose>
          </xsl:when>
          <xsl:otherwise>
            <xsl:choose>
              <xsl:when test="substring(@object,1,1) = '$'">
                <!-- This a variable. we assume it's an object -->
                <xsl:value-of select="@values"/><xsl:text>=</xsl:text>
                <xsl:value-of select="@object"/><xsl:text>-&gt;getItems(</xsl:text>
                <xsl:call-template name="atts2args">
                  <xsl:with-param name="nodeset" select="@*[name() != 'properties' and name()!='values' and name()!='object']"/>
                </xsl:call-template>
                <xsl:text>);</xsl:text>
                <xsl:value-of select="@properties"/><xsl:text>=</xsl:text>
                <xsl:value-of select="@object"/><xsl:text>->getProperties();</xsl:text>
              </xsl:when>
              <xsl:otherwise>
                <!-- This a string. we assume it's an object name -->
                <xsl:text>sys::import('modules.dynamicdata.class.objects.master');</xsl:text>
                <xsl:text>$__</xsl:text>
                <xsl:value-of select="@objectname"/>
                <xsl:text>=DataObjectMaster::getObjectList(array('name'=>'</xsl:text>
                <xsl:value-of select="@objectname"/>
                <xsl:text>'));</xsl:text>
                <xsl:value-of select="@values"/><xsl:text>=</xsl:text>
                <xsl:text>$__</xsl:text>
                <xsl:value-of select="@objectname"/>
                <xsl:text>-&gt;getItems(</xsl:text>
                <xsl:call-template name="atts2args">
                  <xsl:with-param name="nodeset" select="@*[name() != 'properties' and name()!='values' and name()!='objectname']"/>
                </xsl:call-template>
                <xsl:text>);</xsl:text>
                <xsl:value-of select="@properties"/><xsl:text>=</xsl:text>
                <xsl:text>$__</xsl:text>
                <xsl:value-of select="@objectname"/>
                <xsl:text>->getProperties();</xsl:text>
              </xsl:otherwise>
            </xsl:choose>
          </xsl:otherwise>
      </xsl:choose>
    </xsl:processing-instruction>
</xsl:template>

<xsl:template match="xar:data-input">
  <xsl:processing-instruction name="php">
    <xsl:choose>
      <xsl:when test="not(@property)">
        <!-- No property, gotta make one -->
        <xsl:text>sys::import('modules.dynamicdata.class.properties');</xsl:text>
        <xsl:text>$property =&amp; DataPropertyMaster::getProperty(</xsl:text>
        <xsl:call-template name="atts2args">
          <xsl:with-param name="nodeset" select="@*[name() != 'hidden' and name() != 'preset']"/>
        </xsl:call-template>
        <xsl:text>);</xsl:text>
        <xsl:text>echo $property-&gt;</xsl:text>
        <xsl:choose>
          <xsl:when test="@preset and not(@value)">
            <xsl:text>_showPreset(</xsl:text>
            <xsl:call-template name="atts2args">
              <xsl:with-param name="nodeset" select="@*[name() != 'hidden' and name() != 'preset']"/>
            </xsl:call-template>
          </xsl:when>
          <xsl:when test="@hidden">
            <xsl:text>showHidden(</xsl:text>
            <xsl:call-template name="atts2args">
              <xsl:with-param name="nodeset" select="@*[name() != 'hidden' and name() != 'preset']"/>
            </xsl:call-template>
          </xsl:when>
          <xsl:otherwise>
            <xsl:text>showInput(</xsl:text>
            <xsl:call-template name="atts2args">
              <xsl:with-param name="nodeset" select="@*[name() != 'hidden' and name() != 'preset']"/>
            </xsl:call-template>
          </xsl:otherwise>
        </xsl:choose>
        <xsl:text>);</xsl:text>
      </xsl:when>
      <xsl:otherwise>
        <!-- We do have a property in the attribute -->
        <xsl:text>if (isset(</xsl:text>
        <xsl:value-of select="@property"/>
        <xsl:text>)){</xsl:text>
        <xsl:text>echo </xsl:text>
        <xsl:value-of select="@property"/><xsl:text>-&gt;</xsl:text>
        <xsl:choose>
          <xsl:when test="@preset and not(@value)">
            <xsl:text>_showPreset(</xsl:text>
            <xsl:call-template name="atts2args">
              <xsl:with-param name="nodeset" select="@*[name() != 'hidden' and name() != 'preset']"/>
            </xsl:call-template>
          </xsl:when>
          <xsl:when test="@hidden">
            <xsl:text>showHidden(</xsl:text>
            <xsl:call-template name="atts2args">
              <xsl:with-param name="nodeset" select="@*[name() != 'property'  and name() != 'hidden' and name() != 'preset']"/>
            </xsl:call-template>
          </xsl:when>
          <xsl:otherwise>
            <xsl:text>showInput(</xsl:text>
            <xsl:call-template name="atts2args">
              <xsl:with-param name="nodeset" select="@*[name() != 'hidden' and name() != 'preset']"/>
            </xsl:call-template>
          </xsl:otherwise>
        </xsl:choose>
        <xsl:text>);</xsl:text>
        <xsl:text>}</xsl:text>
      </xsl:otherwise>
    </xsl:choose>
  </xsl:processing-instruction>
</xsl:template>

<xsl:template match="xar:data-output">
  <xsl:processing-instruction name="php">
    <xsl:choose>
        <xsl:when test="not(@property)">
          <!-- No prop, get one (the right one, preferably) -->
          <xsl:text>sys::import('modules.dynamicdata.class.properties');</xsl:text>
          <xsl:text>$property =&amp; DataPropertyMaster::getProperty(</xsl:text>
          <xsl:call-template name="atts2args">
            <xsl:with-param name="nodeset" select="@*"/>
          </xsl:call-template>
          <xsl:text>);</xsl:text>
          <xsl:text>echo $property-&gt;showOutput(</xsl:text>
          <!-- if we have a field attribute, use just that, otherwise use all attributes -->
          <xsl:choose>
            <xsl:when test="not(@field)">
              <xsl:call-template name="atts2args">
                <xsl:with-param name="nodeset" select="@*[name() != 'property']"/>
              </xsl:call-template>
            </xsl:when>
            <xsl:otherwise>
              <xsl:value-of select="@field"/>
            </xsl:otherwise>
          </xsl:choose>
          <xsl:text>);</xsl:text>
        </xsl:when>
        <xsl:otherwise>
          <!-- We already had a property object, run its output method -->
          <xsl:text>if (isset(</xsl:text>
          <xsl:value-of select="@property"/>
          <xsl:text>)){</xsl:text>
          <xsl:text>echo </xsl:text>
          <xsl:value-of select="@property"/>
          <xsl:text>-&gt;showOutput(</xsl:text>
          <!-- if we have a field attribute, use just that, otherwise use all attributes -->
          <xsl:choose>
            <xsl:when test="not(@field)">
              <xsl:call-template name="atts2args">
                <xsl:with-param name="nodeset" select="@*[name() != 'property']"/>
              </xsl:call-template>
            </xsl:when>
            <xsl:otherwise>
              <xsl:value-of select="@field"/>
            </xsl:otherwise>
          </xsl:choose>
          <xsl:text>);</xsl:text>
          <xsl:text>}</xsl:text>
        </xsl:otherwise>
    </xsl:choose>
  </xsl:processing-instruction>
</xsl:template>

<xsl:template match="xar:data-label">
  <xsl:processing-instruction name="php">
    <xsl:choose>
      <xsl:when test="not(@object)">
        <!-- No object -->
        <xsl:choose>
          <xsl:when test="not(@property)">
            <!-- No property either -->
            <xsl:choose>
              <xsl:when test="not(@label)">
                <!-- Doh, no label either -->
                <xsl:text>echo "I need an object or a property or a label attribute";</xsl:text>
              </xsl:when>
              <xsl:otherwise>
                <!-- Ok, we have nothin, but a label -->
                <xsl:text>echo xarTplProperty('dynamicdata','label','showoutput',array('label'=&gt;'</xsl:text>
                <xsl:value-of select="@label"/><xsl:text>'</xsl:text>
                <xsl:if test="@for">
                  <xsl:text>,'for'=&gt;'</xsl:text>
                  <xsl:value-of select="@for"/>
                  <xsl:text>'</xsl:text>
                </xsl:if>
                <xsl:if test="@title">
                  <xsl:text>,'title'=&gt;</xsl:text>
                  <xsl:choose>
                    <xsl:when test="starts-with(@title,'$')">
                      <xsl:value-of select="@title"/>
                    </xsl:when>
                    <xsl:otherwise>
                      <xsl:text>'</xsl:text>
                      <xsl:value-of select="@title"/>
                      <xsl:text>'</xsl:text>
                    </xsl:otherwise>
                  </xsl:choose>
                </xsl:if>
                <xsl:text>),'label');</xsl:text>
              </xsl:otherwise>
            </xsl:choose>
          </xsl:when>
          <xsl:otherwise>
            <!-- We have a property -->
            <xsl:text>if (isset(</xsl:text>
            <xsl:value-of select="@property"/>
            <xsl:text>)){</xsl:text>
            <xsl:text>echo </xsl:text><xsl:value-of select="@property"/>
            <xsl:text>-&gt;showLabel(</xsl:text>
            <xsl:call-template name="atts2args">
              <xsl:with-param name="nodeset" select="@*"/>
            </xsl:call-template>
            <xsl:text>);</xsl:text>
            <xsl:text>}</xsl:text>
          </xsl:otherwise>
        </xsl:choose>
      </xsl:when>
      <xsl:otherwise>
        <!-- If we have an object, throw out its label -->
        <xsl:text>echo xarVarPrepForDisplay(</xsl:text>
        <xsl:value-of select="@object"/><xsl:text>-&gt;label);</xsl:text>
      </xsl:otherwise>
    </xsl:choose>
  </xsl:processing-instruction>
</xsl:template>

</xsl:stylesheet>
