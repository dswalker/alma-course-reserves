<?php

/*
 * This file is part of the CSU Course Reserves App
 *
 * (c) California State University <library@calstate.edu>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Reserves;

use Alma\Courses;

/**
 * List of courses
 * 
 * @author dwalker
 */
class CourseList
{
    /**
     * @var Config
     */
    protected $config;
    
    /**
     * @var string
     */
    protected $campus;
    
    /**
     * New CourseList object
     */
    public function __construct($campus)
    {
        $this->config = new Config("campuses/$campus/config.ini");
        $this->campus = $campus;
    }
    
    /**
     * Get courses
     *
     * @return Courses
     */
    public function getCourses()
    {
        if ($this->config->get('api_key') == "") {
            return false;    
        }
        
        return new Courses($this->config->get('host'), $this->config->get('api_key'));
    }
    
    /**
     * Primo jump-to URL
     * 
     * @param string $id
     * @return string;
     */
    public function getPrimoUrl($id)
    {
        $url = "https://api-na.hosted.exlibrisgroup.com/primo/v1/pnxs?" . 
            "q=any,contains,$id" .
            "&apikey=" . $this->config->get('api_key');
        
        $results = file_get_contents($url);
        $json = json_decode($results, true);
        
        $primo_id = "";
        
        if (array_key_exists('docs', $json)) {
            if (array_key_exists(0, $json['docs']) ) {
                $primo_id = $json['docs'][0]['pnxId'];
            }
        }
        
        // didn't work, so send them to OpenURL
        if ($primo_id == "") {
            return $_GET['openurl'];
        }
        
        // got one, send them directly!
        $url = $this->config->get('primo_url');
        $url = str_replace('{{docid}}', $primo_id, $url);
        return $url;
    }
    
    /**
     * Get items and status for a bib record
     * 
     * @param string $mms_id
     * @return array
     */
    public function getItems($mms_id)
    {
        $items = array();

        $query = "format=json&apikey=" . $this->config->get('api_key');
        $url = "https://api-na.hosted.exlibrisgroup.com/almaws/v1/bibs/$mms_id/holdings?$query";
                    
        $results = file_get_contents($url);
        $holdings_json = json_decode($results, true);
        
        // return $holdings_json;
        
        if (array_key_exists('holding', $holdings_json)) {
            foreach ($holdings_json['holding'] as $holding) {
                
                $link = $holding['link'];
                $items_response = file_get_contents("$link/items?$query");
                $items_json = json_decode($items_response, true);
                
                // return $items_json;
                
                if (array_key_exists('item', $items_json)) {
                    foreach ($items_json['item'] as $item_json) {
                        
                        $location = $item_json['holding_data']['temp_location']['desc'];
                        
                        if ($location == "") {
                            $location = $item_json['item_data']['location']['desc'];
                        }
                        
                        $items[] = array(
                            'mms_id' => $mms_id,
                            'location' => $location,
                            'call_number' => $item_json['holding_data']['call_number'],
                            'availability' => $item_json['item_data']['base_status']['desc'],
                            'available' => $item_json['item_data']['base_status']['value'],
                        );
                    }
                }
            }
        }
        
        return $items;
    }
}
