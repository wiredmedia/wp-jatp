<?php
namespace Wired;
/**
 *
 *
 * @class 		Wired\time_since
 * @category	Class
 * @author		Wired Media ( taken from WordPress.com 'wpcom_time_since()' few tiny changes )
 * @param     int $past expects unix time stamp
 * @param     bool $verbose if true output has up to two time units
 * @License:  GPLv2
 */
class Time_Since {

  var $past;
  var $verbose;
  var $time_chunks;
	/**
	 * Constructor
	 */
	function __construct($past, $verbose = false ) {
	  $this->verbose = $verbose;
	  $this->past = $past;
	  // array of time period chunks
    $this->time_chunks = array(
      array(60 * 60 * 24 * 365 , 'year'),
      array(60 * 60 * 24 * 30 , 'month'),
      array(60 * 60 * 24 * 7, 'week'),
      array(60 * 60 * 24 , 'day'),
      array(60 * 60 , 'hour'),
      array(60 , 'minute'),
    );
	}

	function output_time(){
	  $today = time();
    $since = $today - $this->past;

    for ($i = 0, $j = count( $this->time_chunks ); $i < $j; $i++) {
      $seconds = $this->time_chunks[$i][0];
      $name = $this->time_chunks[$i][1];
      if (($count = floor($since / $seconds)) != 0){
        break;
      }
    }

    $print = ($count == 1) ? '1 '.$name : "$count {$name}s";

    /* if there is still another time unit to display and $this->verbose flag set try to ad another time unit */
    if ($i + 1 < $j && $this->verbose ) {
      $seconds2 = $this->time_chunks[$i + 1][0];
      $name2 = $this->time_chunks[$i + 1][1];
      // add time unit if it's greater than 0
      if ( (($count2 = floor(($since - ($seconds * $count)) / $seconds2)) != 0) ){
        $print .= ($count2 == 1) ? ', 1 '.$name2 : ", $count2 {$name2}s";
      }
    }
    return $print;

	}// END: print

}// END: time_since