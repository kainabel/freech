<?php
  // *************************************************************
  // Essential settings.
  // *************************************************************
  // You MUST change this. Enter any random value.
  $cfg[salt] = "";

  // Database settings.
  $cfg[db_host] = 'localhost'; // Your database server, usually 'localhost'.
  $cfg[db_usr]  = 'user';      // Username on your database host.
  $cfg[db_pass] = 'password';  // Password on your database host.
  $cfg[db_name] = 'freech';    // Database name.

  // Forum language. For a list of supported languages please have a look 
  // at the language files in the "language/" subdirectory.
  $cfg[lang] = 'english';

  // The country code of the language of your site.
  $cfg[site_language] = 'en';

  // The address used in the "from" field of any mail sent by the forum.
  $cfg[mail_from] = 'noreply@debain.org';

  // The URL of your domain.
  $cfg[site_url] = 'http://debain.org/';

  // A human readable title for your forum.
  $cfg[site_title] = 'Freech Forum';

  // The descripton included in the RSS.
  $cfg[rss_description] = 'Freech Discussion Forum';

  // *************************************************************
  // Forum appearance.
  // *************************************************************
  // For a list of available themes please have a look into the 'themes/'
  // subdirectory.
  $cfg[theme] = 'heise';

  // Maximum number of threads shown per page. (when shown in thread order)
  $cfg[tpp] = 16;

  // Maximum number of messages shown per page. (when shown in time order)
  $cfg[epp] = 30;

  // Maximum number of pages shown in the index before the [...] button
  // appears.
  $cfg[ppi] = 5;

  // Show threads with new posts first. Note that setting this to TRUE has
  // a negative performance impact.
  $cfg[updated_threads_first] = FALSE;

  // Disable the message counter that is shown above the forum. Note that
  // disabling the search provides a significant performance gain.
  $cfg[disable_message_counter] = FALSE;

  // Disable the forum search.
  $cfg[disable_search] = FALSE;

  // May users edit their postings?
  $cfg[postings_editable] = TRUE;

  // If TRUE, ">>" points to the previous thread.
  $cfg[thread_arrow_rev] = TRUE;

  // If TRUE, the current page in the index is remembered even when reading
  // a message. This comes at the cost of less stable URLs.
  $cfg[remember_page] = FALSE;

  // Whether to convert URL into links, and which pattern these URLs must
  // match.
  $cfg[autolink_urls] = TRUE;
  $cfg[autolink_pattern] = '(ht|f)tp:\/\/[\w\._\-\/\?\&=\%;,\+\(\)]+';

  // The time a posting is considered new (and highlighted). In seconds.
  $cfg[new_post_time] = 60*60*24;

  // Maximum length of the subject line.
  $cfg[max_subjectlength] = 70;

  // Maximum length of a message. If the length is exceeded the forum displays
  // an error message at the time a message is submitted.
  $cfg[max_msglength] = 10000;

  // Number of characters before a line in a message wraps.
  $cfg[max_linelength_soft] = 80;

  // Number of characters before a quoted line in a message wraps.
  $cfg[max_linelength_hard] = 120;

  // The default number of entries in your RSS file (if the 'len' attribute
  // is not passed as a GET variable).
  $cfg[rss_items] = 10;

  // The maximum number of entries in your RSS file.
  $cfg[rss_maxitems] = 20;

  // The following options define which values are allowed in the user
  // profile.
  $cfg[min_usernamelength]    = 3;
  $cfg[max_usernamelength]    = 30;
  $cfg[username_pattern]      = "/^[a-z0-9 _\-\.]+$/i";
  $cfg[min_passwordlength]    = 5;
  $cfg[max_passwordlength]    = 20;
  $cfg[min_firstnamelength]   = 3;
  $cfg[max_firstnamelength]   = 30;
  $cfg[min_lastnamelength]    = 3;
  $cfg[max_lastnamelength]    = 30;
  $cfg[max_maillength]        = 100;
  $cfg[max_homepageurllength] = 100;
  $cfg[max_imlength]          = 100;
  $cfg[max_signaturelength]   = 255;

  // *************************************************************
  // Advanced settings.
  // *************************************************************
  // If unsure leave unchanged. Currently only MySQL is supported.
  $cfg[db_dbn]  = "mysqlt://$cfg[db_usr]:"
                . urlencode($cfg[db_pass])
                . "@$cfg[db_host]/$cfg[db_name]";

  // Allows for adding a prefix to your database table names.
  // Note that you will have to rename the tables yourself.
  // If unsure, leave this setting unchanged.
  $cfg[db_tablebase] = 'freech_';

  // When embedding the forum into your own websites it may be useful to carry
  // along additional variables in the forum. Every key/value pair in this
  // array is automatically added to all URLs created by the forum.
  $cfg[urlvars] = array(
    'sid' => $_GET[sid]
  );
  
  // Defines the time a user stays logged in when the "remember password"
  // flag is set below the login form. Note that your PHP configuration must
  // allow long session timeouts for this to work.
  $cfg[login_time] = 60*60*24*120;

  // Newly registered users are added into the group with the given ID.
  $cfg[default_group_id] = 3;

  // Anonymous users are automatically logged into the forum as the user/group
  // with the given id.
  $cfg[anonymous_user_id]  = 2;
  $cfg[anonymous_group_id] = 2;

  // *************************************************************
  // Performance tweaks.
  // *************************************************************
  // Whether to check templates for changes. Setting this to FALSE
  // will significantly enhance performance at the cost of changed
  // templates getting out of date.
  $cfg[check_cache] = TRUE;

  // If the following is set to FALSE, the permissions set for anonymous
  // users in the DB are ignored. Instead, the forum will use a default
  // set of permissions.
  // Setting this to TRUE has a negative performance impact because the
  // database needs to be contacted to determine the user permissions.
  $cfg[manage_anonymous_users] = TRUE;

  // If manage_anonymous_users is FALSE, the default anonymous user uses
  // the following name.
  $cfg[anonymous_group_name] = 'anonymous';
?>
