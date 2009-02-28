ARGS="-R --exclude-dir *adodb* --exclude *.js --exclude *.sw[po]"

egrep $ARGS --exclude "*.tmpl" "function [a-zA-Z0-9_]*\(" * \
  | sed 's/^.*function  *\([^(]*\)(.*/\1/' \
  | egrep -v '(^on_|_on_|_init$)' \
  | sort -u \
  | while read i; do
  USES=`egrep $ARGS '([^a-z_]|->)'$i'\(' * | egrep -v "function [a-zA-Z0-9_]*\("`
  USES2=`egrep $ARGS 'class '$i *`
  USES3=`egrep $ARGS 'array_map\(.'$i *`
  USES4=`egrep $ARGS 'array\(\\\$this, .'$i *`
  [ "$USES" = "" -a "$USES2" = "" -a "$USES3" = "" -a "$USES4" = "" ] && echo $i
done
