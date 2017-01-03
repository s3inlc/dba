<?php
/**
 * Created by IntelliJ IDEA.
 * User: sein
 * Date: 03.01.17
 * Time: 15:02
 */

namespace DBA;

define("DBA_SERVER", "localhost");
define("DBA_DB", "database");
define("DBA_USER", "example_user");
define("DBA_PASS", "password");

$entries = scandir(dirname(__FILE__));
foreach ($entries as $entry) {
  if (strpos($entry, ".class.php") !== false) {
    require_once(dirname(__FILE__) . "/" . $entry);
  }
}

$entries = scandir(dirname(__FILE__)."/models");
foreach ($entries as $entry) {
  if (strpos($entry, ".class.php") !== false) {
    require_once(dirname(__FILE__) . "/models/" . $entry);
  }
}