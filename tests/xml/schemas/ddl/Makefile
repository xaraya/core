# Makefile to quickly produce other schema formats based on the DTD input

# RNG - good structure
# RNC - machine parseable optimized
# XSD - W3C standard but otherwise unreadable
# DTD - defacto used standard, no xml, used as source here

TRANG=trang
PHP=php5

all: xarddl.rng xarddl.xsd xarddl.rnc
	
%.xsd: %.dtd
	$(TRANG) -I dtd -O xsd $< $@
%.rng: %.dtd
	$(TRANG) -I dtd -O rng $< $@
%.rnc: %.dtd
	$(TRANG) -I dtd -O rnc $< $@

clean:
	$(RM) *.rng *.xsd *.rnc