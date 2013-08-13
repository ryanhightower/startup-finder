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


function get_companies($page = false){

		
		// 1. Build the query
		// Create CrunchBase URL with the tag.
		// Hardcoded for now. In future iteration, these should be editable either via an options page or a shortcode.
		
		$query_args = "geo=94301&range=10&entity=company";
		
		$request = "http://api.crunchbase.com/v/1/search.js?" . $query_args;
		
		// Sets the page offset
		// Currently not used but needs to be implemented ASAP as CrunchBase only returns data 10 at a time.
		if($page) $request .= "&page=".$page;

		// Authorize the api call.
		$request .= "&api_key=c7du3hcrfnz7jgs5xecbt6cg"; 

		// 2. Send request
		$handle = @fopen($request, "rb");
		$jsonText = @stream_get_contents($handle);
		@fclose($handle);
		
		return $jsonText;
}


function startup_finder(){

		// 3. Received data?
		
		// In the actual program, this should be looped multiple times to get all paginated results,
		// or include pagination on the page so the user can see more results if desired.
		$jsonText = get_companies();
		// var_dump($jsonText);
		

		if (!$jsonText) return; // var_dump($request); // Something went wrong, and we should do nothing and exit

		// 4. Parse data
		// Per client requirements, data from CrunchBase query will need to pass through a second loop to 
		// determine if it matches the 1yr old and under 500 employees criteria. 
		$jsonObject = json_decode($jsonText); // main CrunchBase object.

		echo "parsing companies...<br />";	
		
		$jsonCompanyArr = array();	
		foreach($jsonObject->{"results"} as $jsonCompany){
			
			// get the CrunchBase URL for the company
			$cbURL = $jsonCompany->{"crunchbase_url"};
			
			$temp = explode('/', $cbURL);
			$temp_id = count($temp)-1;
			$cb_company = $temp[$temp_id];
			$cb_company_request = "http://api.crunchbase.com/v/1/company/".$cb_company.".js?&api_key=c7du3hcrfnz7jgs5xecbt6cg";
			
			// Send request for individual company
			$handle = @fopen($cb_company_request, "rb");
			$jsonCompanyText = @stream_get_contents($handle);
			@fclose($handle);
			
			$jsonCompany = json_decode($jsonCompanyText);
			
			// Verify company is less than 500 employees and more than 1yr old.
			if($jsonCompany->{"number_of_employees"} <= 500 && ($jsonCompany->{"number_of_employees"} != NULL || $jsonCompany->{"number_of_employees"} != '')){
				$founded_date = $jsonCompany->{"founded_month"}.'/'.$jsonCompany->{"founded_day"}.'/'.$jsonCompany->{"founded_year"};
				if(strtotime("-365 days")>strtotime($founded_date)){
					$jsonCompanyArr[] = $jsonCompany;
				}
			}
			
		}
//		var_dump($jsonCompanies);
		
		
		// 5. Display Data
		// Build and return a simple table. 
		
//		echo "building table...";
		foreach($jsonCompanyArr as $jsonCompany){
			$companyName = $jsonCompany->{"name"};
			$companyDescription = $jsonCompany->{"overview"};
			$homePageUrl = $jsonCompany->{"homepage_url"};
			$number_of_employees = $jsonCompany->{"number_of_employees"};

			$founded_date = '';
			if($jsonCompany->{"founded_month"}!='') $founded_date .= $jsonCompany->{"founded_month"}.'/';
			if($jsonCompany->{"founded_day"}!='') $founded_date .= $jsonCompany->{"founded_day"}.'/';
			$founded_date .= $jsonCompany->{"founded_year"};
			
			$data .= "<tr>";
				$data .= "<td><b><a rel='nofollow' href='" . $homePageUrl . "'>" . $companyName . "</a></b></td>";
				$data .= "<td>" . $number_of_employees . "</td>";
				$data .= "<td>" . $founded_date . "</td>";
				$data .= "<td>" . $companyDescription . "</td>";
			$data .= "</tr>";			
		}
		
		$table .= "<div>";
			$table .= "	<table width='90%' border='1' cellspacing='0' cellpadding='3'>";
				$table .= "<tr><th>Company Name</th><th># of Employees</th><th>Date Founded</th><th>Company Description</th></tr>";
				$table .= $data;
			$table .= "	</table>";
		$table .= "</div>";
			
			
		return $table; // will be displayed via shortcode function.
}



add_shortcode( 'startup-finder', 'startup_finder' );