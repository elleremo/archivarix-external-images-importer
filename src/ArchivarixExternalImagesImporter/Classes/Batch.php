<?php

namespace ArchivarixExternalImagesImporter\Classes;


class Batch
{
  private $options;
  private $process;

  public function __construct( $options )
  {

    $this->options = $options;

    $this->process = new BackgroundProcess();

    add_action( 'ArchivarixExternalImagesImporter__bath-item-success', [$this, 'successBatch'] );

    add_action( 'ArchivarixExternalImagesImporter__options-change', [$this, 'startBackgroundProcess'], 10, 3 );

    add_action( 'admin_notices', [$this, 'BackgroundProcessIndicator'], 20 );

    add_action( 'ArchivarixExternalImagesImporter__background-process-start-link', [
      $this,
      'BackgroundProcessButtonLink',
    ] );

    $this->BackgroundProcessButton();
  }


  public function BackgroundProcessButtonLink()
  {
    ?>
    <a href="<?php echo wp_nonce_url(
      '?page=ArchivarixExternalImagesImporter&ArchivarixExternalImagesImporter-batch',
      'ArchivarixExternalImagesImporter'
    ); ?>"
       class="button ">
      <?php _e( 'Background processing', 'ArchivarixExternalImagesImporter' ); ?>
    </a>
    <?php
  }

  public function BackgroundProcessButton()
  {

    if ( isset( $_GET['ArchivarixExternalImagesImporter-batch'] ) ) {
      if ( current_user_can( 'manage_options' ) ) {
        if ( wp_verify_nonce( $_GET['_wpnonce'], 'ArchivarixExternalImagesImporter' ) ) {
          $option = $this->options->getOption( 'background_replace', 'off' );

          if ( 'off' == $option ) {
            $this->publishPosts();
            $this->options->updateOption( 'background_replace', 'on' );
          }
        }
      }
    }
  }

  public function BackgroundProcessIndicator()
  {
    $option = $this->options->getOption( 'background_replace', 'off' );
    $screen = get_current_screen();

    if ( 'on' == $option && 'settings_page_ArchivarixExternalImagesImporter' == $screen->base ):
      ?>
      <div class="notice notice-info ">
        <p><?php _e( 'Background processing is happening now.', 'my-text-domain' ); ?></p>
      </div>
    <?php
    endif;
  }

  public function successBatch()
  {

    $this->options->updateOption( 'background_replace', 'off' );
  }

  public function publishPosts()
  {

    $types = $this->options->getOption( 'posts_types', false );
    if ( !empty( $types ) ) {

      $args  = [
        'post_type' => $types,
        'posts_per_page' => -1,
      ];
      $posts = get_posts( $args );

      $i = 0;

      foreach ( $posts as $post ) {
        preg_match_all( '/<img[^>]*>/im', $post->post_content, $images, PREG_SET_ORDER );

        if ( !empty( $images ) ) {
          if ( $this->checkExternalImages( $images ) ) {
            $i++;
            $this->postImages( $post->ID );
          }
        }
      }

      if ( empty( $i ) ) {
        $this->options->updateOption( 'background_replace', 'off' );
      }
    }
  }

  private function checkExternalImages( $images )
  {
    foreach ( $images as $image ) {
      $image = ( is_array( $image ) && !empty( $image ) ) ? $image[0] : false;

      if ( empty( $image ) ) {
        return false;
      }

      if ( $this->checkExternalImage( $image ) ) {
        return true;
      }
    }

    return false;
  }

  private function checkExternalImage( $image )
  {

    $host = UrlHelper::getHost( home_url() );

    $src = str_replace(
      ['http://', 'https://'],
      '',
      ReplaceHelper::getAttribute( 'src', $image )
    );

    if ( 0 !== strpos( $src, $host ) ) {
      return true;
    }

    return false;
  }

  public function postImages( $id )
  {
    $post = get_post( $id );

    if ( is_object( $id ) ) {
      $post = $id;
    }

    $helper = new ExtractHelpers();
    $data   = $helper->getImagesData( $post->post_content );

    foreach ( $data as $item ) {
      $this->process->push_to_queue( ['item' => $item, 'ID' => $post->ID] );
    }

    $this->process->save();
    $this->process->dispatch();
  }

}
