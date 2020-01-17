<?php

require_once __DIR__ . '/../vendor/autoload.php';

chdir(__DIR__ . '/../');
ini_set('display_errors', '1');

use Reserves\Config;
use Reserves\DataMap;

// specify campus(es)

$campuses = array();

if (! array_key_exists(1, $argv)) {
	die('No campus specified');
}

$campus = $argv[1];

if ($campus == 'all') {
    $list = glob('campuses/*');
    foreach ($list as $entry) {
        if (is_dir($entry)) {
            $entry  = str_replace('campuses/', '', $entry);
            $campuses[] = $entry;
        }
    }
} else {
	$campuses = array($campus);
}

// let's do it!

foreach ($campuses as $campus ) {
    
    echo "\n==================\n";
    echo "$campus\n";
    echo "==================\n\n";
    
    try {
        
        $config = new Config("campuses/$campus/config.ini");
        $processor = new DataMap($config);
        $courses = $processor->getCourses();
        
        if ( $courses == false ) {
            echo "No API key.\n\n";
            continue;
        }
        
		$course_list = array(); // courses
		$x = 1; // counter
			
		do {
			// get courses in batches of 100
			$results = $courses->getCourses('', 100, $x);
			
			// extract course name and instructor(s)
			foreach ($results->getResults() as $course) {
				$x++;
				
				// only active courses
				if ( $course->getStatus() == 'ACTIVE') {
                    // blank course code and section for leganto campuses
                    if ($config->get('leganto', false, false) == true) {
                        $course->setCode("");
                        $course->setSection("");
                    }
				    $course_list[] = $course;
				}
				
				echo "\r fetching . . . $x";
			}
			
		} while ($x < $results->total);
		
		// serialize it to a file
		file_put_contents("data/$campus.data", serialize($course_list));
		
		echo "\r fetching . . . done.\n";
    } catch (Exception $e) {
        echo "ERROR:" . $e->getMessage() . "\n" . $e->getTraceAsString() . "\n\n";
    }
}
