<?php


namespace ArchivarixExternalImagesImporter\Classes;


class ReplaceHelper {

	public static function checkReplace( $string ) {
		$siteHost = UrlHelper::getHost( site_url() );
		if ( false === stripos( $string, $siteHost ) ) {
			return true;
		}

		return false;
	}

	public static function searchReplaceUrls( $search, $replace, $string ) {
		$siteHost = UrlHelper::getHost( site_url() );
		if ( false === stripos( $search, $siteHost ) ) {
			$string = str_replace( $search, $replace, $string );
		}

		return $string;
	}

	public static function replaceAttributeValue( $attr, $replace, $string ) {
		return preg_replace( "/{$attr}=[\"'].*[\"']/iU", "{$attr}='{$replace}'", $string );
	}

	/**
	 * Get html attribute by name
	 *
	 * @param $string
	 * @param $atr
	 *
	 * @return mixed
	 */
	public static function getAttribute( $atr, $string ) {
		preg_match( "~{$atr}=[\"|'](.*)[\"|']\s~imU", $string, $m );
		if ( isset( $m[1] ) ) {
			return $m[1];
		}

		return '';
	}

	public static function setAttribute($atr,$value, $string){
		//TODO regex <\w+(\s)
	}

}
