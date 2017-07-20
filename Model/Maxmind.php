<?php

App::uses('MaxmindAppModel', 'Maxmind.Model');
App::uses('CakeSession', 'Model/Datasource');

class Maxmind extends MaxmindAppModel 
{
	public $useTable = false;

	public $actsAs = array(
		'Maxmind.Geoip', 
    );
	
	public function checkDatabase()
	{
		return $this->Geoip_checkDatabase();
	}
}