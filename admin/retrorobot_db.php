<?php
define('RETROROBOT_DB_VERSION', 1);

/*----------------*/
/*----------------*/
/*----------------*/
class retrorobot_db {

  /*----------------*/
  public static function checkDB() {
    $sql = "SELECT meta_value FROM rr_meta WHERE meta_key = 'db_version'";
    @$row = key_dblite::getDBRow($sql);

    $dbVersion = -1;
    if ($row !== null) {
      $dbVersion = isset($row['meta_value']) ? intval($row['meta_value']) : -1;
    }

    if ($dbVersion < 1) {
      static::createTables();
    } else if ($dbVersion < RETROROBOT_DB_VERSION) {
      static::migrateDB($dbVersion);
    }
  }

  /*----------------*/
  public static function createTables() {
    $creationError = '';

    $creationScripts = array(
      static::$TABLE_META,
      sprintf("REPLACE INTO rr_meta (meta_key, meta_value) values ('db_version', '%d')", RETROROBOT_DB_VERSION)
    );

    foreach($creationScripts as $creationScript) {
      if (!kdb::execSQL($creationScript)) {
        $creationError = 'Unable to exec : <br>' . $creationScript . '<br>';
      }
    }

    if ($creationError != '') {
      die($creationError);
    }
  }

  /*----------------*/
  public static function migrateDB($dbVersion) {
    //TODO when migrations needed
  }

  /*----------------*/
public static $TABLE_META = <<<TABLE_META
  CREATE TABLE IF NOT EXISTS `rr_meta` (
    `meta_key` TEXT NOT NULL,
    `meta_value` TEXT,
    PRIMARY KEY(`meta_key`)
  );
TABLE_META;

  /*----------------*/
}

/*----------------*/
?>