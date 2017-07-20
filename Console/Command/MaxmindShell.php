<?php

App::uses('MaxmindAppShell', 'Maxmind.Console/Command');

class MaxmindShell extends MaxmindAppShell
{
	public $uses = array('Maxmind.Maxmind');
	
	public function startup() 
	{
		$this->clear();
		$this->out('Maxmind Shell');
		$this->hr();
		return parent::startup();
	}
	
	public function getOptionParser()
	{
	/*
	 * Parses out the options/arguments.
	 * http://book.cakephp.org/2.0/en/console-and-shells.html#configuring-options-and-generating-help
	 */
	
		$parser = parent::getOptionParser();
		
		$parser->description(__d('cake_console', 'The Maxmind Shell used to run common jobs for geoip2.'));
		
		$parser->addSubcommand('check_database', array(
			'help' => __d('cake_console', 'Checks to see if the database exists, and updates it if it is older then 3 months.'),
		));
		
		return $parser;
	}
	
	public function check_database()
	{
		$this->Maxmind->checkDatabase();
	}
}