<?
  // Database settings.
  $cfg[salt]            = ""; // !!!CHANGE THIS!!! to a random value.
  $cfg[db_host]         = 'localhost';  // Your database hostname, usually
                                        // 'localhost'.
  $cfg[db_usr]          = 'user';       // Username on your database host.
  $cfg[db_pass]         = 'password';   // Password on your database host.
  $cfg[db_name]         = 'freech';    // Database name.
  $cfg[db_dbn]          = "mysqlt://$cfg[db_usr]:" // If unsure leave unchanged.
                        . urlencode($cfg[db_pass])
                        . "@$cfg[db_host]/$cfg[db_name]";
  $cfg[db_tablebase]    = 'freech_';   // Table basename, if unsure leave
                                        // unchanged.

  // Site and RSS settings.
  $cfg[lang]            = 'english';    // Forum language. The language files
                                        // are in the "language/" subfolder.
  $cfg[mail_from]       = 'noreply@debain.org';  // Sender address of mails 
                                                 // sent by the forum.
  $cfg[site_url]        = 'http://debain.org/';  // URL of your domain.
  $cfg[site_title]      = 'Freech Forum';       // The name of your site.
  $cfg[site_language]   = 'en';         // The country code of the language 
                                        // of your site.
  $cfg[rss_description] = 'Das Forum fuer den Notfall';
                                        // The descripton included in the RSS.
  
  // URL settings.
  $cfg[urlvars]         = array(        // Lets you append additional variables
    'sid' => $_GET[sid]                 // to every URL. If unsure leave
  );                                    // unchanged.
  
  // *************************************************************
  // Forum appearance.
  $cfg[theme]               = 'heise';  // Theme used. Files are in 'themes/' 
                                        // subfolder
  $cfg[tpp]                 = 16;       // Maximum number of threads shown per
                                        // page. (when shown in thread order)
  $cfg[epp]                 = 15;       // Maximum number of messages shown per
                                        // page. (when shown in time order)
  $cfg[ppi]                 = 5;        // Maximum number of pages shown in the
                                        // index before the [...] button
                                        // appears.
  $cfg[updated_threads_first] = TRUE;   // Show threads with new posts first.
  $cfg[thread_arrow_rev]    = TRUE;     // If TRUE, ">>" points to the previous
                                        // thread.
  $cfg[remember_page]       = FALSE;    // If TRUE, the current page in the
                                        // index is remembered even when reading
                                        // a message. This comes at the cost
                                        // of less stable URLs.
  $cfg[autolink_urls]       = TRUE;     // Whether to convert URL into links.
  $cfg[autolink_pattern]    = '(ht|f)tp:\/\/[\w\._\-\/\?\&=\%;,]+';
                                        // If unsure leave unchanged.
  $cfg[max_msglength]       = 10000;    // Maximum length of a message.
  $cfg[new_post_time]       = 60*60*24; // Seconds a posting is considered new.
  $cfg[max_linelength_soft] = 80;       // Number of characters before a line
                                        // wraps.
  $cfg[max_linelength_hard] = 120;      // Number of characters before a quoted
                                        // line wraps.
  $cfg[max_titlelength]     = 70;       // Maximum length of the title
  $cfg[max_namelength]      = 50;       // Maximum lenght of the name
  $cfg[rss_items]           = 10;       // The default number of entries in
                                        // your RSS file.
  $cfg[rss_maxitems]        = 20;       // The maximum number of entries in
                                        // your RSS file.

  // *************************************************************
  // Usermanagement
  $cfg[login_time]              = 60*60*24*120; // maximum lifetime of login-cookies in seconds
  $cfg[min_loginlength]         = 3;
  $cfg[max_loginlength]         = 30;
  $cfg[login_pattern]           = "/^[a-z0-9 _\-\.]+$/i";
  $cfg[min_passwordlength]      = 5;
  $cfg[max_passwordlength]      = 20;
  $cfg[min_firstnamelength]     = 3;
  $cfg[max_firstnamelength]     = 30;
  $cfg[min_lastnamelength]      = 3;
  $cfg[max_lastnamelength]      = 30;
  $cfg[max_maillength]          = 50;
  $cfg[max_homepageurllength]   = 30;
  $cfg[max_imlength]            = 20;
  $cfg[max_signaturelength]     = 100;
?>
