<?php
/*
* Common PHP SQLite DB Utils Class for Key.Digital Agency
*
* = Licence =
* Copyright (C) Key Digital Agency Limited - All Rights Reserved
* Unauthorized copying of this file, via any medium is strictly prohibited
* For proprietary use within the Key.Digital web projects, and development.
* 
* = Change Log =
* Creation : Jamie McDonald : 7th May 2019
*/

/*----------------------------*/
/*--KEY_DBLITE----------------*/
/*----------------------------*/
if (!class_exists('key_dblite')) {  
 
  abstract class key_dblite {
    public static $VERSION = 1;

    /*-----*/
    private static $dbConn = null;
    private static $dbResult = false;
    private static $dbRow = null;
    private static $dieOnConnectFail = true;
    private static $errors = array();

    /*-----*/
    public static function connection() {
      return self::$dbConn;
    }

    /*-----*/
    public static function setExternalConnection($connection) {
      self::$dbConn = $connection;
    }

    /*-----*/
    public static function connected() {
      return self::$dbConn != null;
    }

    /*-----*/
    public static function hasError() {
      return count(self::$errors) > 0;
    }

    /*-----*/
    public static function lastError() {
      if (self::hasError()) {
        return end(self::$errors);
      }
      return '';
    }

    /*-----*/
    private static function error($message) {
      self::$errors[] = $message;

      if (class_exists('key_session')) {
        key_session::globalDie($message, self::$dieOnConnectFail);
      } else if (self::$dieOnConnectFail) {
        $res = '<strong>Errors: </strong><br/>';
        foreach(self::$errors as $key => $error) {
          $res .= $key . ' = ' . $error . '<br/>';
        }

        die($res);
      }
    }

    /*-----*/
    public static function connect($path = '', $flags = -1) {
      $dbPath  = ($path != '')  ? $path  : (defined('DB_PATH') ? DB_PATH  : '');
      $dbFlags = ($flags != -1) ? $flags : (defined('DB_FLAGS') ? DB_FLAGS : SQLITE3_OPEN_READWRITE | SQLITE3_OPEN_CREATE);
  
      if ($dbPath == '') {
        self::error('Invalid connection information for database'); 
        self::$dbConn = null;     
        return false;
      }
  
      try {
        self::$dbConn = new SQLite3($dbPath, $dbFlags);
      } catch (Exception $e) {
        self::error('Connect Error: ' .  $e->getMessage());  
        self::$dbConn = null; 
      }

      return self::connected();
    }

    /*-----*/
    public static function disconnect() {
      self::closeQuery();     
  
      if (self::$dbConn  != null) {
        self::$dbConn->close();
        self::$dbConn = null;
      }
    }
  
    /*-----*/
    public static function dontDieOnConnect($shouldDie = false) {
      self::$dieOnConnectFail = $shouldDie;
    }

    /*-----*/
    public static function blindConnect() {
      self::dontDieOnConnect();
      return self::connect();
    }

    /*-----*/
    public static function sanitiseValue($value){
      if (self::$dbConn === null) {
        return $value;
      }
      return self::$dbConn->escapeString($value);  
    }

    /*-----*/
    public static function execSQL($sql) {
      if ($sql == '') {
        return false;
      }
  
      if (!self::connected()) {
        if (!self::blindConnect()){
          return false;
        }
      }    
      return self::$dbConn->exec($sql);
    }

    /*-----*/
    public static function lastId(){
    	return (self::connected()) ? self::$dbConn->lastInsertRowID() : -1;
    }

    /*-----*/
    public static function openQuery($sql){
      self::closeQuery();
  
      if ($sql == '') {
        return false;
      }
  
      if (!self::connected()) {
        if (!self::blindConnect()){
          return false;
        }
      }   
  
      self::$dbResult = self::$dbConn->query($sql);     
      return self::$dbResult !== false;    
    }

    /*-----*/
    public static function closeQuery() {
      self::$dbRow = null;
  
      if ((self::$dbResult !== false) && (self::$dbResult !== true)) {      
        self::$dbResult->finalize();
      }
      self::$dbResult = false;
    }

    /*-----*/  
    public static function queryRowCount() {
      if (self::$dbResult === false) {
        return 0;
      }
      $numOfRows = 0;
      self::$dbResult->reset();
      while ($self::$dbResult->fetchArray()) {
        $numOfRows++;
      }
      self::$dbResult->reset();
      return $numOfRows;
    }

    /*-----*/
    public static function getQueryRow($colNamesToLower = true) {
      if (self::$dbResult === false) {
        return null;
      } 
      
      $arr = self::$dbResult->fetchArray();
      if (($arr !== null) && ($arr !== false) && ($colNamesToLower)) {
        $arr = array_change_key_case($arr);
      }
      return $arr;
    }

    /*-----*/
    public static function row() {
      self::$dbRow = self::getQueryRow();
      return self::hasRow();
    }

    /*-----*/
    public static function hasRow() {
      return self::$dbRow != null && self::$dbRow !== false;
    }

    /*-----*/
    public static function getDBRow($sql) {
      if (self::openQuery($sql)) {
        return self::getQueryRow();
      }
      return null;
    }
  
    /*-----*/
    public static function setResultRow($int) {
      if ((self::$dbResult !== false) && ($int < self::queryRowCount())) {
        self::$dbResult->reset();
        $current = 0;
        while(self::row() && ($current < $int)){
          $current++;
        } 
      }
    }
  
    /*-----*/
    public static function rowString($colName, $default = '') {
      if (!self::hasRow() || !is_array(self::$dbRow)) {
        return $defualt;
      }
  
      return (isset(self::$dbRow[$colName])) ? self::$dbRow[$colName] : $default;
    }

    /*-----*/
    public static function rowInt($colName, $default = 0) {
      if (!self::hasRow() || !is_array(self::$dbRow)) {
        return $defualt;
      }
  
      return (isset(self::$dbRow[$colName])) ? intval(self::$dbRow[$colName]) : $default;
    }

    /*-----*/
    public static function rowBool($colName, $default = false) {
      if (!self::hasRow() || !is_array(self::$dbRow)) {
        return $defualt;
      }
      $stringVal = (isset(self::$dbRow[$colName])) ? strval(self::$dbRow[$colName]) : '';
      return (($stringVal == '1') || (strtolower($stringVal) == 'true')) ? true : $default;
    }

    /*-----*/
    public static function rowTime($colName, $default = 0) {
      if (!self::hasRow() || !is_array(self::$dbRow)) {
        return $defualt;
      }

      return (isset(self::$dbRow[$colName])) ? strtotime(self::$dbRow[$colName]) : $default;
    }

    /*-----*/
  }
}
?>