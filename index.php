<!doctype html>
<html><head><title>Testing SQLite ORDER BY RANDOM()</title>
<meta charset="utf-8" />
<meta name="viewport" content="initial-scale=1" />
<?php /* 
if( isset($_GET['run']) ) {
   print '<meta http-equiv="refresh" content="1">';
} */
?>
<style>
body {
   background-color:white; 
   color:black; 
   margin:10px 20px 20px 20px; 
   font-family:sans-serif,helvetica,arial;
}
h1 { font-size:150%; margin:0px 0px 5px 0px; padding:0px; }
h2 { font-size:95%; font-weight:normal; margin:0px; padding:0px; }
a { text-decoration:none; color:black; background-color:#ffb; }
a:visited { color:black; background-color:#ffa; }
a:hover { background-color:yellow; color:black; }
ul { margin:0px; }
.results {
   white-space:pre;
   font-family:monospace;
   display:inline-block;
}
.datah {
   display:inline-block;
   background-color:lightgrey;
   color:black;
   margin:0px;
   font-weight:bold;
}
.datab {
   display:inline-block;
   background-color:yellow;
   color:black;
   padding-left:10px
   padding-right:10px;
   margin-bottom:1px;
   font-weight:bold;
}
.notice {
   background-color:lightyellow;
   font-size:90%;
   border:1px solid black;
   padding:2px;
}
.pre { white-space:pre; font-family:monospace; font-size:120%; }
</style>
</head><body><a name="top"></a>
<?php

require_once( __DIR__.'/random.php' );
$random = new random();

if( isset($_GET['run']) ) {
   $nhits = (int)$_GET['run'];
   if( !$nhits || !is_int($nhits) || $nhits > 1000 ) { $nhits = 1000; }
   $hit = $random->add_more_random( $nhits );
}

if( isset($_GET['a']) ) {
   switch( $_GET['a'] ) {
   
      case 'erase': // clear all data
         $random->delete_all_data();
         break;

      case 'delete': // DROP TABLE test
         $random->delete_test_table();
         break;

      case 'create': // Create Test Table
         $size = isset($_GET['size']) ? $_GET['size'] : 10;
         if( !$size ) { $size = 10; }
         $random->create_test_table( $size );
         break;

      default:
         print '<p>ERROR: invalid input</p>';
         break;
   }
}
?>
<div style="float:right;"><a href="./#about">&nbsp;About&nbsp;</a> &nbsp; <a href="./">&nbsp;Refresh&nbsp;</a></div>
<h1>Testing SQLite ORDER BY RANDOM()</h1>
<?php /*
if( isset($_GET['run']) ) {
   print '<p><a href="./">STOP AUTO RUN</a></p>';
 */
?>
<div class="results"><?php 

$distribution_chart = $random->display_distribution();
$info = $random->get_test_table_info();

print '<span style="font-size:130%; font-weight:bold;"><a href="./?run=1"
> +1  </a> <a href="./?run=10"
> +10  </a> <a href="./?run=25"
> +25  </a> <a href="./?run=50"
> +50  </a> <a href="./?run=100"
> +100</a> <a href="./?run=500"
> +500</a> <a href="./?run=1000"
>+1000</a></span><br />';
print '<br />Table size      : <b>' . number_format(@$info['class_size']) . ' rows</b>';
print '<br /># Data points   : <b>' . number_format(@$info['data_sum']) . '</b>';
print '<br /># Frequencies   : <b>' . number_format($random->frequencies_count) . '</b>';
print '<br />Freq Hi/Lo/Range: <b>' 
   . number_format($random->highest_frequency) 
   . ' / ' . number_format($random->lowest_frequency) 
   . ' / ' . number_format( $random->highest_frequency - $random->lowest_frequency )
   . '</b>';
print '<br />Rows Hi/Lo/Range: <b>' 
   . number_format($random->highest_count) 
   . ' / ' . number_format($random->lowest_count) 
   . ' / ' . number_format( $random->highest_count - $random->lowest_count )
   . '</b>';
   
print '<br /><br />' . $distribution_chart;

?>
</div>

<br /><br />

<div class="results"><a href="?a=erase">Erase all data</a>     <a href="?a=delete">Delete table</a>
<br />New table: <a 
href="?a=create&size=2"> 2 </a> <a 
href="?a=create&size=5"> 5 </a> <a 
href="?a=create&size=10"> 10</a> <a 
href="?a=create&size=25"> 25</a> <a 
href="?a=create&size=50"> 50</a> <a 
href="?a=create&size=100"> 100</a> <a 
href="?a=create&size=250"> 250</a> <a 
href="?a=create&size=500"> 500</a> <a 
href="?a=create&size=1000"> 1,000</a> <a 
href="?a=create&size=10000"> 10,000</a> <a 
href="?a=create&size=100000"> 100,000</a> rows
</div>


<br /><br /><br />
<hr />
<a name="about"></a>

<h3>About Getting Random with SQLite</h3>
<p>This page tests the randomness of the ORDER BY RANDOM() functionality in SQLite.</p>

<p>The test table is defined as:</p>
<span class="pre"><?php print $random->table[1]; ?> </span>

<p>More test data is added everytime you click a <span style="background-color:#ff9;">&nbsp;+&nbsp;</span> number button above.</p>

<p>The test SQL call is:</p>
<span class="pre"><?php print $random->method[1]; ?> </span>

<p>A Frequency chart displays a list of unique frequencies (the number of times a row was randomly selected), 
and the number of rows for each frequency present.

<p><a href="#top">Back to top</a></p>

<footer>

<p><hr /></p>

</footer>

<p>SQL count: <?php print $random->sql_count; ?></p>
<p>Fork this: <a href="https://github.com/attogram/random-sqlite-test">https://github.com/attogram/random-sqlite-test</a></p>

</body>
</html>

