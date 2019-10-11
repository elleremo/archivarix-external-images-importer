<?php

namespace ArchivarixExternalImagesImporter\Classes;


use ArchivarixExternalImagesImporter\Background\BackgroundProcess;

class Batch {

	private $options;

	private $process;

	private $stack = [];

	public function __construct( $options ) {

		$this->options = $options;


		if ( ! $this->process ) {
			$this->process = new BackgroundProcess();
		}

		$this->BackgroundProcessButton();

		add_action( 'admin_notices', [ $this, 'BackgroundProcessIndicator' ], 20 );

	}

	public function BackgroundProcessIndicator() {

		if ( $this->process->is_process_running() ):?>
            <div class="notice notice-info ">
                <p><?php _e( 'Background processing is happening now.', 'my-text-domain' ); ?></p>
            </div>
		<?php
		endif;
	}

	public function BackgroundProcessButton() {

		if ( isset( $_GET['ArchivarixExternalImagesImporter-batch'] ) ) {

			if ( current_user_can( 'manage_options' ) ) {
				$this->publishPosts();
			}
		}
	}

	public function publishPosts() {

		$types = $this->options->getOption( 'posts_types', false );
		if ( ! empty( $types ) ) {


			$posts = $this->getPostsContainPictures();

			foreach ( $posts as $post ) {

				preg_match_all( '/<img[^>]*>/im', $post->post_content, $images, PREG_SET_ORDER );

				if ( ! empty( $images ) ) {
					if ( UrlHelper::checkExternalImages( $images ) ) {
						$this->postImages( $post->ID );
					}
				}
			}
//
//			if ( ! empty( $this->stack ) ) {
//
//				foreach ( $this->stack as $item ) {
//					$this->process->push_to_queue( $item );
//				}
//
//				$this->process->save();
//				$this->process->dispatch();
//			}

		}
	}


	private function getPostsContainPictures() {
		global $wpdb;

		$types = $this->options->getOption( 'posts_types', false );
		$types = array_map( function ( $val ) {
			$val = trim( $val );
			$val = "'$val'";

			return $val;
		}, $types );
		$types = implode( ',', $types );

		$query = "
            SELECT ID, post_content
            FROM $wpdb->posts
            WHERE `post_type` IN ({$types}) AND 
            `post_status` != 'inherit'
            AND `post_content` REGEXP '<img.*(?!.*heroine\.lc.*).*>'            
        ";

		$query = trim( $query );

		$data = $wpdb->get_results( $query );

		return $data;
	}

	public function postImages( $id ) {
		$post = get_post( $id );

		if ( is_object( $id ) ) {
			$post = $id;
		}

		$content = $post->post_content;

		$helper = new ExtractHelpers();
		$data   = $helper->getImagesData( $content );

		foreach ( $data as $item ) {
			$this->stack[] = serialize( [ 'item' => $item, 'post' => $id ] );
		}

	}

}
