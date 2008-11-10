<?php
/*
Description: Client-side Javascript computes an md5 code, server double
             checks. Blocks spam bots and makes DoS a little more difficult.
Authors:     Elliott Back, Samuel Abels
ChangeLog:   0.1 Derived from wp-hashcash, ported by Samuel Abels.
                 Major cleanups, code generalization, objectified.
                 *Huge* performance improvements.
*/
define("SPAMHASH_ERROR_REFERRER",        -1);
define("SPAMHASH_ERROR_REMOTE_ADDRESS",  -2);
define("SPAMHASH_ERROR_SESSION",         -3);
define("SPAMHASH_ERROR_HASH_MISSING",    -4);
define("SPAMHASH_ERROR_UNKNOWN",         -5);

class SpamHash {
  var $session_id;    // The session ID of the currently served client.
  var $user_rand;     // Random value used to calculate reproducible hashes.
  var $form_id;       // Id of the HTML form into which a hash is inserted.
  var $form_action;   // Name of the JS function that is called by onsubmit().
  var $form_field_id; // Id of the hidden field inserted into the form.
  var $fn_enable_name;// Name of the on_load() JS function.
  var $js;            // The entire generated JS snippet.
  
  /**
   * Instantiate a SpamHash generator.
   * Args: $form_id: The id of the HTML form into which a hash is inserted.
   */
  function SpamHash($form_id) {
    // A running session is required.
    $this->session_id = session_id();
    if (empty($this->session_id)) {
      session_start();
      $this->session_id = session_id();
    }

    // Random values.
    $this->user_rand      = strlen(ABSPATH) * 60124 % 32768;
    $this->form_id        = $form_id;
    $this->form_action    = $this->_get_random_string(rand(6, 18));
    $this->form_field_id  = $this->_get_random_string(rand(6, 18));
    $this->fn_enable_name = $this->_get_random_string(rand(6, 18));
    $md5_name             = $this->_get_random_string(rand(6, 18));
    $val_name             = $this->_get_random_string(rand(6, 18));
    $eElement             = $this->_get_random_string(rand(6, 18));
    $in_str               = $this->_get_random_string(rand(6, 18));
    
    /**
     * Define Javascript snippets.
     */
    // The Javascript that calculates the MD5 checksums.
    $bits = $this->_get_md5_javascript($md5_name);
    
    $script  = "function " . $this->form_action . "($in_str){";
    $script .=   "$eElement=document.getElementById('$this->form_field_id');";
    $script .=   "if(!$eElement){ return false; }";
    $script .=   "else { $eElement" . ".name = $md5_name($in_str);";
    $script .=   $eElement . ".value = $val_name(); return true; }}";
    $bits[]  = $script;
    
    $bits[] = $this->_get_field_value_js($val_name);

    // The Javascript that enables all input fields and the form.
    $script  = 'function ' . $this->fn_enable_name . '(){';
    $script .=   'form = document.getElementById("commentform");';
    $script .=   'inputs = form.getElementsByTagName("input");';
    $script .=   'for (var i = 0; i < inputs.length; i++) {';
    $script .=      'thisinput = inputs[i]; thisinput.disabled = false; }';
    //$script .=   'inputs = document.evaluate("//input", form, null,';
    //$script .=                              'XPathResult.UNORDERED_NODE_SNAPSHOT_TYPE, null);';
    //$script .=   'for (var i = 0; i < inputs.snapshotLength; i++) {';
    //$script .=      'thisinput = inputs.snapshotItem(i); thisinput.disabled = false; }';
    $script .= "document.getElementById('" . $this->form_id . "').style.display = 'block';";
    $script .= '}';
    $bits [] = $script;

    // Merge script snippets together.
    shuffle($bits);
    $this->js = '<script type="text/javascript">' . "\n"
              . '<!--'                            . "\n"
              . implode(" ", $bits)               . "\n"
              . '-->'                             . "\n"
              . '</script>'                       . "\n"
              . '<style type="text/css">#' . $this->form_id . "{display: none;}</style>\n";
  }
  
