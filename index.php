<?php
// Randomosity Tester
// page

require_once( __DIR__.'/random.php' );
$random = new random();

$page_title = @$random->generators[$random->generator]['name'] . ' Randomosity Tester';

?><!doctype html>
<html><head><title><?php print $page_title; ?></title>
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
 <a href="<?php print $random->url(); ?>">&nbsp;Refresh&nbsp;</a>
 <a href="<?php print $random->url(FALSE,'about'); ?>">&nbsp;About&nbsp;</a>
 <a href="<?php print $random->url(FALSE, 'tools'); ?>">&nbsp;Tools&nbsp;</a>
</div>
<h1><?php print $page_title; ?></h1>
<?php


if( $random->restart ) {
    $random->delete_test_table();
    $random->create_tables();
}

if( $random->run ) {
   $random->add_more_random();
}

$random->get_results();

print '<div class="pre">'
. $random->display_add_data()
. $random->display_chart()
. '</div>'
. $random->display_distribution()
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

<a name="tools"></a>
Test Tools:

Restart test: Random range 1 to:

<?php
print '<a href="' . $random->url(array('restart'=>1)) . '"> 1 </a>'
. ' <a href="' . $random->url(array('restart'=>2)) . '"> 2 </a>'
. ' <a href="' . $random->url(array('restart'=>3)) . '"> 3 </a>'
. ' <a href="' . $random->url(array('restart'=>4)) . '"> 4 </a>'
. ' <a href="' . $random->url(array('restart'=>5)) . '"> 5 </a>'
. ' <a href="' . $random->url(array('restart'=>10)) . '">10</a>'
. ' <a href="' . $random->url(array('restart'=>50)) . '">50</a>'
. ' <a href="' . $random->url(array('restart'=>100)) . '">100</a>'
. ' <a href="' . $random->url(array('restart'=>500)) . '">500</a>'
. ' <a href="' . $random->url(array('restart'=>1000)) . '">1K</a>'
. ' <a href="' . $random->url(array('restart'=>5000)) . '">5K</a>'
. ' <a href="' . $random->url(array('restart'=>10000)) . '">10K</a>'
. ' <a href="' . $random->url(array('restart'=>50000)) . '">50K</a>'
. ' <a href="' . $random->url(array('restart'=>100000)) . '">100K</a>'; ?>


Random Generator Method:

<?php

while( list($id,$gen) = each($random->generators) ) {
    if( $id == $random->generator ) {
        print '<span style="border:1px solid red; padding:2px;">' . $gen['name'] . '</span>';
    } else {
        print '<a href="' . $random->url(array('gen'=>$id, 'restart'=>$random->random_max)) . '">' . $gen['name'] . '</a>';
    }
    print ' ';
} ?>


<a href="#top">Back to top</a>
</div>


<a name="about"></a>
<p><hr /></p>

<h3>About the Randomosity Tester</h3>
<p>This page tests the frequency distribution and timing of random number generation via these methods:
<ul>
<li>SQLite ORDER BY RANDOM()</li>
<li>PHP rand()</li>
<li>PHP mt_rand()</li>
<li>PHP random_int()</li>
</ul>
</p>

<p>Random number results are stored in a test table.
The test table is defined as:</p>
<span class="pre"><?php print $random->test_table; ?> </span>

<p>The range of random numbers is currently set to <?php print number_format($random->random_min); ?> 
to <?php print number_format($random->random_max); ?>.</p>

<p>For SQLite tests, results are individually generated via the SQL call:</p>
<span class="pre"><?php 
print $random->generators['sqlite_order_by_random']['sql']; 
?> </span>

<p>Generate more random numbers by clicking a 
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
Find out more on Github: <a href="https://github.com/attogram/randomosity-tester">randomosity-tester v<?php print __RT__; ?></a></p>

<p><a href="#top">Back to top</a></p>

<footer>
<p><hr /></p>

PHP Version: <?php print phpversion(); ?> 
<br />SQLite Version: <?php 
    $version = $random->query_as_array('SELECT SQLITE_VERSION() AS version;');
    isset($version[0]['version']) ? print $version[0]['version'] : print '?';
 ?> 
<br/>SQL count: <?php print $random->sql_count; ?>
<br />Page generated in <?php 
    $random->end_timer('page');
    print number_format($random->timer['page'], 6); ?> seconds
<br />Hosted by: <a href="//<?php print $_SERVER['SERVER_NAME']; ?>/"><?php print $_SERVER['SERVER_NAME']; ?></a>
</footer>
<?php $random->display_footer(); ?>
</body>
</html>
