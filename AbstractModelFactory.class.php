<?php

/**
 * Created by IntelliJ IDEA.
 * User: sein
 * Date: 02.01.17
 * Time: 23:57
 */

namespace DBA;
use PDO,PDOStatement,PDOException;

/**
 * Abstraction of all ModelFactories.
 * A ModelFactory is used to get all
 * models from Database. It handels the DB calling and caching of objects.
 */
abstract class AbstractModelFactory {
  /**
   * @var PDO
   */
  private $dbh = null;
  
  /**
   * Return the Models name
   *
   * @return string The name of the model associated with this factory
   */
  abstract function getModelName();
  
  /**
   * Return the Models associated table
   *
   * This function defines table associated with this model and will be
   * used by the database abstraction to save your model in.
   *
   * @return string The name of the table associated with this factory
   */
  abstract function getModelTable();
  
  /**
   * Returns weither the associated model is cachable or not
   *
   * @return boolean True, if the object might be cached, False if not
   */
  abstract function isCachable();
  
  /**
   * Returns weither the models valid time on cache.
   *
   * Returns the time in seconds a object might life on the cache.
   * If the model shouldn't be cachable -1 shall be returned
   *
   * @return int valid time in seconds, -1 if model shouldn't be cached
   */
  abstract function getCacheValidTime();
  
  /**
   * Returns an empty instance of the associated object
   *
   * This empty object is used to get all the object properties for
   * different queries such as the get queries, where no actual object
   * is given
   *
   * @return AbstractModel
   */
  abstract function getNullObject();
  
  /**
   * This function inits, an objects values from a dict and returns it;
   *
   * This function is used to get objects from a certain type from db resourcebundle_get_error_message
   *
   * @param $pk string primary key
   * @param $dict array dict of values and keys
   * @return AbstractModel An object of the factories type
   */
  abstract function createObjectFromDict($pk, $dict);
  
  /**
   * Saves the passed model in database, and returns it with the real id
   * in the database.
   *
   * The function saves the passed model in the database and updates the
   * cache, if the model shall be cached. The primary key of this object
   * MUST be -1
   *
   * The Function returns null if the object could not be placed into the
   * database
   * @param $model AbstractModel model to save
   * @return AbstractModel
   */
  public function save($model) {
    $dict = $model->getKeyValueDict();
    
    $query = "INSERT INTO " . $this->getModelTable() . "(";
    $keys = array_keys($dict);
    $vals = array_values($dict);
    
    $placeHolder = "(";
    for ($i = 0; $i < count($keys); $i++) {
      if ($i != count($keys) - 1) {
        $query = $query . $keys[$i] . ",";
        $placeHolder = $placeHolder . "?,";
      }
      else {
        $query = $query . $keys[$i];
        $placeHolder = $placeHolder . "?";
      }
    }
    $query = $query . ")";
    $placeHolder = $placeHolder . ")";
    
    $query = $query . " VALUES " . $placeHolder;
    
    $dbh = $this->getDB();
    $stmt = $dbh->prepare($query);
    $stmt->execute($vals);
    
    $id = $dbh->lastInsertId();
    if ($id != 0) {
      $model->setId($id);
      return $model;
    }
    else {
      return null;
    }
  }
  
  /**
   * Updates the database entry for the model
   *
   * This function updates the database entry for the given model
   * based on it's primary key.
   * Returns the return of PDO::execute()
   * @param $model AbstractModel model to update
   * @return PDOStatement
   */
  public function update($model) {
    $dict = $model->getKeyValueDict();
    
    $query = "UPDATE " . $this->getModelTable() . " SET ";
    
    $keys = array_keys($dict);
    $values = array();
    
    for ($i = 0; $i < count($keys); $i++) {
      if ($i != count($keys) - 1) {
        $query = $query . $keys[$i] . "=?,";
        array_push($values, $dict[$keys[$i]]);
      }
      else {
        $query = $query . $keys[$i] . "=?";
        array_push($values, $dict[$keys[$i]]);
      }
    }
    
    $query = $query . " WHERE " . $model->getPrimaryKey() . "=?";
    array_push($values, $model->getPrimaryKeyValue());
    
    $stmt = $this->getDB()->prepare($query);
    $stmt->execute($values);
    return $stmt;
  }
  