  /**
   * Args: $length:  The length of the string.
   * Returns: A random string with the given length.
   */
  function _get_random_string($length) {
    if ($length < 1)
      return '';
    srand((double)microtime() * 1000000);
    $alphabet = 'abcdefghifklmnopxyzABCDEFGHIFKLMNOPQRSTUVWXYZ';
    $len      = strlen($alphabet) - 1;
    $str      = '';
    while (strlen($str) < $length)
      $str .= $alphabet[rand(0, $len)];
    return $str;
  }

  /**
   * Takes: A string md5_function_name to call the md5 function
   * Returns: md5 javascript bits to be randomly spliced into the header
   */
  function &_get_md5_javascript($md5_function_name){
    $names    = array();
    $reserved = array('num', 'cnt', 'str', 'bin', 'length', 'len', 'var',
                      'Array', 'mask', 'return', 'function', 'new', 'msw',
                      'lsw', 'olda', 'oldb', 'oldc', 'oldd');
    for ($i = 0; $i < 22; $i++) {
      do {
        $str = $this->_get_random_string(rand(2, 8));
      } while (in_array($str, $reserved) || in_array($str, $names));
      array_push($names, $str);
    }

    $bits = array();
    $script  = 'function ' . $md5_function_name . '(s) {';
    $script .=   'return ' . $names[5] . '(' . $names[6] . '(' . $names[7] . '(s),s.length*8));}';
    $bits[] = $script;

    $script  = 'function ' . $names[6] . '(x,len){';
    $script .=   'x[len>>5]|=0x80<<((len)%32);';
    $script .=   'x[(((len+64)>>>9)<<4)+14]=len;';
    $script .=   'var a=1732584193;';
    $script .=   'var b=-271733879;';
    $script .=   'var c=-1732584194;';
    $script .=   'var d=271733878;';
    $script .=   'for(var i=0;i<x.length;i+=16){';
    $script .=     'var olda=a;var oldb=b;var oldc=c;var oldd=d;';
    $script .=     'a=' . $names[8] . '(a,b,c,d,x[i+0],7,-680876936);';
    $script .=     'd=' . $names[8] . '(d,a,b,c,x[i+1],12,-389564586);';
    $script .=     'c=' . $names[8] . '(c,d,a,b,x[i+2],17,606105819);';
    $script .=     'b=' . $names[8] . '(b,c,d,a,x[i+3],22,-1044525330);';
    $script .=     'a=' . $names[8] . '(a,b,c,d,x[i+4],7,-176418897);';
    $script .=     'd=' . $names[8] . '(d,a,b,c,x[i+5],12,1200080426);';
    $script .=     'c=' . $names[8] . '(c,d,a,b,x[i+6],17,-1473231341);';
    $script .=     'b=' . $names[8] . '(b,c,d,a,x[i+7],22,-45705983);';
    $script .=     'a=' . $names[8] . '(a,b,c,d,x[i+8],7,1770035416);';
    $script .=     'd=' . $names[8] . '(d,a,b,c,x[i+9],12,-1958414417);';
    $script .=     'c=' . $names[8] . '(c,d,a,b,x[i+10],17,-42063);';
    $script .=     'b=' . $names[8] . '(b,c,d,a,x[i+11],22,-1990404162);';
    $script .=     'a=' . $names[8] . '(a,b,c,d,x[i+12],7,1804603682);';
    $script .=     'd=' . $names[8] . '(d,a,b,c,x[i+13],12,-40341101);';
    $script .=     'c=' . $names[8] . '(c,d,a,b,x[i+14],17,-1502002290);';
    $script .=     'b=' . $names[8] . '(b,c,d,a,x[i+15],22,1236535329);';
    $script .=     'a=' . $names[9] . '(a,b,c,d,x[i+1],5,-165796510);';
    $script .=     'd=' . $names[9] . '(d,a,b,c,x[i+6],9,-1069501632);';
    $script .=     'c=' . $names[9] . '(c,d,a,b,x[i+11],14,643717713);';
    $script .=     'b=' . $names[9] . '(b,c,d,a,x[i+0],20,-373897302);';
    $script .=     'a=' . $names[9] . '(a,b,c,d,x[i+5],5,-701558691);';
    $script .=     'd=' . $names[9] . '(d,a,b,c,x[i+10],9,38016083);';
    $script .=     'c=' . $names[9] . '(c,d,a,b,x[i+15],14,-660478335);';
    $script .=     'b=' . $names[9] . '(b,c,d,a,x[i+4],20,-405537848);';
    $script .=     'a=' . $names[9] . '(a,b,c,d,x[i+9],5,568446438);';
    $script .=     'd=' . $names[9] . '(d,a,b,c,x[i+14],9,-1019803690);';
    $script .=     'c=' . $names[9] . '(c,d,a,b,x[i+3],14,-187363961);';
    $script .=     'b=' . $names[9] . '(b,c,d,a,x[i+8],20,1163531501);';
    $script .=     'a=' . $names[9] . '(a,b,c,d,x[i+13],5,-1444681467);';
    $script .=     'd=' . $names[9] . '(d,a,b,c,x[i+2],9,-51403784);';
    $script .=     'c=' . $names[9] . '(c,d,a,b,x[i+7],14,1735328473);';
    $script .=     'b=' . $names[9] . '(b,c,d,a,x[i+12],20,-1926607734);';
    $script .=     'a=' . $names[10] . '(a,b,c,d,x[i+5],4,-378558);';
    $script .=     'd=' . $names[10] . '(d,a,b,c,x[i+8],11,-2022574463);';
    $script .=     'c=' . $names[10] . '(c,d,a,b,x[i+11],16,1839030562);';
    $script .=     'b=' . $names[10] . '(b,c,d,a,x[i+14],23,-35309556);';
    $script .=     'a=' . $names[10] . '(a,b,c,d,x[i+1],4,-1530992060);';
    $script .=     'd=' . $names[10] . '(d,a,b,c,x[i+4],11,1272893353);';
    $script .=     'c=' . $names[10] . '(c,d,a,b,x[i+7],16,-155497632);';
    $script .=     'b=' . $names[10] . '(b,c,d,a,x[i+10],23,-1094730640);';
    $script .=     'a=' . $names[10] . '(a,b,c,d,x[i+13],4,681279174);';
    $script .=     'd=' . $names[10] . '(d,a,b,c,x[i+0],11,-358537222);';
    $script .=     'c=' . $names[10] . '(c,d,a,b,x[i+3],16,-722521979);';
    $script .=     'b=' . $names[10] . '(b,c,d,a,x[i+6],23,76029189);';
    $script .=     'a=' . $names[10] . '(a,b,c,d,x[i+9],4,-640364487);';
    $script .=     'd=' . $names[10] . '(d,a,b,c,x[i+12],11,-421815835);';
    $script .=     'c=' . $names[10] . '(c,d,a,b,x[i+15],16,530742520);';
    $script .=     'b=' . $names[10] . '(b,c,d,a,x[i+2],23,-995338651);';
    $script .=     'a=' . $names[11] . '(a,b,c,d,x[i+0],6,-198630844);';
    $script .=     'd=' . $names[11] . '(d,a,b,c,x[i+7],10,1126891415);';
    $script .=     'c=' . $names[11] . '(c,d,a,b,x[i+14],15,-1416354905);';
    $script .=     'b=' . $names[11] . '(b,c,d,a,x[i+5],21,-57434055);';
    $script .=     'a=' . $names[11] . '(a,b,c,d,x[i+12],6,1700485571);';
    $script .=     'd=' . $names[11] . '(d,a,b,c,x[i+3],10,-1894986606);';
    $script .=     'c=' . $names[11] . '(c,d,a,b,x[i+10],15,-1051523);';
    $script .=     'b=' . $names[11] . '(b,c,d,a,x[i+1],21,-2054922799);';
    $script .=     'a=' . $names[11] . '(a,b,c,d,x[i+8],6,1873313359);';
    $script .=     'd=' . $names[11] . '(d,a,b,c,x[i+15],10,-30611744);';
    $script .=     'c=' . $names[11] . '(c,d,a,b,x[i+6],15,-1560198380);';
    $script .=     'b=' . $names[11] . '(b,c,d,a,x[i+13],21,1309151649);';
    $script .=     'a=' . $names[11] . '(a,b,c,d,x[i+4],6,-145523070);';
    $script .=     'd=' . $names[11] . '(d,a,b,c,x[i+11],10,-1120210379);';
    $script .=     'c=' . $names[11] . '(c,d,a,b,x[i+2],15,718787259);';
    $script .=     'b=' . $names[11] . '(b,c,d,a,x[i+9],21,-343485551);';
    $script .=     'a=' . $names[13] . '(a,olda);';
    $script .=     'b=' . $names[13] . '(b,oldb);';
    $script .=     'c=' . $names[13] . '(c,oldc);';
    $script .=     'd=' . $names[13] . '(d,oldd);}';
    $script .=   'return Array(a,b,c,d);}';
    $bits[] = $script;
    
    $script  = 'function ' . $names[12] . '(q,a,b,x,s,t){';
    $script .=   'return ' . $names[13] . '(' . $names[16] . '(';
    $script .=               $names[13] . '(' . $names[13] . '(a,q),';
    $script .=               $names[13] . '(x,t)),s),b);}';
    $bits[] = $script;
    
    $script  = 'function ' . $names[8] . '(a,b,c,d,x,s,t){';
    $script .=   'return ' . $names[12] . '((b&c)|((~b)&d),a,b,x,s,t);}';
    $bits[] = $script;
    
    $script  = 'function ' . $names[9] . '(a,b,c,d,x,s,t){';
    $script .=   'return ' . $names[12] . '((b&d)|(c&(~d)),a,b,x,s,t);}';
    $bits[] = $script;
    
    $script  = 'function ' . $names[10] . '(a,b,c,d,x,s,t){';
    $script .=   'return ' . $names[12] . '(b ^ c ^ d,a,b,x,s,t);}';
    $bits[] = $script;
    
    $script  = 'function ' . $names[11] . '(a,b,c,d,x,s,t){';
    $script .=   'return ' . $names[12] . '(c ^(b|(~d)),a,b,x,s,t);}';
    $bits[] = $script;
    
    $script  = 'function ' . $names[13] . '(x,y){';
    $script .=   'var lsw=(x&0xFFFF)+(y&0xFFFF);';
    $script .=   'var msw=(x>>16)+(y>>16)+(lsw>>16);';
    $script .=   'return(msw<<16)|(lsw&0xFFFF);}';
    $bits[] = $script;
    
    $script  = 'function ' . $names[16] . '(' . $names[20] . ', ' . $names[21] . '){';
    $script .=   'return(' . $names[20] . ' << ' . $names[21] . ')';
    $script .=   '|(' . $names[20] . ' >>> (32 - ' . $names[21] . '));}';
    $bits[] = $script;
    
    $script  = 'function ' . $names[7] . '(' . $names[18] . '){';
    $script .=   'var ' . $names[17] . '=Array();';
    $script .=   'var ' . $names[19] . '=(1<<8)-1;';
    $script .=   'for(var i=0;i<' . $names[18] . '.length*8;i+=8)';
    $script .=     $names[17] . '[i>>5]|=(' . $names[18];
    $script .=                            '.charCodeAt(i/8)&' . $names[19] . ')<<(i%32);';
    $script .=     'return ' . $names[17] . ';}';
    $bits[] = $script;
    
    $script  = 'function ' . $names[5] . '(' . $names[15] . '){';
    $script .=   'var ' . $names[14] . '="0123456789abcdef";';
    $script .=   'var str="";';
    $script .=   'for(var i=0;i<' . $names[15] . '.length*4;i++){';
    $script .=   'str+=' . $names[14] . '.charAt((' . $names[15];
    $script .=   '[i>>2]>>((i%4)*8+4))&0xF)+' . $names[14] . '.charAt((';
    $script .=   $names[15] . '[i>>2]>>((i%4)*8))&0xF);}return str;}';
    $bits[] = $script;

    return $bits;
  }

