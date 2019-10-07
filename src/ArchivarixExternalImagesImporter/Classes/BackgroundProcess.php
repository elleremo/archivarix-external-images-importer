<?php


namespace ArchivarixExternalImagesImporter\Classes;


use ArchivarixExternalImagesImporter\Lib\WpBackgroundProcess;

class BackgroundProcess extends WpBackgroundProcess
{

  protected $cron_interval = 1;

  protected $action = 'web-archive-external-picture-schedule';

  /**
   * @inheritDoc
   */
  protected function task( $data )
  {

    do_action( 'ArchivarixExternalImagesImporter__bath-item', $data );

    return false;
  }

  /**
   * Complete
   *
   * Override if applicable, but ensure that the below actions are
   * performed, or, call parent::complete().
   */
  protected function complete()
  {
    do_action( 'ArchivarixExternalImagesImporter__bath-item-success' );
    parent::complete();
  }

}
