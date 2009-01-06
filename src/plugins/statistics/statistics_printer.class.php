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
  class StatisticsPrinter extends PrinterBase {
    // Read a list of date/value pairs from a CSV file into an array.
    function _read_csv($file) {
      $results = array();
      $fp      = fopen($file, 'r');

      // Read at most the last 15000 bytes of the file.
      if (filesize($file) > 15000) {
        fseek($fp, -15000, SEEK_END);
        fgets($fp); // Drop the first line as it may be incomplete.
      }

      // Parse all lines into an array.
      while ($line = fgetcsv($fp))
        $results[$line[0]] = (int)$line[1];
      fclose($fp);
      return $results;
    }


    function show() {
      $days       = cfg('statistics_timespan');
      $resolution = 60*60*24;
      $end        = strtotime(strftime('%Y-%m-%d')) + 60*60*24;
      $start      = $end - 60*60*24*$days;
      $results    = array();

      // Read CSV data, if any.
      if (!cfg_is('statistics_traffic_data', ''))
        $traffic = $this->_read_csv(cfg('statistics_traffic_data'));
      if (!cfg_is('statistics_extra_data', ''))
        $postings2 = $this->_read_csv(cfg('statistics_extra_data'));

      // Collect the number of postings per day within the specified period.
      for ($day_end = $start; $day_end <= $end; $day_end += $resolution) {
        $day_start         = $day_end - $resolution;
        $date              = strftime('%Y-%m-%d', $day_start);
        $result[pos]       = (int)($day_end - $start) / $resolution;
        $result[postings]  = $this->forumdb->get_n_postings(NULL,
                                                            $day_start,
                                                            $day_end);
        $result[postings2] = (int)$postings2[$date];
        $result[traffic]   = (int)$traffic[$date] / 1000000;
        array_push($results, $result);
      }

      $this->clear_all_assign();
      $this->assign_by_ref('plugin_dir',      dirname(__FILE__));
      $this->assign_by_ref('days',            $days);
      $this->assign_by_ref('resolution',      $resolution);
      $this->assign_by_ref('data',            $results);
      $this->assign_by_ref('show_traffic',    $traffic   ? TRUE : FALSE);
      $this->assign_by_ref('show_postings2',  $postings2 ? TRUE : FALSE);
      $this->assign_by_ref('postings2_label', cfg('statistics_extra_label'));
      $this->render('statistics.tmpl');
      $this->parent->_set_title(lang('statistics'));
    }
  }
?>
