<?php
// SQLite ORDER BY RANDOM() Tester

define('__RST__', '0.0.12');

class utils {
    
    var $timer;

    function notice( $msg ) {
        print '<div class="notice pre">' . print_r($msg,1) . '</div>';
    }

    function start_timer( $name ) {
        $this->timer[$name] = microtime(1);
    }
    
    function end_timer( $name ) {
        if( !isset($this->timer[$name]) ) {
            $this->timer[$name] = 0;
			return;
        }
        $this->timer[$name] = microtime(1) - $this->timer[$name];		
    }

	function lap_timer( $name ) {
		if( !isset($this->timer[$name]) ) {
			return 0;
		}
		return microtime(1) - $this->timer[$name];
	}
	
	function display_header() {
		if( is_readable(__DIR__.'/header.php') ) {
			include( __DIR__.'/header.php');
		}
	}
	
	function display_footer() {
		if( is_readable(__DIR__.'/footer.php') ) {
			include( __DIR__.'/footer.php');
		}
	}

}

class db extends utils {

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
		if( !$this->db ) { return array(); }
        $statement = $this->db->prepare($sql);
        if( !$statement ) {
			$err = $this->db->errorInfo();
			if( isset($err[2]) && $err[2] == 'no such table: test' ) {
				//$this->notice('ERROR: test table not found');
				$this->create_tables();
				return array();
			}
			$this->notice('EROR: SQLite prepare failed: ' . $sql);
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
		if( !$this->db ) { return FALSE; }
        $statement = $this->db->prepare($sql);
        if( !$statement ) {
            return FALSE;
        }
        while( $x = each($bind) ) {
            $statement->bindParam( $x[0], $x[1] );
        }    
        if( !$statement->execute() ) {
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
        if( !$size ) { $size = $this->default_table_size; }
        if( $size > $this->max_table_size ) { $size = $this->max_table_size; }
		$this->start_timer('get_data');
        if ( !$this->query_as_bool( $this->table[1] ) ) {
            $this->notice('ERROR creating table: ' . $this->table[1]);
            return FALSE;
        }
		$this->end_timer('get_data');
		$this->start_timer('save_data');		
        $this->begin_transaction();
        for ($i = 1; $i <= $size; $i++) {
            if( !$this->query_as_bool("INSERT INTO test (id) VALUES ('$i')") ) {
                $this->notice('ERROR creating rows');
                return FALSE;
            }
        }
        $this->commit();
        $this->vacuum();
		$this->end_timer('save_data');
        return TRUE;
    }

    function get_test_table_info() {
        $info = $this->query_as_array('SELECT count(id) AS class_size, sum(frequency) AS data_sum FROM test');
        if( isset($info[0]) ) {
            return $info[0];
        }
        return array();
    }

    function delete_test_table() {
        if( $this->query_as_bool('DROP TABLE test;') ) {
            $this->vacuum();
            return TRUE;
        }
        return FALSE;
    }

    function delete_all_data() {
        if( $this->query_as_bool('UPDATE test SET frequency = 0;') ) {
            $this->vacuum();
            return TRUE;
        }
        return FALSE;
    }

	function create_tables() {
		$r = $this->create_test_table($this->default_table_size);
		//$r = $this->query_as_bool($this->history_table);
		$this->notice('Welcome!  New Test Database Created.  Reload the page to start testing.');
	}
}

class random extends db {

    var $method;
    var $table;
	var $history_table;
    var $highest_frequency;
    var $highest_rows;
    var $lowest_frequency;
    var $lowest_rows;
    var $frequencies_count;
    var $frequencies_average;
	var $rows_average;
    var $default_table_size;
	var $max_table_size;
	var $time_limit;
	var $run; // how many tests to run

    function __construct() {

        $this->start_timer('page');
		
		$this->time_limit = 1.42; // Time Limitation for test runs, in seconds
		
        set_time_limit( round($this->time_limit + 10) );

        $this->database_name = __DIR__ . '/db/test.sqlite';

        $this->default_table_size = 1000;
		$this->max_table_size     = 100000;
        
        $this->method[1] = 
'SELECT id
FROM test
ORDER BY RANDOM()
LIMIT 1';

        $this->table[1] = 
"CREATE TABLE 'test' (
  'id' INTEGER PRIMARY KEY,
  'frequency' INTEGER DEFAULT '0'
)";
/*
		$this->history_table = 
"CREATE TABLE 'history' (
  'history_id' INTEGER PRIMARY KEY,
  'table_size' INTEGER,
  'id' INTEGER,
  'frequency' INTEGER DEFAULT '0',
  CONSTRAINT hu UNIQUE (table_size, id)
)";
*/	
        $this->query_as_bool('PRAGMA synchronous=OFF;'); // do not wait for disk writes

        $this->query_as_bool('PRAGMA count_changes=OFF;'); // do not do callback to count changes per query

    } // end __construct()

    function add_more_random( $size=1 ) {
        if( !$size || !is_int($size) ) { $size = 1; }

        $hits = array();

        $this->start_timer('get_data');
        for ($i = 1; $i <= $size; $i++) {
            $hit = $this->query_as_array( $this->method[1] );
            if( !$hit || !isset($hit[0]['id']) ) {
                $this->notice('ERROR: Database busy.');
                return FALSE;
            }
            $hits[] = $hit[0]['id']; 

			if( $this->lap_timer('page') > $this->time_limit ) {
				$this->notice('TIME LIMIT REACHED: +' . number_format($i) . ' data');
				$this->run = $i;
				$this->end_timer('get_data');
				goto save_data;
			}
        }
        $this->end_timer('get_data');
		$this->notice('OK: +' . number_format($i-1) . ' data');

		save_data:
        $this->start_timer('save_data');
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
        $this->end_timer('save_data');
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
		
		$dists = $this->get_distribution();
		
		$fratio = $cratio = 1;
		if( $this->highest_frequency > 0 ) {
			$fratio = (100/$this->highest_frequency);
		}
		if( $this->highest_rows > 0 ) {
			$cratio = (100/$this->highest_rows);
		}
		
        $display = '<div class="chart">'
            . '<div class="freq header freqheader pre">Frequency </div>'
            . '<div class="row header rowheader pre"> Rows</div>'
            ;
        foreach( $dists as $dist ) {
            $cwidth = $cratio * $dist['count'];
            if( $cwidth < 1 ) { $cwidth = 1; }
            if( $cwidth > 100 ) { $cwidth = 100; }
			$hwidth = $fratio * $dist['frequency'];
            if( $hwidth < 4 ) { $hwidth = 4; }
            if( $hwidth > 100 ) { $hwidth = 100; }

            $display .= ''
                . '<div class="freq">' 
					. '<div class="data freqdata pre" style="width:' . $hwidth . '%;">'
					. number_format($dist['frequency'])
					. ' </div>'
				. '</div>'
                . '<div class="row">'
					. '<div class="data rowdata pre" style="width:' . $cwidth . '%;"> '
                    . number_format($dist['count'])
					. '</div>'
				. '</div>'
                ;
        }            
        $display .= '</div>';
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
        $this->highest_rows = $chigh;
        $this->lowest_frequency = $flow;
        $this->lowest_rows = $clow;
        $this->frequencies_count = sizeof($dist);

        $this->frequencies_average = round($ftotal / $this->frequencies_count, 2);
        $this->rows_average = round($ctotal / $this->frequencies_count, 2);

        return $dist;
    }

}