  /**
   * Get's a model from it's primary key.
   *
   * This function returns the model with the given primary key or null.
   * If the model is specified to be non-cached, this function will call
   * the getFromDB() function and retrn it's result. It's therefor recommended
   * to use this function
   *
   * @param $pk string primary key
   * @return AbstractModel the with pk associated model or Null
   *
   */
  public function get($pk) {
    if (!$this->isCachable()) {
      return $this->getFromDB($pk);
    }
    else {
      // ToDo: Implement some memecached magic shit
      return $this->getFromDB($pk);
    }
  }
  
  /**
   * Get's a model by it's primary key directly going to the database
   *
   * This function returns the model with the given primary key or null.
   * This function will go to the database directly neglecting the cache.
   * If the model is set to be cachable, the cache will also be updated
   *
   * @param $pk string primary key
   * @return AbstractModel the with pk associated model or Null
   */
  public function getFromDB($pk) {
    $query = "SELECT ";
    
    $keys = array_keys($this->getNullObject()->getKeyValueDict());
    
    for ($i = 0; $i < count($keys); $i++) {
      if ($i != count($keys) - 1) {
        $query = $query . $keys[$i] . ",";
      }
      else {
        $query = $query . $keys[$i];
      }
    }
    $query = $query . " FROM " . $this->getModelTable();
    
    $query = $query . " WHERE " . $this->getNullObject()->getPrimaryKey() . "=?";
    
    $stmt = $this->getDB()->prepare($query);
    $stmt->execute(array(
        $pk
      )
    );
    if ($stmt->rowCount() != 0) {
      $row = $stmt->fetch(PDO::FETCH_ASSOC);
      return $this->createObjectFromDict($pk, $row);
    }
    else {
      return null;
    }
  }
  
