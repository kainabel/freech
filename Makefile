NAME=freech
PACKAGE=$(NAME)-$(VERSION)-1
VERSION=`grep _VERSION src/forum.inc.php | cut -d"'" -f4`
PUBLISH_PATH=/home/sab/backups/code/www/test.debain.org/$(NAME)
PUBLISH_HOST=root@debain.org
DISTDIR=/pub/code/releases/$(NAME)

clean:
	rm -Rf $(PACKAGE)

dist-prepare: clean
	mkdir -p $(PACKAGE)
	ls -1d * | grep -v $(PACKAGE) | while read i; do cp -r "$$i" $(PACKAGE)/; done
	cd $(PACKAGE); ./makedoc.sh; cd -

dist: dist-prepare
	tar cjf $(PACKAGE).tar.bz2 $(PACKAGE)
	make clean
	mkdir -p $(DISTDIR)/
	mv $(PACKAGE).tar.bz2 $(DISTDIR)

publish:
	rsync -azr src/ $(PUBLISH_HOST):$(PUBLISH_PATH)/ \
				--exclude /config.inc.php \
				--exclude /.git
	ssh $(PUBLISH_HOST) "chown -R www-data:www-data $(PUBLISH_PATH)/"
	make clean
