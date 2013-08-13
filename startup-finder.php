<?php
/**
 * @package Startup_Finder
 * @version 0.1
 */
/*
Plugin Name: Starup Finder
Plugin URI: https://github.com/ryanhightower/startup-finder
Description: Pulls company data from CrunchBase and displays them using a shortcode.
Author: Ryan Hightower
Version: 0.1
Author URI: http://ryanhigtower.com


Map:
1. Build query 
	(search.js?
	companies (&entity=company), 
	in 94301 (geo=94301), 
	within 10 miles (&range=10), 
	page offset (&page=n)
	)
2. Send request
3. Receive data
4. Parse data
	- collect all companies from query
	- request additional data from CrunchBase on each company
	- sift companies to match 1yr old and less than 500 employees
5. Display Data 
	- Using shortcode to display table for now.
	- Feature Request: This data should be cached somewhere so it doesn't have to be processed every time.

*/


// Add Shortcode



function startup_finder(){
		
		// 1. Build the query
		// Create CrunchBase URL with the tag.
		// Hardcoded for now. In future iteration, these should be editable either via an options page or a shortcode.
		
		$query_args = "geo=94301&range=10&entity=company";
		
		$request = "http://api.crunchbase.com/v/1/search.js?" . $query_args;
		
		// Sets the page offset
		// Currently not used but needs to be implemented ASAP as CrunchBase only returns data 10 at a time.
		if(isset($page)) $request .= "&page=".$page;

		// Authorize the api call.
		$request .= "&api_key=c7du3hcrfnz7jgs5xecbt6cg"; 
		
		
		// 2. Send request
		$handle = @fopen($request, "rb");
		$jsonText = @stream_get_contents($handle);
		@fclose($handle);
		
		// 3. Received data?
		if (!$jsonText)
			
            return var_dump($request); // Something went wrong, and we should do nothing and exit

		// 4. Parse data
		// Per client requirements, data from CrunchBase query will need to pass through a second loop to 
		// determine if it matches the 1yr old and under 500 employees criteria. 
		// But for now, we'll display the data we have by looping through the content and building a table. 
		$jsonObject = json_decode($jsonText); // main CrunchBase object.
		
		foreach($jsonObject->{"results"} as $jsonCompany){
			
			$companyName = $jsonCompany->{"name"};
			$companyDescription = $jsonCompany->{"overview"};
			$homePageUrl = $jsonCompany->{"homepage_url"};
			
			// 5. Display Data
			// Build and return a simple table. 
			
			$data .= "<tr>";
				$data .= "<td><b><a rel='nofollow' href='" . $homePageUrl . "'>" . $companyName . "</a></b></td>";
				$data .= "<td colspan='2'>" . $companyDescription . "</td>";
			$data .= "</tr>";			
			}
		
		$table .= "<div>";
			$table .= "	<table width='90%' border='1' cellspacing='0' cellpadding='3'>";
				$table .= $data;
			$table .= "	</table>";
		$table .= "</div>";
			
			
		return $table; // will be displayed via shortcode function.
}



add_shortcode( 'startup-finder', 'startup_finder' );