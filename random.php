<?php
// Randomosity Tester

define('__RT__', '0.2.2');

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

    function is_good_number($n='') {
        if ( preg_match('/^[0-9]*$/', $n )) { return TRUE; }
        return FALSE;
    }

}

class db extends utils {

    var $db;
    var $database_name;
    var $sql_count;

    function init_database() {
        if( !in_array('sqlite', PDO::getAvailableDrivers() ) ) {
            $this->notice('ERROR: init_database: SQLite PDO Driver not installed');
            $this->db = FALSE;
            return FALSE;
        }
        try {
            $this->db = new PDO('sqlite:'. $this->database_name);
        } catch(PDOException $e) {
            $this->notice('ERROR: init_database: unable to open SQLite database');
            $this->db = FALSE;
            return FALSE;
        }

        if( !$this->tables_exist() ) {
            //$this->notice('ERROR: init_database: tables do not exist');
            if( !$this->create_tables() ) {
                //$this->notice('ERROR: init_database: unable to create tables');
                return FALSE;
            }
        }
        return TRUE;
    } // end function init_database()

    function query_as_array( $sql, $bind=array() ) {
        if( !$this->db ) { $this->init_database(); }
        if( !$this->db ) { return array(); }
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

} // end class db

class random_db extends db {

    var $test_table;
    var $table_size;

    var $random_min;  // min value for generated random number
    var $random_max;  // max value for generated random number
    var $random_max_default;

    var $rows_count;
    var $rows_sum;
    var $rows_avg;
    var $rows_min;
    var $rows_max;
    var $rows_range;

    var $frequency_count;
    var $frequency_sum; // Sum of data points in test table
    var $frequency_avg;
    var $frequency_min;
    var $frequency_max;
    var $frequency_range;

    var $frequencies_count;

    var $distribution; // array of Frequency=>Rows of test table

    function __construct() {

        $this->database_name = __DIR__ . '/db/test.sqlite';

        $this->random_min = 1;
        $this->random_max_default = 1000;

        $this->test_table = "CREATE TABLE 'test' (\n"
            . "  'id' INTEGER PRIMARY KEY,\n"
            . "  'frequency' INTEGER DEFAULT '0'\n);";

    }

    function tables_exist() {
        $tables = $this->query_as_array('SELECT name, sql FROM sqlite_master WHERE type = "table"');
        if( !$tables ) {
            //$this->notice('ERROR: tables_exist: no tables found');
            return FALSE;
        }
        foreach( $tables as $table ) {
            if( $table['name'] == 'test' ) {
                //$this->notice('OK: test table exists:<br />' . $table['sql']);
                return TRUE;
            }
        }
        $this->notice('ERROR: tables_exist: test table not found');
        return FALSE;
    }

    function create_tables() {

        $this->start_timer('get_data');
        if ( !$this->query_as_bool( $this->test_table ) ) {
            $this->notice('create_tables: CREATE FAILED: errorinfo: ' . print_r($this->db->errorInfo(),1));
            $this->end_timer('get_data');
            return FALSE;
        }
        $this->end_timer('get_data');

        $this->start_timer('save_data');
        if( !$this->random_max ) {
            $this->random_max = $this->random_max_default;
        }
        $this->begin_transaction();
        for ($i = 1; $i <= $this->random_max; $i++) {
            if( !$this->query_as_bool("INSERT INTO test (id) VALUES ('$i')") ) {
                $this->notice('create_tables: INIT FAILED: errorinfo: ' . print_r($this->db->errorInfo(),1));
                return FALSE;
            }
        }
        $this->commit();
        $this->vacuum();
        $this->end_timer('save_data');
        $this->notice('RESTARTED: ' . $this->generator . ', range '
            . $this->random_min . '-' . number_format($this->random_max));
        return TRUE;

    } // end function create_table

    function delete_test_table() {
        if( $this->query_as_bool('DROP TABLE test;') ) {
            $this->vacuum();
            return TRUE;
        }
        $this->notice('ERROR: can not drop test table');
        return FALSE;
    }

    function get_test_table_size() {
        $size = $this->query_as_array('SELECT count(id) AS table_size FROM test');
        if( !$size || !isset($size[0]['table_size']) ) {
            return $this->table_size = 0;
        }
        return $this->table_size = $size[0]['table_size'];
    }