  /**
   * Returns a reproducible md5 hash built from the session id + some salt.
   */
  function _get_special_code() {
		if (!($key = strip_tags($this->session_id)))
			$key = $_SERVER['REMOTE_ADDR'];
		return md5($key . ABSPATH . $_SERVER['HTTP_USER_AGENT'] . date("F j, Y, g a"));
  }

  /**
   * Takes: $val_name: Name of Javascript function.
   * Returns: A Javascript function with the given name, that computes the
   *          field value.
   */
  function _get_field_value_js($val_name) {
    $js = 'function ' . $val_name . '(){';
    
    $type = rand(0, 5);
    switch ($type) {
      /* Addition of n times of field value / n, + modulus */
      case 0:
        $eax = $this->_get_random_string(rand(8,10));
        $val = $this->user_rand;
        $inc = rand(1, $val - 1);
        $n = floor($val / $inc);
        $r = $val % $inc;
        
        $js .= "var $eax = $inc; ";
        for($i = 0; $i < $n - 1; $i++){
          $js .= "$eax += $inc; ";
        }
        
        $js .= "$eax += $r; ";
        $js .= "return $eax; ";
      
        break;
      
      /* Conversion from binary */
      case 1:
        $eax = $this->_get_random_string(rand(8,10));
        $ebx = $this->_get_random_string(rand(8,10));
        $ecx = $this->_get_random_string(rand(8,10));
        $val = $this->user_rand;
        $binval = strrev(base_convert($val, 10, 2));

        $js .= "var $eax = \"$binval\"; ";
        $js .= "var $ebx = 0; ";
        $js .= "var $ecx = 0; ";
        $js .= "while($ecx < $eax.length){ ";
        $js .= "if($eax.charAt($ecx) == \"1\") { ";
        $js .= "$ebx += Math.pow(2, $ecx); ";
        $js .= "} ";
        $js .= "$ecx++; ";
        $js .= "} ";
        $js .= "return $ebx; ";
        
        break;

      /* Multiplication of square roots */
      case 2:
        $val = $this->user_rand;
        $sqrt = floor(sqrt($val));
        $r = $val - ($sqrt * $sqrt);
        $js .= "return $sqrt * $sqrt + $r; ";
        break;

      /* Closest sum up to n */
      case 3:
        $val = $this->user_rand;
        $n = floor((sqrt(8*$val+1)-1)/2);
        $sum = $n * ($n + 1) / 2;
        $r = $val - $sum;
        $eax = $this->_get_random_string(rand(8,10));

        $js .= "var $eax = $r; ";
        for($i = 0; $i <= $n; $i++){
          $js .= "$eax += $i; ";
        }
        $js .= "return $eax; ";
        break;

      /* Closest sum up to n #2 */
      case 4:
        $val = $this->user_rand;
        $n = floor((sqrt(8*$val+1)-1)/2);
        $sum = $n * ($n + 1) / 2;
        $r = $val - $sum;

        $js .= "return $r ";
        for($i = 0; $i <= $n; $i++){
          $js .= "+ $i ";
        }
        $js .= ";";
        break;

      /* Closest sum up to n #3 */
      case 5:
        $val = $this->user_rand;
        $n = floor((sqrt(8*$val+1)-1)/2);
        $sum = $n * ($n + 1) / 2;
        $r = $val - $sum;
        $eax = $this->_get_random_string(rand(8,10));

        $js .= "var $eax = $r; var i; ";
        $js .= "for(i = 0; i <= $n; i++){ ";
        $js .= "$eax += i; ";
        $js .= "} ";
        $js .= "return $eax; ";
        break;
    }
    
    $js .= "} ";
    return $js;
  }
  