  /**
   * Filters the database for a set of options
   *
   * This function filters the dataset (think of it as a select) for a set
   * of options.
   * The structure of the options array is a dictionary with the following
   * structure
   *
   * $options = array();
   * $options['filter'] is an array of QueryFilter options
   * $options['order'] is an array of OrderFilter options
   * $options['join'] is an array of JoinFilter options
   *
   * @param $options array containing option settings
   * @param $single bool result should be one single row
   * @return AbstractModel[]|AbstractModel Returns a list of matching objects or Null
   */
  public function filter($options, $single = false) {
    // Check if we need to join and if so pass on to internal Function
    if (array_key_exists('join', $options)) {
      return $this->filterWithJoin($options);
    }
    else {
      $query = "SELECT ";
      $query = $query . $this->getNullObject()->getPrimaryKey() . ",";
      $keys = array_keys($this->getNullObject()->getKeyValueDict());
      
      for ($i = 0; $i < count($keys); $i++) {
        if ($i != count($keys) - 1) {
          $query = $query . $keys[$i] . ",";
        }
        else {
          $query = $query . $keys[$i];
        }
      }
      $query = $query . " FROM " . $this->getModelTable();
      
      $vals = array();
      
      if (array_key_exists("filter", $options)) {
        $query = $query . " WHERE ";
        
        
        $filterOptions = $options['filter'];
        $vals = array();
        
        for ($i = 0; $i < count($filterOptions); $i++) {
          $option = Util::cast($filterOptions[$i], Filter::class);
          if ($option->getValue() != null) {
            array_push($vals, $option->getValue());
          }
          
          if ($i != count($filterOptions) - 1) {
            $query = $query . $option->getQueryString() . " AND ";
          }
          else {
            $query = $query . $option->getQueryString();
          }
        }
      }
      
      if (!array_key_exists("order", $options)) {
        // Add a asc order on the primary keys as a standard
        $oF = new OrderFilter($this->getNullObject()->getPrimaryKey(), "ASC");
        $orderOptions = array(
          $oF
        );
        $options['order'] = $orderOptions;
      }
      if (count($options['order']) != 0) {
        $query = $query . " ORDER BY ";
        $orderOptions = $options['order'];
        
        for ($i = 0; $i < count($orderOptions); $i++) {
          if ($i != count($orderOptions) - 1) {
            $order = $orderOptions[$i];
            $query = $query . $order->getQueryString() . ",";
          }
          else {
            $order = $orderOptions[$i];
            $query = $query . $order->getQueryString();
          }
        }
      }
      
      $dbh = $this->getDB();
      $stmt = $dbh->prepare($query);
      $stmt->execute($vals);
      
      $objects = array();
      
      // Loop over all entries and create an object from dict for each
      while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $pkName = $this->getNullObject()->getPrimaryKey();
        
        $pk = $row[$pkName];
        $model = $this->createObjectFromDict($pk, $row);
        array_push($objects, $model);
      }
      
      if ($single) {
        if (sizeof($single) == 0) {
          return null;
        }
        else {
          return $objects[0];
        }
      }
      
      return $objects;
    }
  }
  
  /**
   * Private function used to compute a filter when a join is used.
   * This is put here to avoid putting too much stuff in the filter
   * method. It should only be called from filter()
   * @param $options array
   * @return array
   */
  private function filterWithJoin($options) {
    $joinOptions = $options['join'];
    
    $jO = Util::cast($joinOptions[0], JoinFilter::class);
    // Get own tables with prefixes
    $ownTable = $this->getModelTable();
    $ownTablePrefixed = Util::createPrefixedString($ownTable, $this->getPrefixedKeys($ownTable));
    
    // Get other tables prefixes and information
    $otherFactory = Util::cast($jO->getOtherFactory(), AbstractModelFactory::class);
    $otherTable = $jO->getOtherFactory()->getModelTable();
    $otherTablePrefixed = Util::createPrefixedString($otherTable, $this->getPrefixedKeys($otherTable));
    
    // Get macthing colums
    $match1 = $jO->getMatch1();
    $match2 = $jO->getMatch2();
    $query = "SELECT " . $ownTablePrefixed . " , " . $otherTablePrefixed . " FROM " . $ownTable . " AS " . $ownTable . " INNER JOIN " . $otherTable . " ON " . $ownTable . "." . $match1 . "=" . $otherTable . "." . $match2;
    
    $vals = array();
    // Apply all normal filter to this query
    if (array_key_exists("filter", $options)) {
      $query = $query . " WHERE ";
      $filterOptions = $options['filter'];
      
      for ($i = 0; $i < count($filterOptions); $i++) {
        $option = Util::cast($filterOptions[$i], Filter::class);
        array_push($vals, $option->getValue());
        
        if ($i != count($filterOptions) - 1) {
          $query = $query . $option->getQueryString($ownTable) . " AND ";
        }
        else {
          $query = $query . $option->getQueryString($ownTable);
        }
      }
    }
    
    // Apply order filter
    if (!array_key_exists("order", $options)) {
      // Add a asc order on the primary keys as a standard
      $oF = new OrderFilter($this->getNullObject()->getPrimaryKey(), "ASC");
      $orderOptions = array(
        $oF
      );
      $options['order'] = $orderOptions;
    }
    if (count($options['order']) != 0) {
      $query = $query . " ORDER BY ";
      $orderOptions = $options['order'];
      
      for ($i = 0; $i < count($orderOptions); $i++) {
        if ($i != count($orderOptions) - 1) {
          $order = $orderOptions[$i];
          $query = $query . $order->getQueryString($ownTable) . ",";
        }
        else {
          $order = $orderOptions[$i];
          $query = $query . $order->getQueryString($ownTable);
        }
      }
    }
    
    $dbh = $this->getDB();
    $stmt = $dbh->prepare($query);
    $stmt->execute($vals);
    
    // Seperate each table into two dict to create the corresponding OBJECTS
    $ownTablePref = $ownTable . ".";
    $otherTablePref = $otherTable . ".";
    
    $res = array();
    $res[$ownTable] = array();
    $res[$otherTable] = array();
    
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
      $ownKeys = array();
      $ownPk = null;
      
      $otherKeys = array();
      $otherPk = null;
      
      foreach ($row as $k => $v) {
        if (Util::startsWith($k, $ownTablePref)) {
          $nK = str_replace($ownTablePref, "", $k);
          if ($nK != $this->getNullObject()->getPrimaryKey()) {
            $ownKeys[$nK] = $v;
          }
          else {
            $ownPk = $v;
          }
        }
        else if (Util::startsWith($k, $otherTablePref)) {
          $nK = str_replace($otherTablePref, "", $k);
          if ($nK != $otherFactory->getNullObject()->getPrimaryKey()) {
            $otherKeys[$nK] = $v;
          }
          else {
            $otherPk = $v;
          }
        }
      }
      
      $ownModel = $this->createObjectFromDict($ownPk, $ownKeys);
      $otherModel = $otherFactory->createObjectFromDict($otherPk, $otherKeys);
      
      array_push($res[$ownTable], $ownModel);
      array_push($res[$otherTable], $otherModel);
    }
    
    return $res;
  }
  
  /**
   * This function gives back a dict with all colums in the table
   * and their prefixed equivalent.
   *
   * As an example, the column "name" in "user" becomes "name" => "user.name"
   * @param $table string table name
   * @return array
   */
  private function getPrefixedKeys($table) {
    $dbh = $this->getDB();
    
    $query = "DESCRIBE `$table`"; // For whatever reason, prepared statements are not working on this one. Or i'm to stupid.
    
    $stmt = $dbh->prepare($query);
    $stmt->execute(array(
        $table
      )
    );
    
    $dict = array();
    $fields = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    foreach ($fields as $f) {
      $dict[$f] = "`" . $table . "." . $f . "`";
    }
    
    return $dict;
  }
  
  /**
   * Deletes the given model
   *
   * This function deletes the given and also cleanes the cache from it.
   * It returns the return of the execute query.
   * @param $model AbstractModel
   * @return bool
   */
  public function delete($model) {
    if ($model != null) {
      $query = "DELETE FROM " . $this->getModelTable() . " WHERE " . $model->getPrimaryKey() . " = ?";
      $stmt = $this->getDB()->prepare($query);
      return $stmt->execute(array(
          $model->getPrimaryKeyValue()
        )
      );
    }
    return false;
  }
  
  /**
   * @param $options array
   * @return PDOStatement
   */
  public function massDeletion($options) {
    $query = "DELETE FROM " . $this->getModelTable();
    
    $vals = array();
    
    if (array_key_exists("filter", $options)) {
      $query = $query . " WHERE ";
      
      
      $filterOptions = $options['filter'];
      $vals = array();
      
      for ($i = 0; $i < count($filterOptions); $i++) {
        $option = Util::cast($filterOptions[$i], Filter::class);
        array_push($vals, $option->getValue());
        
        if ($i != count($filterOptions) - 1) {
          $query = $query . $option->getQueryString() . " AND ";
        }
        else {
          $query = $query . $option->getQueryString();
        }
      }
    }
    
    $dbh = $this->getDB();
    $stmt = $dbh->prepare($query);
    $stmt->execute($vals);
    return $stmt;
  }
  
  /**
   * Returns the DB connection if possible
   * @param bool $test
   * @return PDO
   */
  public function getDB($test = false) {
    $dsn = 'mysql:dbname=' . DBA_DB . ";" . "host=" . DBA_SERVER;
    $user = DBA_USER;
    $password = DBA_PASS;
    
    if ($this->dbh !== null) {
      return $this->dbh;
    }
    
    try {
      $this->dbh = new PDO($dsn, $user, $password);
      $this->dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
      return $this->dbh;
    }
    catch (PDOException $e) {
      if ($test) {
        return null;
      }
      die("Fatal Error ! Database connection failed");
    }
  }
}

