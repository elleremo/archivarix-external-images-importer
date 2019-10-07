<?php


namespace ArchivarixExternalImagesImporter\Classes;


use ArchivarixExternalImagesImporter;

class Uploader
{
  private $maxImageWidth;
  private $maxImageHeight;


  public function __construct( $mw = 0, $mh = 0 )
  {
    $this->maxImageWidth  = $mw;
    $this->maxImageHeight = $mh;

    if ( !function_exists( 'media_sideload_image' ) ) {
      require_once( ABSPATH . 'wp-admin/includes/file.php' );
    }

    require_once( ABSPATH . 'wp-admin/includes/image.php' );
    require_once( ABSPATH . 'wp-admin/includes/media.php' );
  }

  public function sideLoadWrapper( $url, $postID = 0, $desc = null )
  {
    $id = media_sideload_image( $url, $postID, $desc, 'id' );

    if ( !is_wp_error( $id ) ) {

      $handler = new ImageFileHandlers( $id );
      $sizes   = $handler->imageSize;

      if ( (int)$sizes['width'] > 0 ) {
        if ( (int)$sizes['width'] > (int)$this->maxImageWidth ) {
          $sizes['width'] = $this->maxImageWidth;
        }
      }

      if ( (int)$sizes['height'] > 0 ) {
        if ( (int)$sizes['height'] > (int)$this->maxImageHeight ) {
          $sizes['height'] = $this->maxImageHeight;
        }
      }

      $handler->resize( $sizes['width'], $sizes['height'] );

      $handler->regenerateAttachmentMeta( $id );

    }

    return $id;
  }

  public function errorHandler( $var )
  {
    if ( is_wp_error( $var ) ) {
      if ( 'http_404' == $var->get_error_code() ) {
        return true;
      }
    }

    return false;
  }

  public function loadInWebArchive( $url, $timestamp = false, $pid = 0 )
  {
    if ( empty( $timestamp ) ) {
      $timestamp = gmdate( 'YmdHis' );
    }

    $url = "http://web.archive.org/web/{$timestamp}id_/{$url}";

    return $this->sideLoadWrapper( $url, $pid );
  }

}
