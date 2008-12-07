<?php
  /*
  Freech.
  Copyright (C) 2003 Samuel Abels, <http://debain.org>

  This program is free software; you can redistribute it and/or modify
  it under the terms of the GNU General Public License as published by
  the Free Software Foundation; either version 2 of the License, or
  (at your option) any later version.

  This program is distributed in the hope that it will be useful,
  but WITHOUT ANY WARRANTY; without even the implied warranty of
  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
  GNU General Public License for more details.
 
  You should have received a copy of the GNU General Public License
  along with this program; if not, write to the Free Software
  Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
  */
?>
<?php
// German language file, 2003 by Samuel Abels

  // Forum
  $lang[countrycode]      = "en";
  $lang[dateformat]       = "y-m-d H:i";
  $lang[index]            = "Page";
  $lang[forum]            = "Forum";
  $lang[forum_long]       = "Forum ([MESSAGES] messages, [NEWMESSAGES] new,"
                          . " currently [ONLINEUSERS] users online)";
  $lang[unfoldall]        = "Unfold All";
  $lang[foldall]          = "Fold All";
  $lang[prev]             = "Older Threads";
  $lang[next]             = "Newer Threads";
  $lang[prev_symbol]      = "<<";
  $lang[next_symbol]      = ">>";
  $lang[entry]            = "Message";
  $lang[thread]           = "Thread";
  $lang[threadview]       = "Order by Thread";
  $lang[plainview]        = "Order by Date";
  $lang[editmessage]      = "Edit";
  $lang[writeanswer]      = "Reply";
  $lang[writemessage]     = "New Message";
  $lang[noentries]        = "(No entries yet)";
  $lang[entryindex]       = "Message Overview";
  $lang[hidethread]       = "Hide Thread";
  $lang[showthread]       = "Show Thread";
  $lang[postedby]         = "Written by [USER]";
  $lang[lastupdated]      = "(last edited [TIME])";
  $lang[moderator]        = "Moderator";
  $lang[anonymous]        = "Anonymous User";
  $lang[registered]       = "Registered User";
  $lang[deleted]          = "Deleted User";

  $lang[blockedtitle]     = "Blocked Message";
  $lang[blockedentry]     = "This entry was blocked because it violated "
                          . "our terms of usage.";
  
  $lang[noentrytitle]     = "No Such Message";
  $lang[noentrybody]      = "A message with the given ID does not exist.";
  
  // Compose
  $lang[writeamessage]    = "Write a New Message";
  $lang[writeananswer]    = "Write a Reply";
  $lang[required]         = "(required)";
  $lang[name]             = "Name";
  $lang[msgtitle]         = "Subject";
  $lang[msgbody]          = "Message";
  $lang[msgimage]         = "Image";
  $lang[preview]          = "Preview";
  $lang[quote]            = "Quote Message";
  $lang[answer]           = "Re: ";
  $lang[wrote]            = "[USER] wrote on [TIME]:";
  
  // Preview
  $lang[reallysend]       = "Preview";
  $lang[somethingmissing] = "Warning! Your message is incomplete.";
  $lang[onlywhitespace]   = "Warning! Your message has no visible text."; // Not used
  $lang[messagetoolong]   = "Your message is longer than $cfg[max_msglength] characters. Please reduce the text length.";
  $lang[messageduplicate] = "Your message has already been sent.";
  $lang[pvw_invalidchars] = "Warning! Your message contains invalid characters.";
  $lang[nametoolong]      = "Your chosen name is longer than $cfg[max_usernamelength] characters. Please choose a shorter name.";
  $lang[titletoolong]     = "The subject is longer than $cfg[max_titlelength] characters. Please choose a shorter subject.";
  $lang[forgotname]       = "Please enter a name."; // Not used
  $lang[forgottitle]      = "Please enter a title."; // Not used
  $lang[forgottext]       = "No text was entered."; // Not used
  $lang[change]           = "Edit Message";
  $lang[send]             = "Send Message";
  
  // Submit.
  $lang[entrysuccess]     = "Your message was saved.";
  $lang[backtoentry]      = "Show Your Message";
  $lang[backtoparent]     = "Show the Answered Message";
  $lang[backtoindex]      = "Go Back to the Forum";
  
  // Registration
  $lang[register]            = "Register Account";
  $lang[register_title]      = "User Registration";
  $lang[register_welcome]    = "Welcome!\n"
                             . "By registering you will be able to"
                             . " participate in our discussion forum."
                             . " All you need is a valid email address and a"
                             . " few minutes of your time.";
  $lang[register_privacy]    = "The e-mail address is"
                             . " used only for confirming your registration"
                             . " details, and for sending you a password"
                             . " reminder if you ever forget yours."
                             . " None of your personal data is given to any"
                             . " third parties.";
  $lang[register_nick]       = "Username:";
  $lang[register_nick_l]     = "Please select a username (letters, digits,"
                             . " and spaces only). The username is used to log"
                             . " into your account later.";
  $lang[register_fullname]   = "Firstname And Lastname:";
  $lang[register_fullname_l] = "Please enter valid information, "
                             . " your registration is invalid otherwise"
                             . " and may be deleted.";
  $lang[register_mail]       = "Email Address:";
  $lang[register_publicmail] = "Public Email:";
  $lang[register_public_l]   = "Click here if you want other users to be able"
                             . " to see the address.";
  $lang[register_term_title] = "Terms of Usage:";
  $lang[register_term]       = "By clicking Register below you agree that"
                             . " we may store your personal data as stated"
                             . " above. Your also agree to be bound by the"
                             . " conditions of usage.";
  $lang[register_agree]      = "I Agree, Register";
  $lang[register_disagree]   = "I Do Not Agree, Cancel";
  $lang[register_mail_sent]  = "A confirmation mail has been sent.";
  $lang[invalidmail]         = "Please enter a valid email address.";
  $lang[invalidhomepage]     = "Please enter a valid homepage URL.";
  $lang[invalidfirstname]    = "Please enter a valid first name.";
  $lang[invalidlastname]     = "Please enter a valid last name.";

  // Registration mail.
  $lang[registration_mail_subject] = "Your registration at $cfg[site_title]";
  $lang[registration_mail_body]    = "Hello [FIRSTNAME] [LASTNAME],\n"
                                   . "\n"
                                   . "Thank you for registering at"
                                   . " $cfg[site_title]. Your account name"
                                   . " is \"[LOGIN]\".\n"
                                   . "\n"
                                   . "Please confirm your email address by"
                                   . " clicking the registration link below."
                                   . "\n"
                                   . "[URL]\n";
  
  // Change password.
  $lang[change_password_title] = "Password Change";
  $lang[change_password_text]  = "Please assign your personal login password.";
  $lang[change_password_btn]   = "Change Password";
  $lang[change_password]       = "Password:";
  $lang[change_password2]      = "Repeat:";
  $lang[passwordsdonotmatch]   = "Error: Passwords do not match.";
  $lang[passwordtooshort]      = "Please choose a password with at least"
                               . " $cfg[min_passwordlength] characters.";
  $lang[passwordtoolong]       = "Please choose a password with at most"
                               . " $cfg[max_passwordlength] characters.";
  $lang[password_changed]      = "Your password was changed. You may"
                               . " now log in.";

  // Reset password mail.
  $lang[password_mail_sent] = "An email with instructions for resetting"
                            . " your password has been sent to your email"
                            . " address.";
  $lang[reset_mail_subject] = "Your password at $cfg[site_title]";
  $lang[reset_mail_body]    = "Hello [FIRSTNAME] [LASTNAME],\n"
                            . "\n"
                            . "We have received a password reset request"
                            . " for your account \"[LOGIN]\".\n"
                            . "\n"
                            . "To change your password please click the link"
                            . " below. If you did not request that your"
                            . " password be changed you may ignore"
                            . " this message.\n"
                            . "\n"
                            . "[URL]\n";
  
  // Login
  $lang[login_text]       = "To log in your browser must support cookies.";
  $lang[havetoregister]   = "In order to be able to use personalized features"
                          . " you need to <a href='registration/'>register</a>."
                          . " After that you can log in.";
  $lang[resetpasswd_title] = "Resetting Your Password";
  $lang[resetpasswd]      = "Reset Password";
  $lang[nosuchmail]       = "The given email address was not found.";
  $lang[passwdforgotten]  = "Forgot your password?";
  $lang[enteruserdata]    = "Enter User Data";
  $lang[username]         = "Username";
  $lang[passwd]           = "Password";
  $lang[rememberpasswd]   = "Remember password";
  $lang[remembpasswdlong] = "Click here if you want do set a persistent cookie.";
  $lang[login]            = "Log in";
  $lang[logout]           = "Log out";
  $lang[loginfailed]      = "Login failed.";
  $lang[loginunconfirmed] = "Your account is not yet confirmed.";
  $lang[loginlocked]      = "Your account is locked.";
  $lang[resendconfirm]    = "Resend confirmation email";
  $lang[logintooshort]    = "Your chosen name is too short. Please enter at"
                          . "least $cfg[min_usernamelength] characters.";
  $lang[logintoolong]     = "Your chosen name is too long. Please enter at"
                          . "most $cfg[max_usernamelength] characters.";
  $lang[logininvalidchars]    = "Your login name contains invalid characters."
                              . " Please enter letters, digits or spaces only.";
  $lang[usernamenotavailable] = "The entered username is not available.";

  // User profile and personal data.
  $lang[profile]            = "Profile of [NAME]";
  $lang[account_data]       = "Personal Data of [NAME]";
  $lang[account_status]     = "Account status:";
  $lang[account_created]    = "Member since:";
  $lang[account_messages]   = "Forum postings:";
  $lang[account_name]       = "Username:";
  $lang[account_firstname]  = "Firstname:";
  $lang[account_lastname]   = "Lastname:";
  $lang[account_mail]       = "Email:";
  $lang[account_publicmail] = "Display this email address publicly";
  $lang[account_hiddenmail] = "Not public";
  $lang[account_homepage]   = "Homepage:";
  $lang[account_im]         = "Instant messenger:";
  $lang[account_signature]  = "Signature:";
  $lang[account_password]   = "Password:";
  $lang[account_password2]  = "Repeat:";
  $lang[account_save]       = "Save Personal Data";
  $lang[account_saved]      = "Your data has been saved.";
  $lang[account_emptyfield] = "None";
  $lang[account_edit]       = "[Edit]";

  $lang[USER_STATUS_ACTIVE]      = "Active";
  $lang[USER_STATUS_UNCONFIRMED] = "Unconfirmed";
  $lang[USER_STATUS_BLOCKED]     = "Locked";

  // Group profile.
  $lang[group_name] = "User group:";

  // Top user list.
  $lang[n_postings] = "Postings";

  // Search.
  $lang[search_forum]      = "Search this forum";
  $lang[search_title]      = "Search";
  $lang[msg_search_start]  = "Find Postings";
  $lang[user_search_start] = "Find Users";
  $lang[search_results]    = "[RESULTS] results found.";
  $lang[search_examples]   = "Examples:";
  $lang[search_hint]       = "apples AND \"peaches\"<br/>"
                           . "NOT user:\"that guy\" AND (text:banana OR subject:fruit)<br/>"
                           . "ban?na AND NOT subject:wildcard*matching";
?>
