<?php
// Random SQLite Test

define('__RST__', '0.0.5');

class db {

    var $db;
    var $database_name;
    var $sql_count;
        
    function init_database() {
        if( !in_array('sqlite', PDO::getAvailableDrivers() ) ) {
            return $this->db = FALSE;
        }
        try {
            return $this->db = new PDO('sqlite:'. $this->database_name);
        } catch(PDOException $e) {
            return $this->db = FALSE;
        }
    }

    function query_as_array( $sql, $bind=array() ) {
        if( !$this->db ) { $this->init_database(); }
        $statement = $this->db->prepare($sql);
        if( !$statement ) {
            return array();
        }
        while( $x = each($bind) ) {
            $statement->bindParam( $x[0], $x[1]);
        }    
        if( !$statement->execute() ) {
            return array();
        }
        $this->sql_count++;
        $r = $statement->fetchAll(PDO::FETCH_ASSOC);
        if( !$r && $this->db->errorCode() != '00000') {
            $r = array();
        }
        return $r;
    }

    function query_as_bool( $sql, $bind=array() ) {
        if( !$this->db ) { $this->init_database(); }
        $statement = $this->db->prepare($sql);
        if( !$statement ) {
            $this->notice('Statement failed: ' . $sql);
            return FALSE;
        }
        while( $x = each($bind) ) {
            $statement->bindParam( $x[0], $x[1] );
        }    
        if( !$statement->execute() ) {
            $this->notice('Execute failed');
            return FALSE;
        }
        $this->sql_count++;
        return TRUE;
    }
    
    function vacuum() {
        if( $this->query_as_bool('VACUUM') ) {
            return TRUE;
        }
        $this->notice('ERROR vacumming database');
        return FALSE;
    }

    function begin_transaction() {
        if( $this->query_as_bool('BEGIN TRANSACTION') ) {
            return TRUE;
        }
        $this->notice('ERROR begining transaction');
        return FALSE;
    }

    function commit() {
        if( $this->query_as_bool('COMMIT') ) {
            return TRUE;
        }
        $this->notice('ERROR commiting transaction');
        return FALSE;
    }

    function create_test_table( $size='' ) {
        if( !$size ) { $size = 10; }
		if( $size > 100000 ) { $size = 100000; }
        if ( !$this->query_as_bool( $this->table[1] ) ) {
            $this->notice('ERROR creating table: ' . $this->table[1]);
            return FALSE;
        }
        $this->begin_transaction();
        for ($i = 1; $i <= $size; $i++) {

            if( !$this->query_as_bool("INSERT INTO test (id) VALUES ('$i')") ) {
                $this->notice("ERROR creating rows");
                return FALSE;
            }
        }
        $this->commit();
        $this->vacuum();
        //$this->notice('Created test table with ' . $size . ' rows');
        return TRUE;
    }

    function get_test_table() {
        if( $this->query_as_array('SELECT * FROM test ORDER BY id') ) {
            return $table;
        }
        return array();
    }

    function get_test_table_info() {
        $info = $this->query_as_array('
            SELECT count(id) AS class_size, sum(frequency) AS data_sum FROM test
        ');
        if( isset($info[0]) ) {
            return $info[0];
        }
        return array();
    }

    function delete_test_table() {
        if( $this->query_as_bool('DROP TABLE test;') ) {
            $this->vacuum();
            //$this->notice("Dropped table 'test'");
            return TRUE;
        }
        //$this->notice("ERROR dropping table 'test'");
        return FALSE;
    }

    function delete_all_data() {
        if( $this->query_as_bool('UPDATE test SET frequency = 0;') ) {
            $this->vacuum();
            //$this->notice('DELETED all data');
            return TRUE;
        }
        //$this->notice('ERROR deleting all data');
        return FALSE;
    }
    
}

class random extends db {

    var $method;
    var $table;
    var $highest_frequency;
    var $highest_count;
    var $lowest_frequency;
    var $lowest_count;
    var $frequencies_count;
	var $frequencies_average;
	var $default_table_size;

    function __construct() {

		set_time_limit(30);
					
        $this->database_name = __DIR__ . '/db/test.sqlite';

		$this->default_table_size = 100;
		
        $this->method[1] = 'SELECT id
FROM test
ORDER BY RANDOM()
LIMIT 1';
        $this->table[1] = "CREATE TABLE 'test' (
  'id' INTEGER PRIMARY KEY,
  'frequency' INTEGER DEFAULT '0'
)";

