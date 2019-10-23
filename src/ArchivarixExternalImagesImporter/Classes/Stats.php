<?php


namespace ArchivarixExternalImagesImporter\Classes;


class Stats {

	private $field = 'ArchivarixExternalImagesImporter_stat';

	private $status = false;

	private $obj = [
		'all_images'                     => 0,
		'downloaded_image'               => 0,
		'downloaded_image_url'           => 0,
		'downloaded_image_archive'       => 0,
		'downloaded_image_fails'         => 0,
		'downloaded_image_fails_deleted' => 0,
	];

	public function __construct() {
		add_action( 'ArchivarixExternalImagesImporter__bath-item', [ $this, 'activate' ], 5, 2 );
		add_action( 'ArchivarixExternalImagesImporter__download-image-start', [ $this, 'onUploadImage' ], 10, 1 );
		add_action( 'ArchivarixExternalImagesImporter__download-image-not-found', [ $this, 'onNotFound' ], 10, 1 );
		add_action( 'ArchivarixExternalImagesImporter__image-string-delete', [ $this, 'onDeleteImage' ], 10, 1 );
		add_action( 'ArchivarixExternalImagesImporter__download-image-end', [ $this, 'deactivate' ], 10, 1 );
	}

	private function initObj() {
		$this->obj = array_map( 'intval', get_option( $this->field, $this->obj ) );
	}

	public function onDeleteImage( $string ) {
		$this->obj['downloaded_image_fails_deleted'] = $this->obj['downloaded_image_fails_deleted'] + 1;
	}

	public function onNotFound( $url ) {
		$this->obj['downloaded_image_fails'] = $this->obj['downloaded_image_fails'] + 1;
	}

	public function onUploadImage( $url ) {
		$this->obj['downloaded_image'] = $this->obj['downloaded_image'] + 1;

		if ( strstr( $url, 'web.archive.org' ) ) {
			$this->obj['downloaded_image_archive'] = $this->obj['downloaded_image_archive'] + 1;
		} else {
			$this->obj['downloaded_image_url'] = $this->obj['downloaded_image_url'] + 1;
		}

	}

	public function activate( $string, $batch ) {
		$this->initObj();
		$this->status = true;

		$this->obj['all_images'] = count( $batch );
	}

	public function deactivate( $url ) {
		if ( $this->status ) {
			update_option( $this->field, $this->obj );
		}
	}

}