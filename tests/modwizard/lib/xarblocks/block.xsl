<!--

    XARAYA MODULE WIZARD

    COPYRIGHT:      Michael Jansen
    CONTACT:        xaraya-module-wizard@schneelocke.de
    LICENSE:        GPL

-->
<xsl:stylesheet version="1.0"
                xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
                xmlns="http://www.w3.org/TR/xhtml1/strict">

<!-- ENTRY POINT    print out progress and call module template -->
<xsl:template match="block" mode="xarblocks_block" xml:space="default">

    <xsl:message>      * xarblocks/<xsl:value-of select="@name" />.php</xsl:message>

    <xsl:variable name="block" select="@name" />
    <xsl:document href="{$output}/xarblocks/{$block}.php" format="text" omit-xml-declaration="yes" ><xsl:processing-instruction name="php">

        <!-- call template for file header -->
        <xsl:call-template name="xaraya_standard_php_file_header" select="../..">
            <xsl:with-param name="filename"><xsl:value-of select="$block" />.php</xsl:with-param>
        </xsl:call-template>

        <!-- call template for module_init() function -->
        <xsl:apply-templates mode="xarblocks_block_init" select="." />

        <!-- call template for module_delete() function -->
        <xsl:apply-templates mode="xarblocks_block_info" select="." />

        <!-- call template for module_xarupgrade() function -->
        <xsl:apply-templates mode="xarblocks_block_display" select="." />

        <!-- call template for file footer -->
        <xsl:call-template name="xaraya_standard_php_file_footer" select="../.." />

    </xsl:processing-instruction></xsl:document>

</xsl:template>


<!-- init() Function -->
<xsl:template mode="xarblocks_block_init" match="block">
    <xsl:variable name="module_prefix" select="../../registry/name" />
/**
 * Initialise the block
 */
function <xsl:value-of select="$module_prefix" />_<xsl:value-of select="@name" />block_init()
{
    return array();
}
</xsl:template>


<!-- info() Function -->
<xsl:template mode="xarblocks_block_info" match="block">
    <xsl:variable name="module_prefix" select="../../registry/name" />
/**
 * Show Information about the block
 */
function <xsl:value-of select="$module_prefix" />_<xsl:value-of select="@name" />block_info()
{
        // Values
    return array(
        'text_type' => '<xsl:value-of select="@name" />',
        'module'    => '<xsl:value-of select="$module_prefix" />',
        'text_type_long' => 'Show Status Message',
        'allow_multiple' => true,
        'form_content' => false,
        'form_refresh' => false,
        'show_preview' => true );
}
</xsl:template>


<!-- display() Function -->
<xsl:template mode="xarblocks_block_display" match="block">
    <xsl:variable name="module_prefix" select="../../registry/name" />
/**
 * Display the block
 */
function <xsl:value-of select="$module_prefix" />_<xsl:value-of select="@name" />block_display( $blockinfo )
{
<xsl:if test="$gCommentsLevel >= 10">    // Consider adding a security check here.</xsl:if>
<xsl:if test="$gCommentsLevel >= 10">    // Get variables from content block.
    // Content is a serialized array for legacy support, but will be
    // an array (not serialized) once all blocks have been converted.</xsl:if>

    if( !is_array( $blockinfo['content'] ))
        {
        $vars = unserialize( $blockinfo['content'] );
        }
    else
        {
        $vars = $blockinfo[ 'content' ];
        }

    $blockinfo['content'] = array(
        'content'       => xarSessionGetVar( '<xsl:value-of select="$module_prefix" />_statusmsg' )
    );
    return $blockinfo;
}
</xsl:template>

</xsl:stylesheet>
