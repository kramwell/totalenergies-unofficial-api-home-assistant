<?php
###########################################################################

# TotalEnergies Espana Unofficial API.
# This is for the 'A tu Aire luz Siempre' Plan
# v0.6.4 - Initial release. 20-MAY-23
# Can be used for 'Home Assistant'
# @KramWell

# SETUP #
# You just simply need to place this PHP script somewhere it can be reached via home assistant and follow the home assistant steps to expose the JSON results as sensors (if thats the intended purpose). Otherwise can be used as a JSON API for other projects.

############################################################################

# Set TimeStamp
$get_timestamp = new DateTimeImmutable();

#########################
# Make a request to grab 'date since price change' details
#########################
$html = curlRequest("https://www.totalenergies.es/es/node/159201");

	if ($html[1] != 200) {
		error("node-site-not-200");
	}
	# Create a new DOMDocument object
	$dom = new DOMDocument();
	# Disable warnings for invalid HTML
	libxml_use_internal_errors(true);
	# Load the HTML into the DOMDocument
	$dom->loadHTML($html[0]);
	# Use DOMXPath to query the DOM
	$xpath_node = new DOMXPath($dom);

# Get date since last price change
$luz_price_valid_from_date = $xpath_node->query('//div[@class="views-field views-field-nothing-2"]/span/div[@class="blogview"]')->item(0);

#########################
# Make a request to grab details
#########################
$html = curlRequest("https://www.totalenergies.es/es/tabla-precios-luz");

	if ($html[1] != 200) {
		error("luz-site-not-200");
	}
	# Create a new DOMDocument object
	$dom = new DOMDocument();
	# Disable warnings for invalid HTML
	libxml_use_internal_errors(true);
	# Load the HTML into the DOMDocument
	$dom->loadHTML($html[0]);
	# Use DOMxpath_luz to query the DOM
	$xpath_luz = new DOMxpath($dom);
	# Clean up the DOM
	$dom = null;

#########################
# Get table SIN taxes
#########################
$get_table_sin_taxes = $xpath_luz->query('//div[contains(@class, "tabla-total-energies")]')->item(0);

$get_power_under_10kw = $xpath_luz->query('.//tr[td/div[contains(@class, "blogview")]]', $get_table_sin_taxes)->item(0);
	$power_kw_day_sin_taxes_under_10kw = $xpath_luz->query('.//div[contains(@class, "blogview")]', $get_power_under_10kw)->item(1);
	$consumption_kw_hour_sin_taxes_under_10kw = $xpath_luz->query('.//div[contains(@class, "blogview")]', $get_power_under_10kw)->item(2);
$get_power_over_10kw = $xpath_luz->query('.//tr[td/div[contains(@class, "blogview")]]', $get_table_sin_taxes)->item(1);
	$power_kw_day_sin_taxes_over_10kw = $xpath_luz->query('.//div[contains(@class, "blogview")]', $get_power_over_10kw)->item(1);
	$consumption_kw_hour_sin_taxes_over_10kw = $xpath_luz->query('.//div[contains(@class, "blogview")]', $get_power_over_10kw)->item(2);

#########################
# Get table CON taxes
#########################
$get_table_con_taxes = $xpath_luz->query('//div[contains(@class, "tabla-total-energies")]')->item(1);

$get_power_under_10kw = $xpath_luz->query('.//tr[td/div[contains(@class, "blogview")]]', $get_table_con_taxes)->item(0);
	$power_kw_day_con_taxes_under_10kw = $xpath_luz->query('.//div[contains(@class, "blogview")]', $get_power_under_10kw)->item(1);
	$consumption_kw_hour_con_taxes_under_10kw = $xpath_luz->query('.//div[contains(@class, "blogview")]', $get_power_under_10kw)->item(2);
$get_power_over_10kw = $xpath_luz->query('.//tr[td/div[contains(@class, "blogview")]]', $get_table_con_taxes)->item(1);
	$power_kw_day_con_taxes_over_10kw = $xpath_luz->query('.//div[contains(@class, "blogview")]', $get_power_over_10kw)->item(1);
	$consumption_kw_hour_con_taxes_over_10kw = $xpath_luz->query('.//div[contains(@class, "blogview")]', $get_power_over_10kw)->item(2);