  /**
   * Takes: An array matching the form html.
   * Returns: The "improved" form html code.
   */
  function &_form_replace_callback(&$matches){
    $field_name = $this->_get_random_string(rand(6, 18));

    // Insert hidden field into the form.
    $hidden  = "<input type='hidden'";
    $hidden .= " id='"    . $this->form_field_id . "'";
    $hidden .= " name='"  . $field_name          . "'";
    $hidden .= " value='" . rand(100, 99999999)  . "' />\n";
    $text    = preg_replace("/<input[^>]*?>/si",
                            "$hidden\n$0",
                            $matches[0],
                            1);
  
    // Disable all input elements.
    $text = preg_replace('/<input([^>]*?)/si', '<input disabled="disabled"$1', $text);
    
    // Register the Javascript that does the calculation, so that it is
    // called when sending the form.
    $js   = $this->form_action . "('" . $this->_get_special_code() . "');";
	  $text = str_replace('<form', "<form onsubmit=\"$js\"", $text);

    // Show a better message for browsers that have Javascript disabled.
    $str  = "<noscript><p>Due to spam protection mechanisms, your browser";
    $str .= " must have Javascript enabled to post a message. Please enable";
    $str .= " Javascript and reload this page to add your comment.";
    $str .= " Sorry about that...</p></noscript>\n";
	  return str_replace("<form", "$str<form", $text);
  }

