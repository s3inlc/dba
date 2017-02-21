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

require_once(dirname(__FILE__) . "/AbstractModel.class.php");
require_once(dirname(__FILE__) . "/AbstractModelFactory.class.php");
require_once(dirname(__FILE__) . "/Filter.class.php");
require_once(dirname(__FILE__) . "/Order.class.php");
require_once(dirname(__FILE__) . "/Join.class.php");
require_once(dirname(__FILE__) . "/ComparisonFilter.class.php");
require_once(dirname(__FILE__) . "/ContainFilter.class.php");
require_once(dirname(__FILE__) . "/JoinFilter.class.php");
require_once(dirname(__FILE__) . "/OrderFilter.class.php");
require_once(dirname(__FILE__) . "/QueryFilter.class.php");
require_once(dirname(__FILE__) . "/Util.class.php");
require_once(dirname(__FILE__) . "/UpdateSet.class.php");

$entries = scandir(dirname(__FILE__) . "/models");
foreach ($entries as $entry) {
  if (strpos($entry, ".class.php") !== false) {
    require_once(dirname(__FILE__) . "/models/" . $entry);
  }
}

require_once(dirname(__FILE__) . "/Factory.class.php");
define("DBA_VERSION", "1.0.0");