#########################
# Check and generate a response to send in JSON format
#########################
if ( isset($luz_price_valid_from_date) & isset($get_table_sin_taxes) & isset($power_kw_day_sin_taxes_under_10kw) & isset($consumption_kw_hour_sin_taxes_under_10kw) & isset($power_kw_day_sin_taxes_over_10kw) & isset($consumption_kw_hour_sin_taxes_over_10kw) & isset($get_table_con_taxes) & isset($power_kw_day_con_taxes_under_10kw) & isset($consumption_kw_hour_con_taxes_under_10kw) & isset($power_kw_day_con_taxes_over_10kw) & isset($consumption_kw_hour_con_taxes_over_10kw) ) {

	# Verify, split and re-compile date since last price change
	$luz_price_valid_from_date_array = verifyDate(strtolower($luz_price_valid_from_date->nodeValue));
	$luz_price_valid_from_date = $luz_price_valid_from_date_array['day'] .".". $luz_price_valid_from_date_array['month'] .".". $luz_price_valid_from_date_array['year'];

	# Check what is returned consists of atleast 0-9 and a , (comma)
	$power_kw_day_sin_taxes_under_10kw = checkFormat($power_kw_day_sin_taxes_under_10kw->nodeValue);
	$consumption_kw_hour_sin_taxes_under_10kw = checkFormat($consumption_kw_hour_sin_taxes_under_10kw->nodeValue);
	$power_kw_day_sin_taxes_over_10kw = checkFormat($power_kw_day_sin_taxes_over_10kw->nodeValue);
	$consumption_kw_hour_sin_taxes_over_10kw = checkFormat($consumption_kw_hour_sin_taxes_over_10kw->nodeValue);

	$power_kw_day_con_taxes_under_10kw = checkFormat($power_kw_day_con_taxes_under_10kw->nodeValue);
	$consumption_kw_hour_con_taxes_under_10kw = checkFormat($consumption_kw_hour_con_taxes_under_10kw->nodeValue);
	$power_kw_day_con_taxes_over_10kw = checkFormat($power_kw_day_con_taxes_over_10kw->nodeValue);
	$consumption_kw_hour_con_taxes_over_10kw = checkFormat($consumption_kw_hour_con_taxes_over_10kw->nodeValue);

	#########################
	# Populate the JSON
	#########################
	$json = [
		'power_kw_day_sin_taxes_under_10kw' => $power_kw_day_sin_taxes_under_10kw,
		'consumption_kw_hour_sin_taxes_under_10kw' => $consumption_kw_hour_sin_taxes_under_10kw,
		'power_kw_day_sin_taxes_over_10kw' => $power_kw_day_sin_taxes_over_10kw,
		'consumption_kw_hour_sin_taxes_over_10kw' => $consumption_kw_hour_sin_taxes_over_10kw,
		'power_kw_day_con_taxes_under_10kw' => $power_kw_day_con_taxes_under_10kw,
		'consumption_kw_hour_con_taxes_under_10kw' => $consumption_kw_hour_con_taxes_under_10kw,
		'power_kw_day_con_taxes_over_10kw' => $power_kw_day_con_taxes_over_10kw,
		'consumption_kw_hour_con_taxes_over_10kw' => $consumption_kw_hour_con_taxes_over_10kw,
		'luz_price_valid_from_date' => $luz_price_valid_from_date,
		'timeLastChecked' => $get_timestamp->getTimestamp(),
		'error' => 0,
	];
	
	header('Content-Type: application/json');
	echo json_encode($json);
}else{
	error("cant-scrape-table");
}

#########################
# Functions
#########################
function curlRequest($url){

	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); 

	$res = curl_exec($ch);
	$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
	curl_close($ch);	
	
	return array($res,$http_code) ;
}
function error($errorMessage){
	$get_timestamp = new DateTimeImmutable();
	$jsonResponse = array('error' => '1', 'timeLastChecked' => $get_timestamp->getTimestamp(), 'errorMessage' => $errorMessage);
	header('Content-Type: application/json');
	echo json_encode($jsonResponse);
	die();		
}
function checkFormat($var){
	$var = str_replace(",",".",$var);
	if (!preg_match('/^[0-9.]+$/', $var)) {
		error("validation-check-failed");
	}
	return $var;
}
function checkDateFormat($var){
	if (!preg_match('/^[0-9]+$/', $var)) {
		error("verify-date-failed");
	}
	return $var;
}
function verifyDate($var) {
    $months = array(
        'enero' => '01',
        'febrero' => '02',
        'marzo' => '03',
        'abril' => '04',
        'mayo' => '05',
        'junio' => '06',
        'julio' => '07',
        'agosto' => '08',
        'septiembre' => '09',
        'octubre' => '10',
        'noviembre' => '11',
        'diciembre' => '12'
    );
    preg_match('/(\d{2}) de (\w+) de (\d{4})/', $var, $matches);
    $day = checkDateFormat($matches[1]);
    $month = checkDateFormat($months[$matches[2]]);
    $year = checkDateFormat($matches[3]);
    return array(
        'day' => $day,
        'month' => $month,
        'year' => $year
    );
}

?>