<?php
  /*
  Freech.
  Copyright (C) 2003 Samuel Abels, <http://debain.org>
                     Robert Weidlich, <tefinch xenim de>

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
  $lang[countrycode]      = "de";
  $lang[dateformat]       = "y-m-d H:i";
  $lang[index]            = "Seite";
  $lang[forum]            = "Forum";
  $lang[forum_long]       = "Forum (bisher [MESSAGES] Beiträge,"
                          . " [NEWMESSAGES] neu, zur Zeit [ONLINEUSERS]"
                          . " User online)";
  $lang[unfoldall]        = "Alles aufklappen";
  $lang[foldall]          = "Alles zuklappen";
  $lang[prev]             = "Ältere";
  $lang[next]             = "Neuere";
  $lang[prev_symbol]      = "<<";
  $lang[next_symbol]      = ">>";
  $lang[entry]            = "Beitrag";
  $lang[thread]           = "Thread";
  $lang[threadview]       = "In Thread-Darstellung anzeigen";
  $lang[plainview]        = "In Eingangsreihenfolge anzeigen";
  $lang[editmessage]      = "Bearbeiten";
  $lang[writeanswer]      = "Beantworten";
  $lang[writemessage]     = "Neues Thema";
  $lang[entryindex]       = "Beitragsübersicht";
  $lang[hidethread]       = "Thread-Anzeige ausblenden";
  $lang[showthread]       = "Thread-Anzeige einblenden";
  $lang[postedby]         = "Beitrag von [USER]";
  $lang[lastupdated]      = "(Editiert am [TIME])";
  $lang[moderator]        = "Moderator";
  $lang[anonymous]        = "Anonymer User";
  $lang[registered]       = "Registrierter User";
  $lang[deleted]          = "Gelöschter User";
  $lang[ip_hash]          = "(Salziger IP-Hash: [HASH])";

  // Message administration.
  $lang[message_lock]     = "Beitrag sperren";
  $lang[message_unlock]   = "Beitrag freigeben";
  $lang[message_stick]    = "Pin setzen";
  $lang[message_unstick]  = "Pin entfernen";
  $lang[blockedtitle]     = "Gesperrter Beitrag";
  $lang[blockedentry]     = "Der an dieser Stelle platzierte Kommentar enthielt "
                           ."eine rechtswidrige Äußerung oder verletzte "
                           ."grob die Nutzungsbedingungen für unsere "
                           . "Diskussionsforen. Er ist daher gelöscht worden.";
  
  $lang[noentrytitle]     = "Dieser Beitrag ist nicht vorhanden";
  $lang[noentrybody]      = "Der von ihnen angeforderte Eintrag existiert in diesem"
                           ." Forum nicht";
  
  // Compose
  $lang[writeamessage]    = "Neuen Beitrag für das Forum schreiben";
  $lang[writeananswer]    = "Antwort für das Forum schreiben";
  $lang[required]         = "(bitte unbedingt ausfüllen)";
  $lang[name]             = "Name";
  $lang[msgtitle]         = "Überschrift";
  $lang[msgbody]          = "Beitrag";
  $lang[msgimage]         = "Bild";
  $lang[preview]          = "Vorschau";
  $lang[quote]            = "Zitat einfügen";
  $lang[answer]           = "Re: ";
  $lang[wrote]            = "[USER] schrieb am [TIME]";
  
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
  $lang[register_welcome]    = "Herzlich willkommen!\n"
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
  $lang[havetoregister]   = "Um unsere personalisierten Dienste nutzen zu "
                          . "können, müssen Sie sich zunächst "
                          . "<a href='registration/'>registrieren</a>. Erst "
                          . "danach können Sie sich hier einloggen.";
  $lang[resetpasswd_title] = "Zugangsdaten zurücksetzen";
  $lang[resetpasswd]      = "Zurücksetzen";
  $lang[nosuchmail]       = "Die angegebene Email-Adresse ist unbekannt.";
  $lang[passwdforgotten]  = "Zugangsdaten vergessen";
  $lang[enteruserdata]    = "User-Daten eingeben";
  $lang[username]         = "User-Name";
  $lang[passwd]           = "Passwort";
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
  $lang[account_messages]   = "Forenbeiträge:";
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
  $lang[group_status]          = "Status:";
  $lang[group_status_active]   = "Aktiv";
  $lang[group_status_inactive] = "Inaktiv";
  $lang[group_created]         = "Erstellt am:";
  $lang[group_edit]            = "[Bearbeiten]";

  // Top user list.
  $lang[top_users]   = "User-Statistik";
  $lang[alltime_top] = "Top 20";
  $lang[weekly_top]  = "Top 20 der letzten 7 Tage";
  $lang[n_postings]  = "Postings";

  // Search.
  $lang[search_no_message] = "(Keine Beiträge)";
  $lang[search_no_users]   = "(Keine Benutzer gefunden)";
  $lang[search_forum]      = "Suchen";
  $lang[search_title]      = "Forensuche";
  $lang[msg_search_start]  = "Beiträge suchen";
  $lang[user_search_start] = "Benutzer suchen";
  $lang[search_results]    = "[RESULTS] Suchergebnisse gefunden.";
  $lang[search_examples]   = "Beispiele:";
  $lang[search_hint]       = "äpfel AND \"birnen\"<br/>"
                           . "NOT user:\"der da\" AND (text:bananen OR subject:frucht)<br/>"
                           . "ban?nen AND NOT subject:wildcard*matching";
?>
