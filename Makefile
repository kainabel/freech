NAME=freech
VERSION=`grep _VERSION src/forum.inc.php | cut -d"'" -f4`
PACKAGE=$(NAME)-$(VERSION)-1
DISTDIR=/pub/code/releases/$(NAME)

publish:
	rsync -avzr src/ root@91.184.35.4:/home/sab/backups/code/www/$$NAME.debain.org/ \
				--exclude /config.inc.php \
				--exclude /.git
	ssh root@91.184.35.4 "chown -R www-data:www-data /home/sab/backups/code/www/$$NAME.debain.org/"

dist:
	mkdir -p $(PACKAGE)
	ls -1d * | grep -v $(PACKAGE) | while read i; do cp -r "$$i" $(PACKAGE)/; done
	cd $(PACKAGE); ./makedoc.sh; cd -
	tar cjf $(PACKAGE).tar.bz2 $(PACKAGE)
	rm -R $(PACKAGE)
	mkdir -p $(DISTDIR)/
	mv $(PACKAGE).tar.bz2 $(DISTDIR)
