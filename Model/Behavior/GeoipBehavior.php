<?php

require_once ROOT. DS. 'Vendor'. DS. 'autoload.php';


use GeoIp2\Database\Reader;

class GeoipBehavior extends ModelBehavior 
{
	// Map lookups to geoip's lookups
	public $mapMethods = array('/Geoip(\w+)/' => 'GeoipMap');
	
	public $settings = array();
	
	public $defaults = array(
		'path_database' => false,
		'path_database_gz' => false,
		'path_database_url' => 'http://geolite.maxmind.com/download/geoip/database/GeoLite2-City.mmdb.gz',
		'database_expired' => '-3 months',
	);
	
	public $Reader = false;
	
	public $Curl = false;
	
	public function setup(Model $Model, $settings = array())
	{
		if (!isset($this->settings[$Model->alias]))
		{
			$this->settings[$Model->alias] = array(
				'path_database' => TMP. 'GeoLite2-City.mmdb',
			);
			$this->settings[$Model->alias]['path_database_gz'] = $this->settings[$Model->alias]['path_database'].'.gz';
		}
		$this->settings[$Model->alias] = array_merge($this->defaults, $this->settings[$Model->alias], (array)$settings);
		
		
		return true;
	}
	
	public function GeoipMap(Model $Model, $method = '', $arg1 = false, $arg2 = false) 
	{
		if(!trim($arg1))
		{
			$Model->modelError = __('Unknown Ipaddress');
			return false;
		}
		
		$arg1 = trim($arg1);
		
		if(!$this->Reader)
		{
			$this->Reader = new GeoIp2\Database\Reader($this->settings[$Model->alias]['path_database']);
		}
		
		try {
			if(!$record = $this->Reader->city($arg1))
			{
				$Model->modelError = __('No Geoip records found.');
				return false;
			}
		}
		catch(Exception $e)
		{
			$Model->modelError = $e->getMessage();
			return false;
		}
		
		$out = array(
			'country_name' => $record->country->name,
			'country_iso' => $record->country->isoCode,
			'region_name' => $record->mostSpecificSubdivision->name,
			'region_iso' => $record->mostSpecificSubdivision->isoCode,
			'city' => $record->city->name,
			'postal_code' => $record->postal->code,
			'latitude' => $record->location->latitude,
			'longitude' => $record->location->longitude,
		);
		
		switch($method)
		{
			case 'GeoipCountryName':
				$out = $out['country_name'];
				break;
			case 'GeoipCountryIso':
				$out = $out['country_iso'];
				break;
			case 'GeoipRegionName':
				$out = $out['region_name'];
				break;
			case 'GeoipRegionIso':
				$out = $out['region_iso'];
				break;
			case 'GeoipCity':
				$out = $out['city'];
				break;
			case 'GeoipPostalCode':
				$out = $out['postal_code'];
				break;
			case 'GeoipLatitude':
				$out = $out['latitude'];
				break;
			case 'GeoipLongitude':
				$out = $out['longitude'];
				break;
		}
		return $out;
	}
	
	public function Geoip_checkDatabase(Model $Model)
	{
		$download = false;
		if(!file_exists($this->settings[$Model->alias]['path_database']))
		{
			$Model->shellOut(__('Database dosn\'t exist: %s', $this->settings[$Model->alias]['path_database']), 'geoip');
			$download = true;
		}
		else
		{
			$database_time = filemtime($this->settings[$Model->alias]['path_database']);
			$expired_time = strtotime($this->settings[$Model->alias]['database_expired']);
			
			if($database_time < $expired_time)
				$download = true;
		}
		
		if(!$download)
			return true;
		
		if(!$this->Curl)
		{
			// load the curl object
			$Model->shellOut(__('Loading cUrl.'), 'geoip', 'info');
			App::import('Vendor', 'Utilities.Curl');
			$this->Curl = new Curl();
		}
		
		$Model->shellOut(__('Downloading Database from: %s', $this->settings[$Model->alias]['path_database_url']), 'geoip');
		$this->Curl->url = $this->settings[$Model->alias]['path_database_url'];
			
		if(!$content = $this->Curl->execute())
		{
			$Model->modelError = __('Unable to download the Geoip database file: %s', $this->settings[$Model->alias]['path_database_url']);
			$Model->shellOut($Model->modelError, 'geoip', 'error');
			return false;
		}
		
		$gz_path = $this->settings[$Model->alias]['path_database_gz'];
			
		if(!file_put_contents($this->settings[$Model->alias]['path_database_gz'], $content))
		{
			$Model->modelError = __('Unable to save the compressed Geoip database file: %s - to: %s', $this->settings[$Model->alias]['path_database_url'], $this->settings[$Model->alias]['path_database_gz']);
			$Model->shellOut($Model->modelError, 'geoip', 'error');
			return false;
		}
		
		$command = 'gunzip '. $this->settings[$Model->alias]['path_database_gz'];
		
		$retval = false;
		$results = system($command, $retval);
		
		if(!is_readable($this->settings[$Model->alias]['path_database']))
		{
			$Model->modelError = __('Unable to decompress the Geoip database file: %s', $gz_path);
			$Model->shellOut($Model->modelError, 'geoip', 'error');
			return false;
		}
		
		$Model->shellOut(__('Database Updated, saved to: %s', $this->settings[$Model->alias]['path_database']), 'geoip');
	}
}