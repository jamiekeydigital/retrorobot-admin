<?php
/*----------------*/
class ks  extends key_session {}
class kdb extends key_dblite {}
class kf  extends key_file {}

/*----------------*/
/*----------------*/
/*----------------*/
class retrorobot_utils {

  /*----------------*/
  private static $metaData = array();

  /*----------------*/
  function createDirectories() {
    kf::createFolder(RETRO_ROBOT_ROMS_DIR);
    kf::createFolder(RETRO_ROBOT_OVERLAYS_DIR);
  }

  /*----------------*/
  public static function loadMetaData() {
    $sql = "SELECT * FROM rr_meta";
    if (key_dblite::openQuery($sql)) {
      while(key_dblite::row()) {
        $value = key_dblite::rowString('meta_value');
        $key = key_dblite::rowString('meta_key');
        if (($value != '') && ($key != '')) {
          static::$metaData[$key] = $value;
        }
      }
    }
  }

  /*----------------*/
  public static function metaString($key, $dft = '') {
    $val = isset(static::$metaData[$key]) ? static::$metaData[$key] : '';
    return ($val == '') ? $dft : $val;
  }

  /*----------------*/
  public static function metaInt($key, $dft = 0) {
    $val = isset(static::$metaData[$key]) ? static::$metaData[$key] : '';
    return ($val == '') ? $dft : intval($val);
  }

  /*----------------*/
}

/*----------------*/
?>