egrep -R --exclude-dir "*smarty*" \
         --exclude-dir "*adodb*" \
         --exclude "*.js" \
         --exclude "*.tmpl" \
         "function [a-zA-Z0-9_]*\(" * \
  | sed 's/^.*function  *\([^(]*\)(.*/\1/' \
  | egrep -v '(^on_|_on_|_init$)' \
  | sort -u \
  | while read i; do
  USES=`egrep -R --exclude-dir "*smarty*" --exclude-dir "*adodb*" --exclude "*.js" '([^a-z_]|->)'$i'\(' * \
          | egrep -v "function [a-zA-Z0-9_]*\("`
  USES2=`egrep -R --exclude-dir "*smarty*" --exclude-dir "*adodb*" --exclude "*.js" 'class '$i *`
  [ "$USES" = "" -a "$USES2" = "" ] && echo $i
done
