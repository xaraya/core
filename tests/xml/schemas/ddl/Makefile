# Makefile to quickly produce other schema formats based on the DTD input

# RNG - good structure
# RNC - machine parseable optimized
# XSD - W3C standard but otherwise unreadable
# DTD - defacto used standard, no xml, used as source here

TRANG=trang
PHP=php5
XSLTPROC=xsltproc
XARROOT=/var/mt/xar/core/core.2.x/html

all: xarddl.rng xarddl.xsd xarddl.rnc xar-model.sql

# Generating stuff from the DTD
%.xsd: %.dtd
	$(TRANG) -I dtd -O xsd $< $@
%.rng: %.dtd
	$(TRANG) -I dtd -O rng $< $@
%.rnc: %.dtd
	$(TRANG) -I dtd -O rnc $< $@

# Getting an xml schema from a xar installation (see db2xml)
xar-model.xml: db2xml db2xml.xd
	cd $(XARROOT) && ./db2xml > $(PWD)/$@
	
%.sql: %.xml xar-model.xml
	$(XSLTPROC) xml2ddl.xsl $< > $@
clean:
	$(RM) *.rng *.xsd *.rnc *.sql xar-model.xml