  /**
   * Takes: A single HTML header.
   * Returns: The same page with a Javascript code added.
   */
  function &insert_header_code(&$page) {
    // Insert the bulk of the snippets into <head>.
    return str_replace("</head>", "$this->js</head>", $page);
  }


  /**
   * Takes: A single HTML header.
   * Returns: The same page with a Javascript code added.
   */
  function &insert_body_code(&$page) {
    return str_replace('<body', '<body onload="' . $this->fn_enable_name . '();"', $page);
  }


  /**
   * Takes: A single HTML header.
   * Returns: The same page with a Javascript code added.
   */
  function &insert_form_code(&$page) {
    // Insert snippets into the form. (hide input fields, register
    // onsubmit(), etc)
    $form = '/<form[^>]*?' . $this->form_id . '.*?<\/form>/si';
    return preg_replace_callback($form, array(&$this, "_form_replace_callback"), $page);
  }


  /**
   * Returns: 0 if the tag matches, an error code otherwise.
   */
  function check_hash() {
    // Our special codes, fixed to check the previous hour
    $salt = ABSPATH . $_SERVER['HTTP_USER_AGENT'];
    $special = array();
    $special[] = md5($_SERVER['REMOTE_ADDR']  . $salt . date("F j, Y, g a"));
    $special[] = md5($_SERVER['REMOTE_ADDR']  . $salt . date("F j, Y, g a", time() - (60 * 60)));
    $special[] = md5(strip_tags(session_id()) . $salt . date("F j, Y, g a"));
    $special[] = md5(strip_tags(session_id()) . $salt . date("F j, Y, g a", time() - (60 * 60)));
    foreach($special as $val){
      //echo "Bla: $val/" . $_POST[md5($val)] . "<br>";
      if($_POST[md5($val)] == $this->user_rand)
        return 0;
    }

    // Be more user friendly if we detect spam, and it sends a referer
    if (strlen(trim($_SERVER['HTTP_REFERER'])) <= 0)
      return SPAMHASH_ERROR_REFERRER;

    // Try to determine the sources of the error.
    if(!session_id() && strlen($_SERVER['REMOTE_ADDR']) < 1)
      return SPAMHASH_ERROR_REMOTE_ADDRESS;

    if (!session_id())
      return SPAMHASH_ERROR_SESSION;

    $hashash = false;
    foreach($special as $spec){
      if(array_key_exists($spec, $_POST))
        $hashash = true;
    }
    if (!$hashash)
      return SPAMHASH_ERROR_HASH_MISSING;

    return SPAMHASH_ERROR_UNKNOWN;
  }
}
?>
