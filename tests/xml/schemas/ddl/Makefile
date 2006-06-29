# Makefile to quickly produce other schema formats based on the DTD input

# Generated from the .dtd format:
# RNG - good structure
# RNC - machine parseable optimized
# XSD - W3C standard but otherwise unreadable
# DTD - defacto used standard, no xml, used as source here

# db2xml creates a model from a running xar installation
# that model is then taken as input for the xml2ddl target to
# produce SQL like an sqldumper utility.

TRANG=trang
PHP=php5
XSLTPROC=xsltproc
XARROOT=/var/mt/xar/core/core.2.x/html

all: schemas xar-model.sql
schemas: xarddl.rng xarddl.xsd xarddl.rnc
test: schema.sql
	
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
	$(XSLTPROC) xml2ddl-mysql.xsl $< > $(*F).mysql.sql
	$(XSLTPROC) xml2ddl-sqlite.xsl $< > $(*F).sqlite.sql
	$(XSLTPROC) xml2ddl-pgsql.xsl $< > $(*F).pgsql.sql
	$(XSLTPROC) xml2ddl-oracle.xsl $< > $(*F).oracle.sql
	$(XSLTPROC) xml2ddl-mssql.xsl $< > $(*F).mssql.sql

clean:
	$(RM) *.rng *.xsd *.rnc *.sql xar-model.xml