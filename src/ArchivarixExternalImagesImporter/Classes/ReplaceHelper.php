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
	 * @param $str
	 * @param $atr
	 *
	 * @return mixed
	 */
	public static function getAttribute( $atr, $str ) {
		preg_match( "~{$atr}=[\"|'](.*)[\"|']\s~imU", $str, $m );
		if ( isset( $m[1] ) ) {
			return $m[1];
		}

		return '';
	}

}