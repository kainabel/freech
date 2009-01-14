<?php
  /*
  Freech.
  Copyright (C) 2008 Samuel Abels, <http://debain.org>

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
  // Home page.
  $lang[home]             = "Startseite";
  $lang[home_welcome]     = "Herzlich Willkommen!";
  $lang[home_intro]       = "Bitte wählen sie ein Forum aus der Liste.";
  $lang[home_forum_links] = "Foreninformationen";
  $lang[home_activity]    = "Neueste Forenbeiträge";
  $lang[home_new_users]   = "Neueste User";

  // Forum
  $lang[countrycode]      = "de";
  $lang[dateformat]       = "y-m-d H:i";
  $lang[forum]            = "Forum";
  $lang[forum_status]     = "[POSTINGS] Beiträge, [NEWPOSTINGS] neu";
  $lang[forum_n_online]   = "[USERS] User online";
  $lang[breadcrumbs]      = "Sie sind hier:";
  $lang[unfoldall]        = "Alles aufklappen";
  $lang[foldall]          = "Alles zuklappen";
  $lang[prev]             = "Ältere";
  $lang[next]             = "Neuere";
  $lang[prev_symbol]      = "<<";
  $lang[next_symbol]      = ">>";
  $lang[entry]            = "Beitrag";
  $lang[thread]           = "Thread";
  $lang[threadview]       = "Thread-Darstellung";
  $lang[listview]         = "Flache Darstellung";
  $lang[editposting]      = "Bearbeiten";
  $lang[writeanswer]      = "Beantworten";
  $lang[writemessage]     = "Neues Thema";
  $lang[entryindex]       = "Beitragsübersicht";
  $lang[hidethread]       = "Thread-Anzeige ausblenden";
  $lang[showthread]       = "Thread-Anzeige einblenden";
  $lang[hidelist]         = "Posting-Liste ausblenden";
  $lang[showlist]         = "Posting-Liste einblenden";
  $lang[postedby]         = "Beitrag von [USER]";
  $lang[lastupdated]      = "(Editiert am [TIME])";
  $lang[moderator]        = "Moderator";
  $lang[anonymous]        = "Anonymer User";
  $lang[registered]       = "Registrierter User";
  $lang[deleted]          = "Gelöschter User";
  $lang[ip_hash]          = "(Salziger IP-Hash: [HASH])";

  // Message administration.
  $lang[posting_stick]    = "Pin setzen";
  $lang[posting_unstick]  = "Pin entfernen";
  $lang[blockedtitle]     = "Gesperrter Beitrag";
  $lang[blockedentry]     = "Der an dieser Stelle platzierte Kommentar enthielt "
                           ."eine rechtswidrige Äußerung oder verletzte "
                           ."grob die Nutzungsbedingungen für unsere "
                           . "Diskussionsforen. Er ist daher gelöscht worden.";

  $lang[noentrytitle]     = "Dieser Beitrag ist nicht vorhanden";
  $lang[noentrybody]      = "Der von ihnen angeforderte Eintrag existiert in diesem"
                           ." Forum nicht";

  // Compose
  $lang[writeamessage]     = "Neuen Beitrag für das Forum schreiben";
  $lang[writeananswer]     = "Antwort für das Forum schreiben";
  $lang[required]          = "(bitte unbedingt ausfüllen)";
  $lang[name]              = "Name";
  $lang[msgtitle]          = "Überschrift";
  $lang[msgbody]           = "Beitrag";
  $lang[msgimage]          = "Bild";
  $lang[preview]           = "Vorschau";
  $lang[quote]             = "Zitat einfügen";
  $lang[answer]            = "Re: ";
  $lang[wrote]             = "[USER] schrieb am [TIME]";
  $lang[too_many_postings] = "Sie haben zu viele Nachrichten abgeschickt."
                           . " Nächstes Posting ist erst in [SECONDS]"
                           . " Sekunden wieder möglich.";

  // Preview
  $lang[reallysend]       = "Vorschau";
  $lang[somethingmissing] = "Achtung! Ihr Beitrag ist nicht vollständig.";
  $lang[onlywhitespace]   = "Achtung! Ihr Beitrag enthält keinen sichtbaren Text."; // Not used
  $lang[messagetoolong]   = "Ihr Beitrag ist länger als $cfg[max_msglength] Zeichen. Bitte kürzen sie den Text.";
  $lang[pvw_invalidchars] = "Achtung! Ihr Beitrag enthält ungültige Zeichen.";
  $lang[nametoolong]      = "Der von ihnen gewählte Name ist länger als $cfg[max_usernamelength] Zeichen. Bitte kürzen sie den Namen.";
  $lang[titletoolong]     = "Der von ihnen gewählte Beitragstitel ist länger als $cfg[max_subjectlength] Zeichen. Bitte kürzen sie den Titel.";
  $lang[forgotname]       = "Bitte einen Namen eingeben."; // Not used
  $lang[forgottitle]      = "Bitte einen Titel eingeben."; // Not used
  $lang[forgottext]       = "Sie haben keinen Text eingegeben."; // Not used
  $lang[change]           = "Bearbeiten";
  $lang[send]             = "Abschicken";

  // Registration
  $lang[register]            = "Account registrieren";
  $lang[register_title]      = "Benutzer-Registrierung";
  $lang[register_welcome]    = "Herzlich willkommen!\n\n"
                             . "Bitte registrieren Sie sich hier, um aktiv an"
                             . " den Diskussionsforen und an anderen Diensten"
                             . " (wie zum Beispiel Online-Umfragen)"
                             . " teilnehmen zu können. Sie benötigen"
                             . " dafür lediglich eine gültige E-Mail-Adresse"
                             . " und wenige Minuten Zeit.";
  $lang[register_privacy]    = "Wir respektieren den Datenschutz und"
                             . " garantieren, dass wir Ihre persönlichen"
                             . " Angaben strikt vertraulich behandeln. Bitte"
                             . " lesen Sie die Erklärung zum Datenschutz.";
  $lang[register_nick]       = "Benutzername:";
  $lang[register_nick_l]     = "Wählen Sie einen Benutzernamen (nur"
                             . " Buchstaben, Zahlen, oder Leerzeichen,"
                             . " keine Umlaute) zu Ihrer Identifikation bei"
                             . " der Registrierung.";
  $lang[register_fullname]   = "Vor- und Nachname:";
  $lang[register_fullname_l] = "Geben Sie bitte auf jeden Fall Ihren"
                             . " vollständigen Namen an, da die"
                             . " Registrierung sonst nicht gültig ist.";
  $lang[register_mail]       = "E-Mail-Adresse:";
  $lang[register_publicmail] = "Öffentliche E-Mail-Adresse:";
  $lang[register_public_l]   = "Klicken Sie hier, wenn Ihre E-Mail-Adresse"
                             . " über Ihren Beiträgen erscheinen soll.";
  $lang[register_term_title] = "Einwilligungserklärung:";
  $lang[register_term]       = "Wenn Sie mit der Speicherung Ihrer"
                             . " personenbezogenen Daten sowie den"
                             . " vorstehenden Regeln und Bestimmungen der"
                             . " Nutzungsvereinbarung einverstanden sind,"
                             . " können Sie mit einem Klick auf den"
                             . " \"Zustimmen\"-Button unten fortfahren."
                             . " Ansonsten drücken Sie bitte \"Abbrechen\".";
  $lang[register_agree]      = "Ich stimme zu";
  $lang[register_disagree]   = "Abbrechen";
  $lang[register_mail_sent]  = "Eine Bestätigungsmail wurde an Ihre Email-Adresse versandt.";
  $lang[register_done]       = "Vielen Dank, ihre Registrierung ist nun vollständig.";
  $lang[invalidmail]         = "Bitte geben Sie eine gueltige Email-Adresse ein.";
  $lang[invalidfirstname]    = "Bitte geben Sie einen gueltigen Vornamen ein.";
  $lang[invalidlastname]     = "Bitte geben Sie einen gueltigen Nachnamen ein.";
  $lang[invalidhomepage]     = "Bitte geben Sie eine gültige Homepage ein.";

  // Registration mail.
  $lang[registration_mail_subject] = "Deine Registrierung bei $cfg[site_title]";
  $lang[registration_mail_body]    = "Hallo [FIRSTNAME] [LASTNAME]!\n"
                                   . "\n"
                                   . "Vielen Dank für deine Registrierung bei"
                                   . " $cfg[site_title]. Dein Account-Name ist"
                                   . " \"[LOGIN]\".\n"
                                   . "\n"
                                   . "Bitte bestätige deine Email-Adresse durch"
                                   . " Klick auf den folgenden Link:"
                                   . "\n"
                                   . "[URL]\n";

  // Change password.
  $lang[change_password_title] = "Passwort ändern";
  $lang[change_password_text]  = "Bitte vergeben sie ihr persönliches Login-Passwort.";
  $lang[change_password_btn]   = "Passwort ändern";
  $lang[change_password]       = "Passwort:";
  $lang[change_password2]      = "Wiederholung:";
  $lang[passwordsdonotmatch]   = "Fehler: Die Passworte stimmen nicht überein.";
  $lang[passwordtooshort]      = "Bitte ein Passwort mit mindestens"
                               . " $cfg[min_passwordlength] Zeichen wählen.";
  $lang[passwordtoolong]       = "Bitte ein Passwort mit maximal"
                               . " $cfg[max_passwordlength] Zeichen wählen.";
  $lang[password_changed]      = "Ihr Passwort wurde geändert. Sie können"
                               . " sich nun einloggen.";

  // Reset password mail.
  $lang[password_mail_sent] = "Eine Mail mit Anleitung zum Zurücksetzen"
                            . " wurde an Ihre Email-Adresse versandt.";
  $lang[reset_mail_subject] = "Dein Passwort bei $cfg[site_title]";
  $lang[reset_mail_body]    = "Hallo [FIRSTNAME] [LASTNAME],\n"
                            . "\n"
                            . "Wir haben eine Anfrage zur Zurücksetzung des"
                            . " Passwortes für deinen Account \"[LOGIN]\""
                            . " erhalten.\n"
                            . "Um das Passwort jetzt zurückzusetzen klicke"
                            . " bitte den Link unten an."
                            . " Falls du keine Anfrage zur Zurücksetzung"
                            . " gestellt hast, ignoriere bitte diese Mail."
                            . "\n"
                            . "\n"
                            . "[URL]\n";

  // Login
  $lang[login_text]       = "Hinweis: Um einzuloggen muss Ihr Browser Cookies"
                          . " akzeptieren.";
  $lang[resetpasswd_title] = "Zugangsdaten zurücksetzen";
  $lang[resetpasswd]      = "Zurücksetzen";
  $lang[nosuchmail]       = "Die angegebene Email-Adresse ist unbekannt.";
  $lang[passwdforgotten]  = "Zugangsdaten vergessen";
  $lang[username]         = "User-Name:";
  $lang[passwd]           = "Passwort:";
  $lang[rememberpasswd]   = "Passwort merken";
  $lang[remembpasswdlong] = "Klicken Sie hier, wenn wir ein dauerhaftes Cookie "
                          . "setzen sollen.";
  $lang[login]            = "Anmelden";
  $lang[logout]           = "Ausloggen";
  $lang[loginfailed]      = "Login fehlgeschlagen.";
  $lang[loginunconfirmed] = "Der Account ist noch nicht freigeschaltet.";
  $lang[loginlocked]      = "Der Account ist gesperrt.";
  $lang[resendconfirm]    = "Bestätigungsmail erneut versenden";
  $lang[logintooshort]    = "Der von ihnen gewählte Benutzername ist zu kurz."
                          . " Bitte mindestens $cfg[min_usernamelength] Zeichen"
                          . " eingeben.";
  $lang[logintoolong]     = "Der von ihnen gewählte Benutzername ist zu lang."
                          . " Bitte maximal $cfg[max_usernamelength] Zeichen"
                          . " eingeben.";
  $lang[logininvalidchars]    = "Der von ihnen gewählte Name enthält ungültige"
                              . " Zeichen. Bitte nur"
                              . " Buchstaben, Zahlen, oder Leerzeichen,"
                              . " keine Umlaute oder Sonderzeichen eingeben.";
  $lang[usernamenotavailable] = "Der Benutzername gehört bereits einem "
                              . " registrierten User.";
  $lang[register_mail_exists] = "Die angegebene Email-Adresse gehört bereits"
                              . " einem registrierten User.";

  // User posting list.
  $lang[mypostings]          = "Meine Postings";
  $lang[postings_of]         = "Postings von [NAME]";

  // User profile and personal data.
  $lang[myprofile]          = "Mein Profil";
  $lang[profile]            = "Profil von [NAME]";
  $lang[account_mydata]     = "Meine persönlichen Daten";
  $lang[account_data]       = "Persönliche Daten von [NAME]";
  $lang[account_id]         = "User-ID:";
  $lang[account_status]     = "Account-Status:";
  $lang[account_created]    = "Mitglied seit:";
  $lang[account_postings]   = "Forenbeiträge:";
  $lang[account_name]       = "Username:";
  $lang[account_firstname]  = "Vorname:";
  $lang[account_lastname]   = "Nachname:";
  $lang[account_mail]       = "Email:";
  $lang[account_publicmail] = "Email-Adresse öffentlich anzeigen";
  $lang[account_hiddenmail] = "Nicht öffentlich";
  $lang[account_homepage]   = "Homepage:";
  $lang[account_im]         = "Instant Messenger:";
  $lang[account_signature]  = "Signatur:";
  $lang[account_password]   = "Passwort:";
  $lang[account_password2]  = "Wiederholung:";
  $lang[account_save]       = "Daten speichern";
  $lang[account_saved]      = "Die Daten wurden gespeichert.";
  $lang[account_emptyfield] = "Nicht angegeben";
  $lang[account_edit]       = "[Bearbeiten]";
  $lang[signature_too_long] = "Die Signatur hat zu viele Zeichen.";
  $lang[signature_lines]    = "Die Signatur hat zu viele Zeilen.";

  $lang[USER_STATUS_DELETED]     = "Gelöscht";
  $lang[USER_STATUS_ACTIVE]      = "Aktiv";
  $lang[USER_STATUS_UNCONFIRMED] = "Nicht freigeschaltet";
  $lang[USER_STATUS_BLOCKED]     = "Gesperrt";

  // Group profile.
  $lang[group_profile]         = "Gruppenprofil von [NAME]";
  $lang[group_id]              = "Gruppen-ID:";
  $lang[group_name]            = "Benutzergruppe:";
  $lang[group_special]         = "Spezialfunktionen:";
  $lang[group_is_special]      = "Ja";
  $lang[group_is_not_special]  = "Keine";
  $lang[group_icon]            = "Icon:";
  $lang[group_status]          = "Status:";
  $lang[group_status_active]   = "Aktiv";
  $lang[group_status_inactive] = "Inaktiv";
  $lang[group_created]         = "Erstellt am:";
  $lang[group_edit]            = "[Bearbeiten]";

  // Group editor.
  $lang[group_editor]          = "Daten der Gruppe [NAME]";
  $lang[group_permissions]     = "Berechtigungen";
  $lang[group_may]             = "'[ACTION]' gewähren";
  $lang[group_save]            = "Daten speichern";
  $lang[group_saved]           = "Die Daten wurden gespeichert.";

  // Top user list.
  $lang[top_users]   = "Top 20";
  $lang[alltime_top] = "Top 20";
  $lang[weekly_top]  = "Top 20 der letzten 7 Tage";
  $lang[n_postings]  = "Postings";

  // Statistics.
  $lang[statistics]          = "Statistik";
  $lang[statistics_title]    = "Postings und Traffic der letzten [DAYS] Tage";
  $lang[statistics_postings] = "Postings";
  $lang[statistics_traffic]  = "Traffic";

  // Search.
  $lang[search_no_posting] = "(Keine Beiträge)";
  $lang[search_no_users]   = "(Keine Benutzer gefunden)";
  $lang[search_forum]      = "Suchen";
  $lang[search_quick]      = "Suche:";
  $lang[search_title]      = "Forensuche";
  $lang[msg_search_start]  = "Beiträge suchen";
  $lang[user_search_start] = "Benutzer suchen";
  $lang[search_results]    = "[RESULTS] Suchergebnisse gefunden.";
  $lang[search_examples]   = "Beispiele:";
  $lang[search_hint]       = "äpfel AND \"birnen\"\n"
                           . "NOT user:\"der da\" AND (text:bananen OR subject:frucht)\n"
                           . "ban?nen AND NOT subject:wildcard*matching";

  // Polls.
  $lang[poll]                  = "Umfrage: [TITLE]";
  $lang[poll_create]           = "Umfrage starten";
  $lang[poll_title]            = "Umfragetitel";
  $lang[poll_submit]           = "Umfrage abschicken";
  $lang[poll_allow_multiple]   = "Mehrfachauswahl erlauben.";
  $lang[poll_option]           = "Option [NUMBER]:";
  $lang[poll_add_row]          = "Weitere Option hinzufügen";
  $lang[poll_title_missing]    = "Bitte einen Titel angeben.";
  $lang[poll_title_too_long]   = "Bitte einen kürzeren Titel wählen.";
  $lang[poll_too_few_options]  = "Bitte weitere Optionen ausfüllen.";
  $lang[poll_too_many_options] = "Zu viele Optionen.";
  $lang[poll_option_too_long]  = "Eine der Optionen ist zu lang.";
  $lang[poll_duplicate_option] = "Die Umfrage hat doppelte Optionen.";
  $lang[poll_anonymous]        = "Um Abstimmen zu können bitte einloggen.";
  $lang[poll_vote]             = "Stimme abgeben";
  $lang[poll_vote_accepted]    = "Ihre Stimmabgabe wurde gewertet.";
  $lang[poll_limit_reached]    = "Sie haben ihre Umfragen bereits verbraucht.";
  $lang[poll_to_result]        = "Zum Umfrageergebnis";

  // Moderation log.
  $lang[modlog]                = 'Moderations-Log';
  $lang[modlog_reason]         = 'Grund: [REASON]';
  $lang[modlog_lock_posting]   = '[MODERATOR_LINK] hat ein'
                               . ' <a href="[POSTING_URL]">Posting</a>'
                               . ' von [USER_LINK] gesperrt.';
  $lang[modlog_unlock_posting] = '[MODERATOR_LINK] hat das Posting'
                               . ' "[POSTING_LINK]"'
                               . ' von [USER_LINK] wieder freigegeben.';
  $lang[modlog_set_sticky]     = '[MODERATOR_LINK] hat das Posting'
                               . ' "[POSTING_LINK]" von [USER_LINK]'
                               . ' festgepinnt.';
  $lang[modlog_remove_sticky]  = '[MODERATOR_LINK] hat den Pin vom'
                               . ' Posting "[POSTING_LINK]" von [USER_LINK]'
                               . ' wieder entfernt.';

  // Locking postings.
  $lang[posting_lock_title]     = 'Beitrag sperren';
  $lang[posting_lock]           = 'Beitrag sperren';
  $lang[posting_unlock]         = 'Beitrag freigeben';
  $lang[posting_lock_username]  = 'Username:';
  $lang[posting_lock_subject]   = 'Betreff:';
  $lang[posting_lock_reason]    = 'Begründung:';
  $lang[posting_lock_no_reason] = 'Bitte eine Begründung angeben.';

  // Linkify plugin.
  $lang[show_videos] = 'Videos einblenden';
  $lang[hide_videos] = 'Videos ausblenden';
?>
