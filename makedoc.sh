#!/bin/sh
## Builds all documentations.

echo -n "Building plugin developer documentation..."
(
  echo "*************************************************************"
  echo "* List of hooks provided to plugins by Freech."
  echo "* Generated by $0 on `date`."
  echo "* Do not edit this file."
  echo "*************************************************************"
  echo
  find src -name "*.php" -a ! -path "*smarty*" -exec cat {} \; \
    | ./extract_hooklist.pl
) > plugin_hooks.txt
echo "done."
