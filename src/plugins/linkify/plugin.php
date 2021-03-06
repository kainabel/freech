<?php
/*
Plugin:      Linkify
Version:     0.1
Author:      Samuel Abels
Description: Converts URLs in messages into hyperlinks.
*/


function linkify_init(&$api) {
  $eventbus = $api->eventbus();
  $eventbus->signal_connect('on_message_read_print',    'linkify_on_read');
  $eventbus->signal_connect('on_message_preview_print', 'linkify_on_preview');

  // Register our extra actions.
  $api->register_action('linkify_hide_videos', 'linkify_on_hide_videos');
  $api->register_action('linkify_show_videos', 'linkify_on_show_videos');
}


function linkify_on_read(&$api, &$message) {
  $message->signal_connect('on_format_after_html', 'linkify_on_format');

  // Make sure that the link is not added twice on pages where
  // this method is called multiple times.
  foreach ($api->links('view')->get_items() as $item) {
    if (!$item->is_link())
      continue;
    if ($item->get_url()->get_var('action') == 'linkify_hide_videos'
     || $item->get_url()->get_var('action') == 'linkify_show_videos')
      return;
  }

  // Add a link to the bottom of the page.
  if ($_COOKIE['linkify_show_videos']) {
    $url = new FreechURL('', _('Hide Videos'));
    $url->set_var('action', 'linkify_hide_videos');
  }
  else {
    $url = new FreechURL('', _('Show Videos'));
    $url->set_var('action', 'linkify_show_videos');
  }
  $url->set_var('refer_to', $_SERVER['REQUEST_URI']);
  $api->links('view')->add_link($url);
}


function linkify_on_preview(&$api, &$message) {
  $message->signal_connect('on_format_after_html', 'linkify_on_format');
}


function linkify_on_show_videos(&$api) {
  $api->set_cookie('linkify_show_videos', TRUE);
  $api->refer_to($_GET['refer_to']);
}


function linkify_on_hide_videos(&$api) {
  $api->set_cookie('linkify_show_videos', FALSE);
  $api->refer_to($_GET['refer_to']);
}

// RegEx pattern ripped from: http://goo.gl/Yv62qW
function linkify_try_youtube_url($url, $in_quotes) {
  if ($_GET['preview'] or $_POST['preview'])
    return '';
  if (!$_COOKIE['linkify_show_videos'])
    return '';
  if ($in_quotes)
    return '';
  $pattern = '~(?P<scheme>https?)(?::\/\/|)(?:www.|)(?:youtu\.be\/|youtube\.com(?:\/embed\/|\/v\/|\/watch\?v=|\/ytscreeningroom\?v=|\/feeds\/api\/videos\/|\/user\S*[^\w\-\s]|\S*[^\w\-\s]))(?P<video_id>[\w\-]{11})[a-z0-9;:@?&%=+\/\$_.-]*~i';
  if (!preg_match($pattern, $url, $matches)) {
    return '';
  }
  $url_short = $matches['scheme']."://www.youtube.com/v/".$matches['video_id'];
  $video_id  = $url_short.'&amp;hl=en&amp;fs=1&amp;border=0&amp;rel=0&amp;showsearch=0';
  return "<!-- plugin linkify -->
  <div class='video'>
    <object type='application/x-shockwave-flash' data='$video_id'>
      <param name='movie' value='$video_id' />
      <param name='allowFullScreen' value='true' />
      <param name='allowscriptaccess' value='always' />
      <img class='noflash' title='no flash plug-in found' src='#'
           alt='flash plug-in is missing or disabled' />
    </object>
    <br/>
    <a class='extern' href='$url' title='$url'>$url_short</a>
  </div>";
}


function linkify_url2link($match) {
  $prefix    = $match[1].$match[2];
  $url       = $match[3];
  $whitelist = cfg('autolink_whitelist', '');
  // matching against a pipe separated whitelist of domain names
  // e.g: $cfg['autolink_whitelist'] = 'localhost|domian.xz|foo.baz|youtube.com|youtu.be';
  if (!empty($whitelist)) {
      if (!preg_match('~'.$whitelist.'~i',$url,$arr)) {
          return $prefix.$url;
      }
  }
  $quote_pfx = "<span class='quote'"; //NOTE: string was used in message.class.php
  $in_quotes = substr($match[2], 0, strlen($quote_pfx)) == $quote_pfx;
  if ($newurl = linkify_try_youtube_url($url, $in_quotes))
    return $prefix.$newurl;
  return $prefix."<a class='extern' href='$url'>$url</a>";
}


function linkify_on_format(&$message) {
  // Convert URLs to links.
  $body = preg_replace_callback('~'
                              . '(^|[\r\n])'   // Line start.
                              . '([^\r\n]*?)'  // Line start to URL start.
                              . '('.cfg('autolink_pattern').')'
                              . '~',
                                'linkify_url2link',
                                $message->get_body_html());
  $message->set_body_html($body);
}
?>
