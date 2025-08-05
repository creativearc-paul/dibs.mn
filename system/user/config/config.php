<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

$config['share_analytics'] = 'n';
$config['require_cookie_consent'] = 'n';
$config['strip_image_metadata'] = 'n';
$config['index_page'] = '';
$config['save_tmpl_files'] = 'y';
// ExpressionEngine Config Items
// Find more configs and overrides at
// https://docs.expressionengine.com/latest/general/system-configuration-overrides.html

$config['app_version'] = '7.5.13';
$config['encryption_key'] = 'a4f79f228b213cdba7c6e6178740a66298b8b204';
$config['session_crypt_key'] = 'c2a7dcdd5f57f86879f947335a2064202854f6c5';
$config['database'] = array(
	'expressionengine' => array(
		'hostname' => 'localhost',
		'database' => 'dibs',
		'username' => 'dibs',
		'password' => '~C_OmIpd7iix3zy8',
		'dbprefix' => 'exp_',
		'char_set' => 'utf8mb4',
		'dbcollat' => 'utf8mb4_unicode_ci',
		'port'     => ''
	),
);
$config['show_ee_news'] = 'y';


// EOF