<?php
  /*
  Freech.
  Copyright (C) 2003 Samuel Abels

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
  class FooterController extends Controller {
    function show($_forum_id) {
      $url = new FreechURL('http://code.google.com/p/freech/',
                           'Powered by Freech '.FREECH_VERSION);
      $this->api->links('footer')->add_link($url, 100);

      if (cfg('rss_enabled')) {
        $url = new FreechURL('rss.php', _('RSS feed'));
        $url->set_var('forum_id', (int)$_forum_id);

        $html = '<span id="rss">'
              . '<a href="' . $url->get_string(TRUE) . '">'
              . '<img src="themes/' . cfg('theme') . '/img/rss.png" alt="RSS 2.0" />'
              . '</a>'
              . '&nbsp;' . $url->get_html()
              . '</span>';
        $this->api->links('footer')->add_html($html, 200);
      }

      if (cfg('goto_top_enabled')) {
        $top_link = "<a id='goto_top' href='#top'>"
                  . esc(_('Go to top')) . "</a>";
        $this->api->links('footer')->add_html($top_link, 300);
      }

      // Render the resulting template.
      $this->clear_all_assign();
      $this->assign_by_ref('view_links',   $this->api->links('view'));
      $this->assign_by_ref('footer_links', $this->api->links('footer'));
      $this->render_php('footer.php.tmpl');
    }
  }
?>
