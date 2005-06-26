#!/bin/bash
## Some stupid security checks.
##

CHECK1="egrep '(\\\$sql|^ *\.).*\\\$_' src/*sql*"
CHECK2="egrep '^ *function .*\\\$[a-zA-Z]' src/*"

i="1"
CHECK="CHECK1"

while [ "${!CHECK}" != "" ]; do
  echo -n "Running check $i: ${!CHECK}... "
  if [ "`eval ${!CHECK}`" != "" ]; then
    echo failed!
  else
    echo success.
  fi
  i=`expr $i + 1`
  CHECK="CHECK$i"
done
