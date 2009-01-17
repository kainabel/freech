#!/usr/bin/perl -w
## Extracts the hook documentation from the source code.
##

sub print_hook {
  my($name, $body, $args) = @_;
  $body =~ s/[\r\n]+[\*\s]*/ /g;
  $body =~ s/[\r\n]+[\*\s]*/\n/g;
  #$t = $_[0];
  print "Hook: $name\n";
  print "Description: $body\n";
  print "Arguments: $args\n";
  print "\n";
}


# Read entire stdin into memory - one multi-line string.
$_ = do { local $/; <> };

# The main comment-detection code.
s{
  /\*\s+Plugin\ hook:\s+        # Open comment.
  ([^\r\n]+)                    # $1 = Hook name.
  (.*?)                         # $2 = Description (multi-line)
  [\r\n]+[\*\s]+Args:\s+(.*?)   # $3 = Argument list (multi-line)
  \s*\*\/                       # Close comment
 }
 {
    print_hook($1, $2, $3)
 }gesxi;
# ^^^^ Modes: g - Global, match all occurances.
#             e - Evaluate the replacement as an expression.
#             s - Single-line - allows the pattern to match across newlines.
#             x - eXtended pattern, ignore embedded whitespace
#                 and allow comments.
