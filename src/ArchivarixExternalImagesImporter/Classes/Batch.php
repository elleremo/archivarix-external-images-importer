<?php

namespace ArchivarixExternalImagesImporter\Classes;


use ArchivarixExternalImagesImporter\Background\BackgroundProcess;

class Batch {

	private $options;

	private $process;

	private $stack = [];

	private $excludeDomains = [];

	public function __construct( $options ) {

		$this->excludeDomains = $this->getExcludeDomains( $options->getOption( 'exclude_domains', false ) );
		$this->options        = $options;
		$this->process        = new BackgroundProcess();

		add_action( 'admin_init', [ $this, 'BackgroundProcessButton' ] );
		add_action( 'admin_notices', [ $this, 'BackgroundProcessIndicator' ], 20 );
		add_filter( 'ArchivarixExternalImagesImporter__background-process-running', [ $this, 'processRunningFilter' ] );
	}

	public function processRunningFilter( $status ) {
		if ( $this->process->is_process_running() ) {
			return true;
		}

		return false;
	}

	private function getExcludeDomains( $string ) {
		$exclude   = UrlHelper::getExcludeDomains( $string );

		$exclude   = array_diff( $exclude, [ '' ] );

		return array_map( function ( $val ) {
			return str_replace( '.', '\\.', $val );
		}, $exclude );
	}

	public function BackgroundProcessIndicator() {

		if ( $this->process->is_process_running() ):?>
            <div class="notice notice-info ">
                <p><?php
					echo sprintf(
						__(
							'Background processing is happening now. %d images left.',
							'ArchivarixExternalImagesImporter'
						),
						$this->process->remain()
					); ?></p>
            </div>
		<?php
		endif;
	}

	public function BackgroundProcessButton() {

		if ( isset( $_GET['ArchivarixExternalImagesImporter-batch'] ) ) {
			if ( current_user_can( 'manage_options' ) ) {
				if ( ! $this->process->is_process_running() ) {
					$this->publishPosts();

					$url = add_query_arg(
						[ 'page' => 'ArchivarixExternalImagesImporter' ],
						admin_url( 'options-general.php' )
					);

					do_action( 'ArchivarixExternalImagesImporter__batch-start' );

					wp_redirect( $url, 303 );
				}
			}
		}
	}

	public function publishPosts() {
		$excludeDomainsString = implode( '|', $this->excludeDomains );
		$types                = $this->options->getOption( 'posts_types', false );

		if ( ! empty( $types ) ) {

			$posts = $this->getPostsContainPictures();

			foreach ( $posts as $post ) {

				preg_match_all(
					"/<img(?!.*{$excludeDomainsString}.*).*>/im",
					$post->post_content,
					$images,
					PREG_SET_ORDER );

				if ( ! empty( $images ) ) {
					if ( UrlHelper::checkExternalImages( $images ) ) {
						$this->postImages( $post->ID );
					}
				}
			}

			if ( ! empty( $this->stack ) ) {

				foreach ( $this->stack as $item ) {
					$this->process->push_to_queue( $item );
				}

				$this->process->save();
				$this->process->dispatch();
			}

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
            AND `post_content` REGEXP '<img.*>';          
        ";

		$query = trim( $query );

		return $wpdb->get_results( $query );
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
