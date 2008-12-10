publish:
	rsync -avzr src/ root@91.184.35.4:/home/sab/backups/code/www/freech.debain.org/ \
				--exclude /config.inc.php \
				--exclude /.git
	ssh root@91.184.35.4 "chown -R www-data:www-data /home/sab/backups/code/www/freech.debain.org/"
