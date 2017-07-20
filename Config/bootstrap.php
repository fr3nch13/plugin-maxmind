<?php


CakeLog::config('geoip', array(
	'engine' => 'FileLog',
	'mask' => 0666,
	'size' => 0, // disable file log rotation, handled by logrotate
	'types' => array('info', 'notice', 'error', 'warning', 'debug'),
	'scopes' => array('geoip'),
	'file' => 'geoip.log',
));