<?
  $cfg[db_host]         = 'localhost';  // Your database hostname, usually
                                        // 'localhost'.
  $cfg[db_usr]          = 'user';       // Username on your database host.
  $cfg[db_pass]         = 'password';   // Password on your database host.
  $cfg[db_name]         = 'tefinch';    // Database name.
  $cfg[db_tablebase]    = 'tefinch';    // Table basename, if unsure leave
                                        // unchanged.
  $cfg[db_backend]      = 'matpath';    // Algorithm. Don't touch if unsure.
  
  $cfg[lang]            = 'english';    // Forum language. The language files
                                        // are in the "language/" subfolder.
  $cfg[urlvars]         = array('sid'); // Lets you append additional variables
                                        // to every URL. If unsure leave
                                        // unchanged.
  
  // *************************************************************
  // Forum appearance.
  $cfg[theme]               = 'heise';  // Theme used. Files are in 'themes/' 
                                        // subfolder
  $cfg[tpp]                 = 8;        // Maximum number of threads shown per
                                        // page. (when shown in thread order)
  $cfg[epp]                 = 15;       // Maximum number of messages shown per
                                        // page. (when shown in time order)
  $cfg[ppi]                 = 5;        // Maximum number of pages shown in the
                                        // index before the [...] button
                                        // appears.
  $cfg[remember_page]       = FALSE;    // If TRUE, the current page in the
                                        // index is remembered even when reading
                                        // a message. This comes at the cost
                                        // of less stable URLs.
  $cfg[max_msglength]       = 10000;    // Maximum length of a message.
  $cfg[max_linelength_soft] = 80;       // Number of characters before a line
                                        // wraps.
  $cfg[max_linelength_hard] = 120;      // Number of characters before a quoted
                                        // line wraps.
  $cfg[max_titlelength]     = 70;       // Maximum length of the title
  $cfg[max_namelength]      = 50;       // Maximum lenght of the name
?>
