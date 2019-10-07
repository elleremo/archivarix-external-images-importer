<?php

namespace ArchivarixExternalImagesImporter\Classes;

class UrlHelper
{

  /**
   * Получает имя хоста из url строки и возвращет его,
   * если воспользоваться опциональным аргументом то
   * еще и протокол добавит к хосту.
   *
   * @param $url
   * @param bool $scheme
   *
   * @return bool|string
   */
  public static function getHost( $url, $scheme = false )
  {
    $data = parse_url( $url );

    if ( !isset( $data['host'] ) ) {
      return false;
    }

    if ( !$scheme ) {
      return $data['host'];
    }

    return "{$data['scheme']}://{$data['host']}";
  }

  /**
   * Получает basename от url исключая попадание в него get параметров
   * @param $url
   *
   * @return string
   */
  public function getImageName( $url )
  {
    $path = parse_url( $url );

    return basename( $path['path'] );
  }

}
