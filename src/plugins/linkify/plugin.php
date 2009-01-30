<?php
/*
Plugin:      Linkify
Version:     0.1
Author:      Samuel Abels
Description: Converts URLs in messages into hyperlinks.
*/


function linkify_init($forum) {
  $eventbus = $forum->eventbus();
  $eventbus->signal_connect('on_message_read_print',    'linkify_on_read');
  $eventbus->signal_connect('on_message_preview_print', 'linkify_on_preview');

  // Register our extra actions.
  $forum->register_action('linkify_hide_videos', 'linkify_on_hide_videos');
  $forum->register_action('linkify_show_videos', 'linkify_on_show_videos');
}


function linkify_on_read($forum, $message) {
  $message->signal_connect('on_format_after_html', 'linkify_on_format');

  if ($_COOKIE['linkify_show_videos']) {
    $url = new FreechURL('', _('Hide Videos'));
    $url->set_var('action', 'linkify_hide_videos');
  }
  else {
    $url = new FreechURL('', _('Show Videos'));
    $url->set_var('action', 'linkify_show_videos');
  }
  $url->set_var('refer_to', $_SERVER['REQUEST_URI']);
  $forum->links('footer')->add_link($url);
}


function linkify_on_preview($forum, $message) {
  $message->signal_connect('on_format_after_html', 'linkify_on_format');
}


function linkify_on_show_videos($forum) {
  $forum->set_cookie('linkify_show_videos', TRUE);
  $forum->refer_to($_GET['refer_to']);
}


function linkify_on_hide_videos($forum) {
  $forum->set_cookie('linkify_show_videos', FALSE);
  $forum->refer_to($_GET['refer_to']);
}


function linkify_try_youtube_url($url, $in_quotes) {
  if ($_GET['preview'] or $_POST['preview'])
    return '';
  if (!$_COOKIE['linkify_show_videos'])
    return '';
  if ($in_quotes)
    return '';
  if (!preg_match('~http://(?:\w+\.)?youtube.com/watch\?v=([\w\_\-]+)~i',
                  $url,
                  $matches))
    return '';
  $video_id = $matches[1];
  return "<!-- plugin linkify -->
  <div style='margin: 10px; width: 650px; text-align: center' class='video'>
    <object width='615'
            height='494'
            type='application/x-shockwave-flash'
            data='http://www.youtube.com/v/$video_id&amp;hl=en&amp;fs=1&amp;border=1'>
      <param name='movie'
             value='http://www.youtube.com/v/$video_id&amp;hl=en&amp;fs=1' />
      <param name='allowFullScreen' value='true' />
      <param name='allowscriptaccess' value='always' />
      <img class='noflash'
           src='#'
           alt='flash plug-in is missing or disabled'
           title='no flash plug-in found' />
    </object>
    <br/>
    <a href='$url'>$url</a>
  </div>";
}


function linkify_url2link($match) {
  $prefix    = $match[1].$match[2];
  $url       = $match[3];
  $quote_pfx = "<span class='quote'"; //NOTE: string was used in message.class.php
  $in_quotes = substr($match[2], 0, strlen($quote_pfx)) == $quote_pfx;
  if ($newurl = linkify_try_youtube_url($url, $in_quotes))
    return $prefix.$newurl;
  return $prefix."<a class='extern' href='$url'>$url</a>";
}


function linkify_on_format($message) {
  // Split body and signature.
  $body      = $message->get_body_html();
  $signature = '';
  if (preg_match('/^(.*)([\r\n]--.*)$/s', $body, $matches)) {
    $body      = $matches[1];
    $signature = $matches[2];
  }

  // Convert URLs to links.
  $body = preg_replace_callback('~'
                              . '(^|[\r\n])'     // Line start.
                              . '([^\r\n]*?)'  // Line start to URL start.
                              . '('.cfg('autolink_pattern').')'
                              . '~',
                                'linkify_url2link',
                                $body);

  // Done. Also reattach the signature.
  $message->set_body_html($body.$signature);
}
?>
