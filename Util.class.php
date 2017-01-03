<?php

/**
 * Created by IntelliJ IDEA.
 * User: sein
 * Date: 03.01.17
 * Time: 14:50
 */

namespace DBA;

class Util {
  /**
   * @param $obj Object
   * @param $to_class
   * @return Object
   */
  public static function cast($obj, $to_class) {
    if (class_exists($to_class)) {
      $obj_in = serialize($obj);
      $obj_out = 'O:' . strlen($to_class) . ':"' . $to_class . '":' . substr($obj_in, $obj_in[2] + 7);
      return unserialize($obj_out);
    }
    else {
      return null;
    }
  }
  
  /**
   * TODO Document me
   * @param $table string
   * @param $dict array
   * @return string
   */
  public static function createPrefixedString($table, $dict) {
    $arr = array();
    foreach ($dict as $key => $val) {
      $arr[] = "`" . $table . "`" . "." . "`" . $key . "`" . " AS `" . $table . "." . $key . "`";
    }
    return implode(", ", $arr);
  }
  
  /**
   * Checks if $search starts with $pattern. Shortcut for strpos==0
   * @param $search string
   * @param $pattern string
   * @return bool
   */
  public static function startsWith($search, $pattern) {
    if (strpos($search, $pattern) === 0) {
      return true;
    }
    return false;
  }
}