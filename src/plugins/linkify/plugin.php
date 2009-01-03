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


function linkify_try_youtube_url($url) {
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
              <a href='$url'><font size='-1'>$url</font></a>
            </td>
            </tr>
          </table>";
}


function linkify_url2link($match) {
  $url = $match[0];
  if ($newurl = linkify_try_youtube_url($url))
    return $newurl;
  return "<a href=\"$url\">$url</a>";
}


function linkify_on_format($message) {
  if (!cfg('autolink_urls'))
    return;
  $body = preg_replace_callback('~' . cfg('autolink_pattern') . '~',
                                'linkify_url2link',
                                $message->get_body_html());
  $message->set_body_html($body);
}
?>
