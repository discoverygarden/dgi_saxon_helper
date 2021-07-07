<?xml version="1.0" encoding="UTF-8"?>
<xsl:stylesheet version="2.0"
xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
  <xsl:param name="one"/>
  <xsl:param name="two"/>
  <xsl:param name="three"/>

  <xsl:template match="alpha">
    <bravo>
      <xsl:attribute name="one">
        <xsl:value-of select="$one"/>
      </xsl:attribute>
      <xsl:attribute name="two">
        <xsl:value-of select="$two"/>
      </xsl:attribute>
      <xsl:attribute name="three">
        <xsl:value-of select="$three"/>
      </xsl:attribute>
    </bravo>
  </xsl:template>
</xsl:stylesheet>
