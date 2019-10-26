<?php
/*
Plugin Name: Archivarix External Images Importer plugin
Plugin URI: https://en.archivarix.com/archivarix-external-images-importer/
Author: Archivarix
Author URI: http://en.archivarix.com
Text Domain: ArchivarixExternalImagesImporter
Domain Path: /languages
Requires PHP: 7.0
Version: 1.0.0
License: GPLv3
*/

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once( plugin_dir_path( __FILE__ ) . "includes/Autoloader.php" );

if ( file_exists( plugin_dir_path( __FILE__ ) . "vendor/autoload.php" ) ) {
	require_once( plugin_dir_path( __FILE__ ) . "vendor/autoload.php" );
}

use ArchivarixExternalImagesImporter\Admin\Data;
use ArchivarixExternalImagesImporter\Admin\Settings;
use ArchivarixExternalImagesImporter\Autoloader;

new Autoloader( __FILE__, 'ArchivarixExternalImagesImporter' );

use ArchivarixExternalImagesImporter\Base\Wrap;
use ArchivarixExternalImagesImporter\Classes\AddCdn;
use ArchivarixExternalImagesImporter\Classes\Batch;
use ArchivarixExternalImagesImporter\Classes\InsertPostHook;
use ArchivarixExternalImagesImporter\Classes\Renamer;
use ArchivarixExternalImagesImporter\Classes\Stats;

class ArchivarixExternalImagesImporter extends Wrap {
	public $version = '1.0.0';
	public static $textdomain;
	public $filePath;
	public $options;

	public function __construct() {
		$this->filePath   = __FILE__;
		self::$textdomain = $this->setTextdomain();

		new Settings( $this );
		$this->options = new Data( $this );
		new Renamer( $this->options );
		$stats = new Stats();

		if ( 'on' == $this->options->getOption( 'temporarily_disable_auto_download' ) ) {
			$insertClass = new InsertPostHook( $this->options );
			$insertClass->applySavePostFilter();
			$stats->statInit();
			new addCdn( $this->options );
			new Batch( $this->options );
		}

	}

}

function ArchivarixExternalImagesImporter__init() {
	new ArchivarixExternalImagesImporter();
}

add_action( 'plugins_loaded', 'ArchivarixExternalImagesImporter__init', 30 );
