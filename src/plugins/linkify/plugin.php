<?php
/*
Plugin:      Linkify
Version:     0.1
Author:      Samuel Abels
Description: Converts URLs in messages into hyperlinks.
Constructor: linkify_init
*/


function linkify_init($forum) {
  $eventbus = $forum->get_eventbus();
  $eventbus->signal_connect('on_message_read_print',    'linkify_on_read');
  $eventbus->signal_connect('on_message_preview_print', 'linkify_on_preview');
}


function linkify_on_read($forum, $message) {
  $message->signal_connect('on_format_after_html', 'linkify_on_format');
}


function linkify_on_preview($forum, $message) {
  $message->signal_connect('on_format_after_html', 'linkify_on_format');
}


function linkify_try_youtube_url($url, $in_quotes) {
  if (!cfg('autoembed_media_urls'))
    return '';
  if ($in_quotes)
    return '';
  if (!preg_match('~http://(?:\w+\.)?youtube.com/watch\?v=([\w\_\-]+)~i',
                  $url,
                  $matches))
    return '';
  $video_id = $matches[1];
  return "<table width='650'>
            <tr>
            <td align='center'>
              <object width='615' height='494'>
                <param name='movie' value='http://www.youtube.com/v/$video_id&hl=en&fs=1'></param>
                <param name='allowFullScreen' value='true'></param>
                <param name='allowscriptaccess' value='always'></param>
                <embed src='http://www.youtube.com/v/$video_id&hl=en&fs=1&border=1'
                       type='application/x-shockwave-flash'
                       allowscriptaccess='always'
                       allowfullscreen='true'
                       width='615' height='494'></embed>
              </object>
              <br/>
              <a href='$url'>$url</a>
            </td>
            </tr>
          </table>";
}


function linkify_url2link($match) {
  $prefix    = $match[1].$match[2];
  $url       = $match[3];
  $quote_pfx = '<font color';
  $in_quotes = substr($match[2], 0, strlen($quote_pfx)) == $quote_pfx;
  if ($newurl = linkify_try_youtube_url($url, $in_quotes))
    return $prefix.$newurl;
  return $prefix."<a href=\"$url\">$url</a>";
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
