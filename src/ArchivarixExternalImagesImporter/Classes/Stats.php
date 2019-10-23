<?php


namespace ArchivarixExternalImagesImporter\Classes;


class Stats {

	private $field = 'ArchivarixExternalImagesImporter_stat';

	private $status = false;

	private $obj = [
		'import_start_time'              => 0,
		'all_images'                     => 0,
		'downloaded_image'               => 0,
		'downloaded_image_url'           => 0,
		'downloaded_image_archive'       => 0,
		'downloaded_image_fails'         => 0,
		'downloaded_image_fails_deleted' => 0,
	];

	public function __construct() {

		add_action( 'ArchivarixExternalImagesImporter__statistic-page', [ $this, 'page' ] );
	}

	public function page() {
		$options = get_option( $this->field, $this->obj );
		?>
        <div class="form-table" role="presentation">
			<?php if ( ! empty( $options['import_start_time'] ) ): ?>
                <p>
					<?php _e( 'Time start of import:', 'ArchivarixExternalImagesImporter' ); ?>
                    <em><?php echo $options['import_start_time']; ?></em>
                </p>
                <p><?php _e( 'Number of images:', 'ArchivarixExternalImagesImporter' ); ?>
                    <em><?php echo $options['all_images']; ?></em>
                </p>
                <p><?php _e( 'Number of images downloaded:', 'ArchivarixExternalImagesImporter' ); ?>
                    <em><?php echo $options['downloaded_image']; ?></em>
                </p>
                <p><?php _e( 'Number of images downloaded on source:', 'ArchivarixExternalImagesImporter' ); ?>
                    <em><?php echo $options['downloaded_image_url']; ?></em>
                </p>
                <p><?php _e( 'Number of images downloaded on web archive:', 'ArchivarixExternalImagesImporter' ); ?>
                    <em><?php echo $options['downloaded_image_archive']; ?></em>
                </p>
                <p><?php _e( 'Number of download errors:', 'ArchivarixExternalImagesImporter' ); ?>
                    <em><?php echo $options['downloaded_image_fails']; ?></em>
                </p>
                <p><?php _e( 'Number of deleted images:', 'ArchivarixExternalImagesImporter' ); ?>
                    <em><?php echo $options['downloaded_image_fails_deleted']; ?></em>
                </p>
			<?php else: ?>
                <strong>
					<?php _e( 'No data', 'ArchivarixExternalImagesImporter' ); ?>
                </strong>
			<?php endif; ?>
        </div>
		<?php
	}

	public function statInit() {
		add_action( 'ArchivarixExternalImagesImporter__bath-item', [ $this, 'activate' ], 5, 2 );
		add_action( 'ArchivarixExternalImagesImporter__download-image-start', [ $this, 'onUploadImage' ], 10, 1 );
		add_action( 'ArchivarixExternalImagesImporter__download-image-not-found', [ $this, 'onNotFound' ], 10, 1 );
		add_action( 'ArchivarixExternalImagesImporter__image-string-delete', [ $this, 'onDeleteImage' ], 10, 1 );
		add_action( 'ArchivarixExternalImagesImporter__download-image-end', [ $this, 'deactivate' ], 10, 1 );
		add_action( 'ArchivarixExternalImagesImporter__batch-start', [ $this, 'onStart' ] );
	}

	public function onStart() {
		$this->initObj();
		update_option( $this->field, $this->obj );
	}

	private function initObj() {

		$options = get_option( $this->field, $this->obj );

		$this->obj = array_map( 'intval', $options );

		$this->obj['import_start_time'] = current_time( 'mysql' );
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