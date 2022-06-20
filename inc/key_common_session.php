<?php
/*
* Common PHP Session Utils Class for Key.Digital Agency
*
* = Licence =
* Copyright (C) Key Digital Agency Limited - All Rights Reserved
* Unauthorized copying of this file, via any medium is strictly prohibited
* For proprietary use within the Key.Digital web projects, and development.
* 
* = Change Log =
* Creation : Jamie McDonald : 6th Novemeber 2018
* Bug      : Jamie McDonald : 15th Feb 2019 - Protection if two of the common utils are included
*/

/*----------------------------*/
$key_common_debug_path = dirname(__FILE__) . '/key_common_debug.php';
if (file_exists($key_common_debug_path)) {
  require_once($key_common_debug_path);  
} 

/*----------------------------*/
/*--KEY_SESSION---------------*/
/*----------------------------*/
if (!class_exists('key_session')) {
  
  /*------------------*/
  define('RETURN_AS_IS',      0);
  define('RETURN_LOWER_CASE', 1);
  define('RETURN_UPPER_CASE', 2);

  define('KEY_MINUTE_IN_SECONDS', 60);
  define('KEY_HOUR_IN_SECONDS', 3600);
  define('KEY_DAY_IN_SECONDS', 86400);

  /*------------------*/
  abstract class key_session {
    public static $VERSION = 16;

    /*------------------*/
    public static $globalError = '';

    /*------------------*/
    public static function isDebug() {
      return class_exists('key_debug') && key_debug::isDebug();
    }

    /*------------------*/
    public static function debugPrint($object, $nonDebug = false) {
      if ($nonDebug || self::isDebug()) {
        die('Key Global Print: <br/><pre>' . print_r($object, true) . '</pre>');
      }
    }

    /*------------------*/
    public static function globalDie($newError = '', $shouldDie = true) {
      if ($newError != '') {
        self::$globalError = $newError;
      }
  
      if ($shouldDie) {
        die(self::$globalError);
      }
    }

    /*------------------*/
    public static function hasError() {
      return self::$globalError != '';
    }
  
    /*------------------*/
    public static function error() {
      return self::$globalError;
    }

    /*------------------*/
    public static function startOrRestartSession() {
      if (session_status() !== PHP_SESSION_NONE) {
        session_write_close();
      }
      session_start();
    }

    /*-----------------*/
    public static function putCookie($key, $value, $expiryDays = 364, $path = '/') {
      $encoded = urlencode($value);
      $cookieExpiry = time() + (86400 * $expiryDays);
      setcookie($key, $encoded, $cookieExpiry, $path);
      $_COOKIE[$key] = $encoded;
    }

    /*-----------------*/
    public static function putDelayedCookie($key, $value, $delaySeconds = 0, $expiryDays = 364, $path = '/') {      
      $validFrom = strtotime('+' . $delaySeconds . ' secs');
      $cookieValue = serialize(array('value' => $value, 'validFrom' => $validFrom));
      self::putCookie($key, $cookieValue, $expiryDays, $path);
    }

    /*-----------------*/
    public static function getCookie($key, $dft = '') {
      return isset($_COOKIE[$key]) ? urldecode($_COOKIE[$key]) : $dft;
    }

    /*-----------------*/
    public static function getDelayedCookie($key, $dft = '') {
      $value = self::getCookie($key);
      if ($value != '') {
        $unSerialized = unserialize($value); 
        if (($unSerialized !== false) && is_array($unSerialized)) {
          $current = time();
	        $toCheck = isset($unSerialized['validFrom']) ? $unSerialized['validFrom'] : $current;
	        if ($current >= $toCheck) {
	          return isset($unSerialized['value']) ? $unSerialized['value'] : $dft;
          }
        }
      }
      return $dft;
    }

    /*------------------*/
    public static function getCurrentURI($removeArgs = false) {
      $url = (isset($_SERVER['HTTPS']) && ($_SERVER['HTTPS'] === 'on')) ? 'https://' : 'http://';

      $url .= $_SERVER['HTTP_HOST'];
      $url .= $_SERVER['REQUEST_URI'];

      if (($removeArgs) && (strpos($url, '?') !== false)) {
        $urlArr = explode('?', $url);
        if (count($urlArr) > 0) {
          $url = $urlArr[0];
        }
      }
      return rtrim($url, '/');
    }
  
    /*------------*/
    public static function getCurrentOriginalUrl(){
      return trim((isset($_SERVER['HTTP_X_ORIGINAL_URL']) ? $_SERVER['HTTP_X_ORIGINAL_URL'] : ($_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'])), '/');
    }

    /*-----------------*/
    public static function getRootFolderFromUrl($url) {
      $toBreak = str_replace(array('https://', 'http://'), '', $url);
      $splitUrl = explode('/', $toBreak); 
      if (count($splitUrl) > 1) {
        unset($splitUrl[0]);
        return '/' . implode('/', $splitUrl);
      }
      return '';
    }

    /*------------*/
    public static function headerRedirect($url) {     
      if (session_status() !== PHP_SESSION_NONE) {
        session_write_close();
      }
    
      if ($url != '') {
        header('Location: ' . $url);
        die;
      }
    }

    /*--------------*/
    public static function getClientIPAddress() {
      if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        $ip = $_SERVER['HTTP_CLIENT_IP'];
      } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
      } else {
        $ip = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '';
      }
      return $ip;
    }
  
    /*--------------*/
    public static function getClientUserAgent() {
      return isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : 'UNKNOWN';
    }

    /*--------------*/
    private static function returnValue($value, $caseChange) {
      if ($value == ''){
        return $value;
      }
    
      switch ($caseChange) {
        case RETURN_LOWER_CASE : return strtolower($value);
        case RETURN_UPPER_CASE : return strtoupper($value);
        default : return $value;
      }
    }

    /*------------------*/
    public static function getRawRequestValue($name, $postOnly = false) {
      $request = ($postOnly) ? $_POST : $_REQUEST;
      return isset($request[$name]) ? $request[$name] : null;
    }
  
    /*------------------*/
    public static function sanitiseValue($value, $stripTags = true) {      
      $newValue = trim(strval($value));
      if($stripTags) {
        $newValue = strip_tags($newValue);
      }
      $newValue = htmlentities($newValue);
      //if (function_exists('esc_sql')) { 
      //  $newValue = esc_sql($newValue);
      //} else 
      if (class_exists('key_db')) {
        $newValue = key_db::sanitiseValue($newValue);
      } else if (class_exists('key_dblite')) {
        $newValue = key_dblite::sanitiseValue($newValue);
      }
      return $newValue;
    }
  
    /*------------------*/
    public static function getRequestBool($name, $dft = false, $postOnly = false) {
      $testVal = self::getRequestStr($name, '', $postOnly, RETURN_LOWER_CASE);
      if ($testVal == '') {
        return $dft;
      }
      return ($testVal == 'on') || ($testVal == 'true') || ($testVal == '1');
    }  

    /*------------------*/
    public static function getRequestStr($name, $dft = '', $postOnly = false, $returnAs = RETURN_AS_IS, $stripTags = true) {
      $value = self::getRawRequestValue($name, $postOnly);
      $toReturn = ($value !== null) ? self::sanitiseValue($value, $stripTags) : $dft;
      return self::returnValue($toReturn, $returnAs);
    }
    
    /*------------------*/
    public static function getRequestInt($name, $dft = 0, $postOnly = false) {
      $value = self::getRawRequestValue($name, $postOnly);
      return ($value !== null) ? intval(self::sanitiseValue($value)) : $dft;
    }
  
    /*------------------*/
    public static function getRequestArray($name, $dft = array(), $postOnly = false) {
      $values = self::getRawRequestValue($name, $postOnly);
      if (($values === null) || (!is_array($values))) {
        return $dft;
      }
    
      $santised = array();
      foreach ($values as $key => $value) {
        $santised[$key] = self::sanitiseValue($value);
      }
    
      return $santised;
    }

    /*--------------*/
    public static function getPostBody($default = ''){
      $postAsStr = file_get_contents('php://input');
      if (($postAsStr == null) || ($postAsStr == '')) {
        $postAsStr = $default;
      }
      return $postAsStr;
    }
  
    /*--------------*/
    public static function getParamValue($paramNumber, $default = '', $caseChange = RETURN_AS_IS) {  
      $startUrl = self::getCurrentOriginalUrl();
            
      $paramSplit = explode('?', $startUrl);
      $startUrl = $paramSplit[0];
      $res = $default;
      $arr = explode('/', $startUrl);
      if (count($arr) > $paramNumber){
        $res = $arr[$paramNumber];
        $res = urldecode($res);
        $res = self::sanitiseValue($res);
      } 
      
      return self::returnValue(trim($res), $caseChange);
    }

    /*------------------*/
    public static function quoteStr($text) {
      return '\''. addslashes($text) . '\'';
    }

    /*------------------*/
    public static function currencyWhole($amount, $args = array()) {
      return static::currency($amount, array_merge($args, array('decimalCount' => 0)));
    }

    /*------------------*/
    public static function currency($amount, $args = array()) {
      $usableArgs = array_merge(array(
        'prefix' => '£',
        'suffix' => '',
        'separator' => ',',
        'frequency' => 3,
        'decimal' => '.',
        'decimalCount' => 2
      ), $args);

      $amountArr = explode('.', strval($amount));
      if (count($amountArr) == 1) {
        $amountArr[] = '00';
      }

      $whole = $amountArr[0];
      if ($usableArgs['separator'] != '') {
        $separator = substr($usableArgs['separator'], 0, 1);
        $wholeLength = strlen($whole);
        $remainderMod = $wholeLength % $usableArgs['frequency'];
        $remainder = ($remainderMod > 0) ? $usableArgs['frequency'] - $remainderMod : 0;
        $whole = str_pad($whole, $wholeLength + $remainder, $separator, STR_PAD_LEFT);
        $whole = chunk_split($whole, $usableArgs['frequency'], $separator);
        $whole = trim($whole, $separator);
      }
      
      if ($usableArgs['decimalCount'] > 0) {
        $decimal = $amountArr[1];
        $decimalCount = count($decimal);
        $decimal = str_pad($decimal, $usableArgs['decimalCount'], '0');
        $decimal = substr($decimal, 0, $usableArgs['decimalCount']);
        return sprintf(
          '%s%s%s%s%s',
          $usableArgs['prefix'],
          $whole,
          $usableArgs['decimal'],
          $decimal,
          $usableArgs['suffix']
        );
      } else {
        return sprintf(
          '%s%s%s',
          $usableArgs['prefix'],
          $whole,
          $usableArgs['suffix']
        );
      }
    }

    /*------------------*/
    public static function dbDate($time) {
      return self::quoteStr(date('Y-m-d', $time));
    }

    /*------------------*/
    public static function dbTimestamp($time) {
      return self::quoteStr(date('Y-m-d H:i:s', $time));
    }

    /*------------------*/
    public static function dbString($string, $skipSanitise = false) {
      $toReturn = ($skipSanitise) ? $string : self::sanitiseValue($string);
      return self::quoteStr($toReturn);
    }

    /*------------------*/
    public static function dbBool($aBool) {
      return ($aBool) ? 1 : 0;
    }

    /*------------------*/
    public static function splitByNewLines($aString, $removeEmpty = true) {
      if ($removeEmpty) {
        return preg_split('~\R~', $aString, -1, PREG_SPLIT_NO_EMPTY);
      } else {
        return preg_split('~\R~', $aString);
      }
    }

    /*------------------*/
    public static function secondsToReadableTime($timeInSeconds, $args = array()) {
      $usableArgs = array_merge(array(
        'prefix' => '',
        'suffixDay' => 'd',
        'suffixDayPlural' => 'd',
        'suffixHour' => 'h',
        'suffixHourPlural' => 'h',
        'suffixMinute' => 'm',
        'suffixMinutePlural' => 'm',
        'suffixSeconds' => 's',
        'suffixSecondsPlural' => 's',
        'separator' => ' ',
        'includeSeconds' => false, 
        'spaceBeforeSuffix' => false
      ), $args);

      $prefix = $usableArgs['prefix'];
      $separator = $usableArgs['separator'];
      $includeSeconds = $usableArgs['includeSeconds'];

      $returnString = '';
      if ($prefix != '') {
        $returnString .= $prefix;
      }
      
      $days = floor($timeInSeconds / KEY_DAY_IN_SECONDS);
      $hours = floor(($timeInSeconds % KEY_DAY_IN_SECONDS) / KEY_HOUR_IN_SECONDS);
      $minutes = floor(($timeInSeconds % KEY_HOUR_IN_SECONDS) / KEY_MINUTE_IN_SECONDS);

      $toPrintDefs = array(
        array($days,    'suffixDay',    'suffixDayPlural'),
        array($hours,   'suffixHour',   'suffixHourPlural'),
        array($minutes, 'suffixMinute', 'suffixMinutePlural')
      );

      if ($includeSeconds) {
        $seconds = $timeInSeconds % 60;
        $toPrintDefs[] = array($seconds, 'suffixSeconds', 'suffixSecondsPlural');
      }

      $spacing = $usableArgs['spaceBeforeSuffix'] ? ' ' : '';

      foreach($toPrintDefs as $def) {
        $value       = $def[0];
        $singularKey = $def[1];
        $pluralKey   = $def[1];

        if ($value > 0) {
          if ($returnString != '') {
            $returnString .= $separator;
          }

          $suffix = ($value == 1) ? $usableArgs[$singularKey] : $usableArgs[$pluralKey];
          $returnString .= $value . $spacing . $suffix;
        }
      }
  
      return $returnString;      
    }

     /*------------------*/
    public static function readableTimeDifference($start, $args = array(), $end = null) {
      $endTime = ($end == null) ? time() : $end;

      $timeDifference = abs($endTime - $start);

      return static::secondsToReadableTime($timeDifference, $args);
    }
    /*------------------*/
  }
}

?>