NAME=freech
VERSION=`grep _VERSION src/main_controller.class.php | cut -d"'" -f4`
PACKAGE=$(NAME)-$(VERSION)-1
PUBLISH_PATH=/home/sab/backups/code/www/test.debain.org/$(NAME)
PUBLISH_HOST=root@debain.org
DISTDIR=/pub/code/releases/$(NAME)

###################################################################
# Project-specific targets.
###################################################################
dist-prepare: clean
	# Copy all files that are to be distributed into a subdirectory.
	mkdir -p $(PACKAGE)
	ls -1d * \
		| grep -v $(PACKAGE) \
		| while read i; do \
		cp -r "$$i" $(PACKAGE)/; \
	done

	# Update plugin hook documentation.
	cd $(PACKAGE); ./makedoc.sh; cd -

pot:
	find src/ -name "*.php" -o -name "*.php.tmpl" | xargs xgettext -L php -j -o po/$(NAME).pot

mo:
	ls -1 po/*.po | while read i; do \
		mkdir -p src/language/`basename $$i .po`/LC_MESSAGES/; \
		msgfmt -o src/language/`basename $$i .po`/LC_MESSAGES/$(NAME).mo $$i; \
	done

###################################################################
# Standard targets.
###################################################################
clean:
	rm -Rf $(PACKAGE)

dist-clean: clean
	rm -Rf $(PACKAGE)*

doc:
	# No docs yet.

install:
	# No such action. Please read the INSTALL file.

uninstall:
	# No such action. Please read the INSTALL file.

tests:
	rsync -azr src/ $(PUBLISH_HOST):$(PUBLISH_PATH)/ \
				--exclude /data/config.inc.php \
				--exclude /.git \
				--delete
	ssh $(PUBLISH_HOST) "chown -R www-data:www-data $(PUBLISH_PATH)/"
	make clean

###################################################################
# Package builders.
###################################################################
targz: dist-prepare
	tar czf $(PACKAGE).tar.gz $(PACKAGE)

tarbz: dist-prepare
	tar cjf $(PACKAGE).tar.bz2 $(PACKAGE)

deb: dist-prepare
	# No debian package yet.

dist: targz tarbz deb

###################################################################
# Publishers.
###################################################################
dist-publish: dist
	mkdir -p $(DISTDIR)/
	mv $(PACKAGE).tar* $(DISTDIR)

doc-publish: doc
