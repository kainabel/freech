<?
  $cfg[db_host]      = 'localhost';   // Your database hostname, usually 'localhost'.
  $cfg[db_usr]       = 'user';        // Username on your database host.
  $cfg[db_pass]      = 'password';    // Password on your database host.
  $cfg[db_name]      = 'tefinch';     // Database name.
  $cfg[db_tablebase] = 'forum';       // Table basename, if unsure leave unchanged.
  $cfg[db_backend]   = 'matpath';     // Algorithm. Don't touch unless you know.
  
  $cfg[lang]         = 'german';      // Forum language. The language files are in the
                                      // "language/" subfolder.
  $cfg[urlvars]      = array('sid');  // Lets you append additional variables to every
                                      // URL. If unsure leave unchanged.
  
  // *************************************************************
  // Forum appearance preferences.
  $cfg[tpp]          = 8;             // Maximum number of threads shown per page.
  $cfg[ppi]          = 5;             // Maximum number of pages shown in the index
                                      // before the [...] button is shown.
?>
