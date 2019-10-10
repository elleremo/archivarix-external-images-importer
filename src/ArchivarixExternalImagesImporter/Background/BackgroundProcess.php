<?php


namespace ArchivarixExternalImagesImporter\Background;


use ArchivarixExternalImagesImporter\Lib\WpBackgroundProcess;

class BackgroundProcess extends WpBackgroundProcess {

	protected $cron_interval = 1;

	protected $action = 'web-archive-external-picture-schedule';

	/**
	 * @inheritDoc
	 */
	protected function task( $data ) {
		do_action( 'ArchivarixExternalImagesImporter__bath-item', $data );

		return false;
	}

	/**
	 * Complete
	 *
	 * Override if applicable, but ensure that the below actions are
	 * performed, or, call parent::complete().
	 */
	protected function complete() {
		parent::complete();

		set_site_transient( $this->identifier . '_process_completed', microtime() );
	}


	public function is_process_completed() {
		if ( get_site_transient( $this->identifier . '_process_completed' ) ) {
			delete_site_transient( $this->identifier . '_process_completed' );

			return true;
		}

		return false;
	}

	public function is_process_running() {
		$is_process_running = parent::is_process_running();

		return $is_process_running;
	}

}