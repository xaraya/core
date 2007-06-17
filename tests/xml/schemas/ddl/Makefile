# Makefile to quickly produce other schema formats based on the DTD input

# Generated from the .dtd format:
# RNG - good structure
# RNC - machine parseable optimized
# XSD - W3C standard but otherwise unreadable
# DTD - defacto used standard, used as source here

# db2xml creates a model from a running xar installation
# that model is then taken as input for the xml2ddl target to
# produce SQL like an sqldumper utility.

TRANG=trang
PHP=php5
XSLTPROC=xsltproc
SRC=rnc

all: schemas xar-model.sql
schemas: xarddl.dtd xarddl.xsd xarddl.rng
test: schema.sql

# Generating stuff from the DTD
%.xsd: %.$(SRC)
	$(TRANG) -I $(SRC) -O xsd $< $@
%.rng: %.$(SRC)
	$(TRANG) -I $(SRC) -O rng $< $@
%.rnc: %.$(SRC)
	$(TRANG) -I $(SRC) -O rnc $< $@
%.dtd: %.$(SRC)
	$(TRANG) -I $(SRC) -O dtd $< $@

# Getting an xml schema from a xar installation (see db2xml)
xar-model.xml: db2xml db2xml.xd
	./db2xml > $(PWD)/$@

%.sql: %.xml xar-model.xml
	$(XSLTPROC) --stringparam vendor mysql  xml2ddl-mysql.xsl $< > $(*F).mysql.sql
	$(XSLTPROC) --stringparam vendor sqlite xml2ddl-sqlite.xsl $< > $(*F).sqlite.sql
	$(XSLTPROC) --stringparam vendor pgsql  xml2ddl-pgsql.xsl $< > $(*F).pgsql.sql
	$(XSLTPROC) --stringparam vendor oracle xml2ddl-oracle.xsl $< > $(*F).oracle.sql
	$(XSLTPROC) --stringparam vendor mssql  xml2ddl-mssql.xsl $< > $(*F).mssql.sql

clean:
	$(RM) *.dtd *.rng *.xsd  *.sql xar-model.xml