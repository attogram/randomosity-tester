<?php

require_once( __DIR__.'/random.php' );
$random = new random();

$display_header = (isset($_GET['h']) && $_GET['h'] == '0') ? TRUE : FALSE;
function header_urlvar($prefix) {
	global $display_header;
	if( !$display_header ) {
		return;
	}
	return $prefix . 'h=0';
}

?><!doctype html>
<html><head><title>SQLite ORDER BY RANDOM() Tester</title>
<meta charset="utf-8" />
<meta name="viewport" content="initial-scale=1" />
<style>
body {
   background-color:white; 
   color:black; 
   margin:0px 20px 20px 20px; 
   font-family:sans-serif,helvetica,arial;
}
h1 { font-size:115%; margin:5px 0px 5px 0px; padding:0px; display:inline-block;}
h2 { font-size:95%; font-weight:normal; margin:0px; padding:0px; }
a { text-decoration:none; color:darkblue; background-color:#e8edd3; }
a:visited { color:darkblue; background-color:#e8edd3; }
a:hover { background-color:yellow; color:black; }
ul { margin:0px; }
.notice {
   background-color:lightyellow;
   font-size:90%;
   border:1px solid grey;
   margin:0px 0px 4px 0px;
   padding:4px;
   display:inline-block;
}
.pre {
	white-space:pre; 
	font-family:monospace;
}
.chart {
	margin:0px auto;
	width:100%;
	font-family:monospace;
	font-weight:bold;
	font-size:80%;
}
.freq {
	display:table-cell;
	float:left;
	width:50%;
	text-align:right;
	margin:1px auto;
	overflow:visible;
	background-color:#fcfcfc;
}
.row {
	display:table-cell;
	float:right;
	width:50%;
	text-align:left;
	margin:1px auto;
	background-color:#fcfcfc;
}
.data {
	margin:0px;
	padding:0px;
	display:inline-block;
}
.freqdata {
	background-color:lightblue;
}
.rowdata {
	background-color:lightsalmon;
}
.header {
	font-size:125%;
}
.freqheader {
	background-color:darkblue;
	color:white;
}
.rowheader {
	background-color:darkred;
	color:white;
}
footer {
	font-size:80%;
}
</style>
</head><body><a name="top"></a>
<?php $random->display_header(); ?>
<div style="float:right; margin-top:5px;">
 <a href="./<?php print header_urlvar('?'); ?>">&nbsp;Refresh&nbsp;</a>
 <a href="./<?php print header_urlvar('?'); ?>#about">&nbsp;About Tester&nbsp;</a>
</div>
<h1>SQLite ORDER BY RANDOM() Tester</h1>
<?php
if( isset($_GET['run']) ) {
   $random->run = (int)$_GET['run'];
   if( !$random->run || !is_int($random->run) ) { $random->run = 1; }
   $random->add_more_random( $random->run );
}

if( isset($_GET['restart']) ) {
    $restart = (int)$_GET['restart'];
    if( !$restart || !is_int($restart) || $restart > $random->max_table_size || $restart < 1 ) {
        $restart = $random->default_table_size;
    }
    $random->delete_test_table();
    $random->create_test_table( $restart );
}

?>
<div class="pre"><?php 

$distribution_chart = $random->display_distribution(); // also gets dist info
$info = $random->get_test_table_info();

$pad_size = 6;
print '<span style="font-size:130%; font-weight:bold;">'
. '<a href="./?run=1' . header_urlvar('&amp;') . '">+1</a>'
. ' <a href="./?run=10' . header_urlvar('&amp;') . '">+10</a>'
. ' <a href="./?run=100' . header_urlvar('&amp;') . '">+100</a>'
. ' <a href="./?run=1000' . header_urlvar('&amp;') . '">+1K</a>'
. ' <a href="./?run=10000' . header_urlvar('&amp;') . '">+10K</a>'
. ' <a href="./?run=999999999' . header_urlvar('&amp;') . '">+MAX</a>'
. '</span>'
. '<br /><b>' . number_format(@$info['class_size']) . '</b> rows, '
	. '<b>' . number_format(@$info['data_sum']) . '</b> data points, '
	. '<b>' . number_format($random->frequencies_count) . '</b> groups'
	. '<br />           High   / Low    / Range  / Average<br />'
	. 'Frequency: <b>' . str_pad(number_format($random->highest_frequency), $pad_size, ' ')
	. '</b> / <b>' . str_pad(number_format($random->lowest_frequency), $pad_size, ' ')
	. '</b> / <b>' . str_pad(number_format($random->highest_frequency - $random->lowest_frequency), $pad_size, ' ')
	. '</b> / <b>' . str_pad(number_format($random->frequencies_average), $pad_size, ' ') . '</b>'
	. '<br />     Rows:<b> ' . str_pad(number_format($random->highest_rows), $pad_size, ' ')
	. '</b> / <b>' . str_pad(number_format($random->lowest_rows), $pad_size, ' ')
	. '</b> / <b>' . str_pad(number_format($random->highest_rows - $random->lowest_rows), $pad_size, ' ')
	. '</b> / <b>' . str_pad(number_format($random->rows_average), $pad_size, ' ') . '</b>'
. '</div>'
. $distribution_chart
. '<br clear="all" />'
;


$get_data  = isset($random->timer['get_data'])  ? number_format($random->timer['get_data'],  6) : '0';
$save_data = isset($random->timer['save_data']) ? number_format($random->timer['save_data'], 6) : '0';
$run = isset($radom->run) ? $random->run : '0';
?>
<div class="pre">Test runs: <?php print number_format($random->run); ?> 
Avg run  : <?php 
	if( isset($random->run) && $random->run > 0 ) {
		print number_format( ($get_data / $random->run), 6);
	} else {
		print '0';
	}
?> seconds
Test time: <?php print $get_data; ?> seconds
Data Save: <?php print $save_data; ?> seconds
</div>

<div class="pre">
Restart test with: 
<?php
print '<a href="?restart=1' . header_urlvar('&amp;') . '"> 1 </a>'
. ' <a href="?restart=2' . header_urlvar('&amp;') . '"> 2 </a>'
. ' <a href="?restart=3' . header_urlvar('&amp;') . '"> 3 </a>'
. ' <a href="?restart=4' . header_urlvar('&amp;') . '"> 4 </a>'
. ' <a href="?restart=5' . header_urlvar('&amp;') . '"> 5 </a>'
. ' <a href="?restart=10' . header_urlvar('&amp;') . '"> 10</a>'
. ' <a href="?restart=50' . header_urlvar('&amp;') . '"> 50</a>'
. ' <a href="?restart=100' . header_urlvar('&amp;') . '">100</a>'
. ' <a href="?restart=500' . header_urlvar('&amp;') . '">500</a>'
. ' <a href="?restart=1000' . header_urlvar('&amp;') . '">1K</a>'
. ' <a href="?restart=5000' . header_urlvar('&amp;') . '">5K</a>'
. ' <a href="?restart=10000' . header_urlvar('&amp;') . '">10K</a>'
. ' <a href="?restart=50000' . header_urlvar('&amp;') . '">50K</a>'
. ' <a href="?restart=100000' . header_urlvar('&amp;') . '">100K</a> rows';
?>
</div>


<a name="about"></a>
<p><hr /></p>

<h3>About Getting Random with SQLite</h3>
<p>This page tests the randomness of the ORDER BY RANDOM() functionality in SQLite.</p>

<p>The test table is defined as:</p>
<span class="pre"><?php print $random->table[1]; ?> </span>

<p>Test tables may have 1 to <?php print number_format($random->max_table_size); ?> rows.</p>

<p>Data points are individually generated via the SQL call:</p>
<span class="pre"><?php print $random->method[1]; ?> </span>

<p>Add data by clicking a 
<span style="background-color:#e8edd3;">&nbsp;+&nbsp;</span> 
number button to start a test run.</p>

<p>Each test run is limted to ~<?php print $random->time_limit; ?> seconds.</p>

<p>A <em>Frequency of Frequencies</em> chart displays:
<ul>
<li>Frequency: the list of unique frequencies (the number of times a row was randomly selected)
<li>Rows: the number of rows for each frequency present</li>
</ul>
</p>

<p>This site was created with Open Source software.
Find out more on Github: <a href="https://github.com/attogram/random-sqlite-test">random-sqlite-test v<?php print __RST__; ?></a></p>


<footer>
<p><hr /></p>
<p><a href="#top" style="float:right;">Back to top</a></p>
SQL count: <?php print $random->sql_count; ?>
<br />Page generated in <?php 
	$random->end_timer('page');
	print number_format($random->timer['page'], 6); ?> seconds
<br />Hosted by: <a href="//<?php 
print $_SERVER['SERVER_NAME']; ?>/"><?php 
print $_SERVER['SERVER_NAME']; ?></a>
</footer>
<?php $random->display_footer(); ?>
</body>
</html>