    function get_results() {
        $info = $this->query_as_array(
            'SELECT count(frequency) AS frequency_count,
                    sum(frequency) AS frequency_sum,
                    avg(frequency) AS frequency_avg,
                    min(frequency) AS frequency_min,
                    max(frequency) AS frequency_max
            FROM test'
        );
        if( !$info || !isset($info[0]) ) {
            $this->notice('ERROR: unable to get test data');
            return;
        }
        $this->table_size = $this->frequency_count = $info[0]['frequency_count'];
        $this->frequency_sum = $info[0]['frequency_sum'];
        $this->frequency_avg = $info[0]['frequency_avg'];
        $this->frequency_min = $info[0]['frequency_min'];
        $this->frequency_max = $info[0]['frequency_max'];
        $this->frequency_range = $this->frequency_max - $this->frequency_min;

        // Distribtuion
        $this->distribution = array();
        $dist = $this->query_as_array('
            SELECT frequency, count(id) AS count
            FROM test
            GROUP BY frequency
            ORDER BY frequency DESC
        ');
        if( !$dist ) {
            return FALSE;
        }

        $this->distribution = $dist;

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
        $this->rows_count = sizeof($dist);
        $this->rows_max = $chigh;
        $this->rows_min = $clow;
        $this->rows_range = $this->rows_max - $this->rows_min;
        $this->rows_avg = round($ctotal / $this->rows_count, 2);

        $this->frequencies_count = sizeof($dist);

    } // end function get_results()

    function add_more_random() {

        if( !$this->run ) {
            return;
        }

        $hits = array();
        $this->start_timer('get_data');

        //$this->notice('generator=' . $this->generator . ' random min=' . $this->random_min . ' max=' . $this->random_max);

        for ($i = 1; $i <= $this->run; $i++) {

            switch( $this->generator ) {

                case 'php_rand':
                    $hit = rand( $this->random_min, $this->random_max);
                    break;

                case 'php_mt_rand':
                    $hit = mt_rand( $this->random_min, $this->random_max );
                    break;

                case 'sqlite_order_by_random':
                default:
                    $hit = $this->query_as_array(
                        $this->generators['sqlite_order_by_random']['sql']
                    );
                    if( !$hit || !isset($hit[0]['id']) ) {
                        $this->notice('ERROR: get random query failed');
                        return FALSE;
                    }
                    $hit = $hit[0]['id'];
            } // end switch on generator

            $hits[] = $hit;

            if( $this->lap_timer('page') > $this->time_limit ) {
                $this->notice('TIMEOUT');
                $this->run = $i;
                $this->end_timer('get_data');
                goto save_data;
            }
        }
        $this->end_timer('get_data');

        save_data:
        $this->notice('+' . number_format($i-1) . ' data');

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

} // end class random_db

class random extends random_db {

    var $run; // how many tests to run

    var $generators; // array of Random Number Generators
    var $generator; // Active generator
    var $default_generator;

    var $restart; // restart: # of rows to create in new test table, or FALSE
    var $time_limit; // test time limit, in seconds

    var $show_header; // site header

    function __construct() {

        $this->start_timer('page');

        parent::__construct();
        //$this->notice('random::__construct');

        // Defaults, Setups

        $this->time_limit = 1.42; // Time Limitation for test runs, in seconds
        set_time_limit( round($this->time_limit + 10) ); // total page load time limit, in seconds


        $this->generators = array();

        $this->generators['sqlite_order_by_random'] = array(
            'name' => 'SQLite ORDER BY RANDOM()',
            'sql' => "SELECT id\nFROM test\nORDER BY RANDOM()\nLIMIT 1;",
        );
        $this->generators['php_rand'] = array(
            'name' => 'PHP rand()',
        );
        $this->generators['php_mt_rand'] = array(
            'name' => 'PHP mt_rand()',
        );
        $this->generators['php_random_int'] = array(
            'name' => 'PHP random_int()',
        );
        /*$this->generators['sqlite_random'] = array(
            'name' => 'SQLite RANDOM()',
            'sql' => 'RANDOM();',
        );*/


        $this->default_generator = 'sqlite_order_by_random';

        $this->set_parameters();

        $this->get_results();

    } // end __construct()

    function set_parameters() {

        // Number of test runs
        $this->run = isset($_GET['run']) ? $_GET['run'] : 0;
        if( !$this->is_good_number($this->run) ) {
            $this->run = 0;
        }
        // Random generator
        $this->generator = isset($_GET['gen']) ? $_GET['gen'] : '';
        if( !array_key_exists($this->generator, $this->generators) ) {
            $this->generator = $this->default_generator;
        }

        // Restart test
        $this->restart = isset($_GET['restart']) ? $_GET['restart'] : 0;
        if( !$this->restart || !$this->is_good_number($this->restart) ) {
            $this->restart = 0;
             $this->random_max = $this->get_test_table_size();
        } else {
            $this->random_max = $this->restart;
        }

        $this->show_header = (isset($_GET['h']) && $_GET['h'] == '1') ? FALSE : TRUE;

    } // end function set_parameters()

    function display_add_data() {
        return ''
        . '<span style="font-size:130%; font-weight:bold;">'
        . '<a href="' . $this->url(array('run'=>1)) . '">+1</a>'
        . ' <a href="' . $this->url(array('run'=>10)) . '">+10</a>'
        . ' <a href="' . $this->url(array('run'=>100)) . '">+100</a>'
        . ' <a href="' . $this->url(array('run'=>1000)) . '">+1K</a>'
        . ' <a href="' . $this->url(array('run'=>10000)) . '">+10K</a>'
        . ' <a href="' . $this->url(array('run'=>999999999)) . '">+MAX</a>'
        . '</span>';
    }

    function display_chart() {
        $pad_size = 6;
        return ''
        . '<br />range: <b>' . $this->random_min . '-' . number_format($this->random_max) . '</b>, '
        . 'data points: <b>' . number_format($this->frequency_sum) . '</b>, '
        . 'groups: <b>' . number_format($this->frequencies_count) . '</b>'
        . '<br />           High   / Low    / Range  / Average<br />'
        . 'Frequency: <b>' . str_pad(number_format($this->frequency_max), $pad_size, ' ')
        . '</b> / <b>' . str_pad(number_format($this->frequency_min), $pad_size, ' ')
        . '</b> / <b>' . str_pad(number_format($this->frequency_range), $pad_size, ' ')
        . '</b> / <b>' . str_pad(number_format($this->frequency_avg,2), $pad_size, ' ') . '</b>'
        . '<br />     Rows:<b> ' . str_pad(number_format($this->rows_max), $pad_size, ' ')
        . '</b> / <b>' . str_pad(number_format($this->rows_min), $pad_size, ' ')
        . '</b> / <b>' . str_pad(number_format($this->rows_range), $pad_size, ' ')
        . '</b> / <b>' . str_pad(number_format($this->rows_avg,2), $pad_size, ' ') . '</b>'
        ;
    }

    function display_distribution() {

        $fratio = $cratio = 1;
        if( $this->frequency_max > 0 ) {
            $fratio = (100/$this->frequency_max);
        }
        if( $this->rows_max > 0 ) {
            $cratio = (100/$this->rows_max);
        }

        $display = '<div class="chart">'
            . '<div class="freq header freqheader pre">Frequency </div>'
            . '<div class="row header rowheader pre"> Rows</div>'
            ;
        if( !is_array($this->distribution) ) {
            $display .= '<p style="text-align:center;">No data found</p>';
            $this->distribution = array();
        }
        foreach( $this->distribution as $dist ) {
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

    function url( $vars=array(), $hash='' ) {
        if( !is_array($vars) ) {
            $vars = array();
        }
        $url = './';
        if( !array_key_exists('gen', $vars) ) {
            $url .= '?gen=' . $this->generator;
        } else {
            $url .= '?gen=' . $vars['gen'];
            unset($vars['gen']);
        }
        if( !array_key_exists('h', $vars) ) {
            if( !$this->show_header ) {
                $url .= '&amp;h=1';
            }
        }
        while( list($name,$val) = each($vars) ) {
            if( $name == 'h' && $val == 0 ) { continue; }
            $url .= '&amp;' . urlencode($name) . '=' . urlencode($val);
        }
        if( $hash ) {
            $url .= '#' . $hash;
        }
        return $url;
    } // end function url()

} // end class