		$this->query_as_bool('PRAGMA synchronous=OFF;'); // do not wait for disk writes

		$this->query_as_bool('PRAGMA count_changes=OFF;'); // do not do callback to count changes per query

	} // end __construct()

    function add_more_random( $size=1 ) {
        if( !$size || !is_int($size) ) { $size = 1; }
        if( $size > 1000 ) { $size = 1000; }
        $hits = array();
        for ($i = 1; $i <= $size; $i++) {
            $hit = $this->query_as_array( $this->method[1] );
            if( !$hit || !isset($hit[0]['id']) ) {
                $this->notice('ERROR selecting from test table');
                return FALSE;
            }
            $hits[] = $hit[0]['id'];
        }
        $freqs = array(); // array of id=>frequency 
        foreach( $hits as $hit ) {
            if( isset($freqs[$hit]) ) { 
                $freqs[$hit] +=1;
                continue;
            }
            $freqs[$hit] = 1;
        }
        $this->begin_transaction();
        while( list($id, $frequency) = each($freqs) ) {
            if( $this->query_as_bool("UPDATE test SET frequency = frequency + $frequency WHERE id = $id") ) {
                //$this->notice("ADDED random data: id:$id frequency:$frequency");
                continue;
            }
            $this->notice("ERROR adding random data: id:$id frequency:$frequency");
            return FALSE;
        }
        $this->commit();
        $this->vacuum();
        return TRUE;
    }

    function get_total_data_points() {
        $data = $this->query_as_array('
            SELECT SUM(frequency) AS frequency FROM test
        ');
        if( !$data|| !isset($data[0]['frequency']) ) {
            return 'NULL';
        }
        return $data[0]['frequency'];
    }

    function display_distribution() {
        $display = ''
            . '<div class="datah" style="background-color:lightgrey; color:black; border:1px solid white;"> Frequency </div>'
            . '<div class="datab" style="background-color:lightgrey; color:black; border:1px solid white;"> Rows      </div>'
            . '<br />';
        foreach( $this->get_distribution() as $dist ) {
            $cwidth = 1 * $dist['count'];
            if( $cwidth < 1 ) { $cwidth = 1; }
            //if( $cwidth > 500 ) { $cwidth = 500; }

            $display .= ''
                . '<div class="datah">' 
                    . str_pad(number_format($dist['frequency']), 10, ' ', STR_PAD_BOTH)
                . ' </div>'
                . '<div class="datab" style="width:' 
                    . $cwidth . 'px;"> ' 
                    . number_format($dist['count']) . '</div>'
                . '<br />';
                ;
        }            
        //$display .= '</div>';
        return $display;
    }

    function get_distribution() {
        $dist = $this->query_as_array('
            SELECT frequency, count(id) AS count
            FROM test
            GROUP BY frequency
            ORDER BY frequency DESC
        ');
        if( !$dist ) { return array(); }
        
        $flow = $fhigh = $ftotal = NULL;
        $clow = $chigh = $ctotal = NULL;
        foreach( $dist as $d ) {
            if( !$flow ) { $flow = $d['frequency']; }
            if( !$clow ) { $clow = $d['count']; }
            if( !$fhigh ) { $fhigh = $d['frequency']; }
            if( !$chigh ) { $chigh = $d['count']; }
            if( $d['frequency'] > $fhigh ) { $fhigh = $d['frequency']; }
            if( $d['count'] > $chigh ) { $chigh = $d['count']; }
            if( $d['frequency'] < $flow ) { $flow = $d['frequency']; }
            if( $d['count'] < $clow ) { $clow = $d['count']; }
			$ftotal += $d['frequency'];
			$ctotal += $d['count'];
        }
        $this->highest_frequency = $fhigh;
        $this->highest_count = $chigh;
        $this->lowest_frequency = $flow;
        $this->lowest_count = $clow;
        $this->frequencies_count = sizeof($dist);
		
		$this->frequencies_average = round($ftotal / $this->frequencies_count, 2);
		$this->count_average = round($ctotal / $this->frequencies_count, 2);
		
        return $dist;
    }

    function notice( $msg ) {
        print '<div class="notice pre">' . print_r($msg,1) . '</div>';
    }

}
