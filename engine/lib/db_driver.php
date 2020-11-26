<?php

$core_errors = "";
$core_warnings = "";
$core_stlog = "";
$core_criticalError = false;
$core_current_error = "";
$core_error_code = 0;

require_once "db_low_level_tools.php";
require_once 'other_tools.php';

class TableField
{
    var $colName = "";
    var $colType = "";
    var $colLabel = "";

    /**
     * TableCol constructor.
     * @param string $ColName
     * @param string $ColType
     * @param string $colLabel
     */
    public function __construct($colName, $colType, $colLabel = "undef")
    {
        $this->colName = $colName;
        $this->colType = $colType;
        if ($colLabel === "undef") $this->colLabel = $colName;
        else $this->colLabel = $colLabel;
    }


}

class keyValueRecord
{
    var $key;
    var $value;

    /**
     * keyValueRecord constructor.
     * @param $key
     * @param $value
     */
    public function __construct($key, $value)
    {
        $this->key = $key;
        $this->value = $value;
    }


}

class TableAdapter
{
    var $tableName = "";
    public $tableRecords;
    private $tableFields;
    private $newItems;
    private $editedItems;
    private $itemKeysToDelete;
    private $lastLoadAction = "create";
    private $lastLoadKeyValue;

    /**
     * TableAdapter constructor.
     * @param string $tableName
     * @param array $tableRecords
     * @param array $tableFields
     */
    public function __construct($tableName, array $tableFields, array $tableRecords = null)
    {
        global $core_criticalError;
        if (!$core_criticalError) {
            global $db_name;
            $this->tableName = $tableName;
            $this->tableFields = $tableFields;
            $result = db_runQuery("SHOW DATABASES LIKE \"" . $db_name . "\";");
            if (!$result) {
                addErrorToLog("DB checker", "Request error while checking database existence: " . $db_name, true);
                global $core_current_error;
                $core_current_error = "#101 Request error while checking database existence";
                global $core_error_code;
                $core_error_code = 101;
            } else if (!mysqli_fetch_array($result)) {
                addErrorToLog("DB checker", "Database doesn't exists: " . $db_name, true);
                global $core_current_error;
                $core_current_error = "#102 Database doesn't exist";
                global $core_error_code;
                $core_error_code = 102;
            } else {
                $result = db_runQuery("SHOW TABLES LIKE \"" . $tableName . "\";");
                if (!$result) {
                    addErrorToLog("DB checker", "Request error while checking: " . $tableName);
                }
                if (!mysqli_fetch_array($result)) {
                    addWarningToLog("DB checker", "Table not found: " . $tableName);

                    if ($this->createTable()) {
                        $this->newItems = $tableRecords;
                        $this->applyChanges();
                    }
                } else {
                    $this->tableRecords = $tableRecords;
                }
            }

        } else {
            addErrorToLog("DB checker control point", "Operation stopped cause of critical state", true);
        }
    }

    function createTable()
    {
        global $core_criticalError;
        if (!$core_criticalError) {
            $query = "CREATE TABLE `" . $this->tableName . "` (";
            $isKeySetted = false;
            foreach ($this->tableFields as $currentCol) {
                if ($isKeySetted) $query .= ', ';
                $query .= "`" . $currentCol->colName . "` " . $currentCol->colType . " NOT NULL";
                if (!$isKeySetted) {
                    $isKeySetted = true;
                    $query .= " AUTO_INCREMENT PRIMARY KEY";
                }
            }
            $query .= ");";
            $result = db_runQuery($query);

            if (!$result) {
                addErrorToLog("Table creator", "Table create failed: " . $this->tableName, true);
                global $core_current_error;
                $core_current_error = "#108 table creation failed";
                global $core_error_code;
                $core_error_code = 108;
                return false;
            }
            return true;
        } else {
            addErrorToLog("DB table creator control point", "Operation stopped cause of critical state");
            return false;
        }
    }

    function applyChanges()
    {
        global $core_criticalError;
        if (!$core_criticalError) {
            if (!empty($this->itemKeysToDelete)) {
                foreach ($this->itemKeysToDelete as $item) {
                    //delete action
                    $result = db_runQuery('DELETE FROM ' . $this->tableName . ' WHERE ' . $item->key . '="' . $item->value . '";');
                    if (!$result) {
                        addErrorToLog("DB adapter", "Db record delete failed: " . $this->tableName, true);
                        global $core_current_error;
                        $core_current_error = "#105 DB record delete failed";
                        global $core_error_code;
                        $core_error_code = 105;
                        return false;
                    }
                }
                unset($this->itemKeysToDelete);
            }

            if (!empty($this->newItems)) {
                //add new action
                foreach ($this->newItems as $tableValue) {

                    $query = "INSERT INTO " . $this->tableName . " (";
                    $isFirst = true;
                    foreach ($tableValue as $key => $currentVal) {
                        $query .= ($isFirst ? " `" : "` , `") . $key;
                        $isFirst = false;
                    }
                    $isFirst = true;
                    $query .= "`) VALUES(";
                    foreach ($tableValue as $currentValueCol) {
                        $query .= ($isFirst ? " \"" : "\" , \"") . htmlspecialchars($currentValueCol);
                        $isFirst = false;
                    }
                    $query .= "\");";

                    $result = db_runQuery($query);
                    if (!$result) {
                        addErrorToLog("DB adapter", "Db data insert failed: " . $this->tableName, true);
                        global $core_current_error;
                        $core_current_error = "#106 DB data insert failed";

                        global $core_error_code;
                        $core_error_code = 106;
                        return false;
                    }
                }
                unset($this->newItems);
            }
            if (!empty($this->editedItems)) {
                foreach ($this->editedItems as $record) {
                    //Updating action
                    $query = 'UPDATE `' . $this->tableName . '` SET ';
                    $isFirst = true;
                    foreach ($record->value as $key => $currentVal) {
                        if ($key === "id") continue;
                        if (!$isFirst) $query .= ', ';
                        $query .= "`$key`=\"" . htmlspecialchars($currentVal) . "\"";
                        $isFirst = false;
                    }
                    $query .= ' WHERE `id`="' . $this->tableRecords[$record->key]["id"] . '";';
                    $result = db_runQuery($query);
                    if (!$result) {
                        addErrorToLog("DB adapter", "Db data update failed: " . $this->tableName, true);
                        global $core_current_error;
                        $core_current_error = "#107 DB data update failed";

                        global $core_error_code;
                        $core_error_code = 107;
                        return false;
                    }
                }
            }
            $this->refresh();
            return true;
        } else {
            addErrorToLog("DB update control point", "Operation stopped cause of critical state");
            return false;
        }
    }

    function refresh()
    {
        switch ($this->lastLoadAction) {
            case "all":
                $this->loadFromDatabaseAll();
                break;
            case "key":
                $this->loadFromDatabaseByKey($this->lastLoadKeyValue->key, $this->lastLoadKeyValue->value);
                break;
        }
    }

    function loadFromDatabaseAll($additionalParam = null)
    {
        global $core_criticalError;
        if (!$core_criticalError) {
            $this->lastLoadAction = "all";
            unset($this->tableRecords);
            $result = db_runQuery("SELECT * FROM " . $this->tableName . " " . $additionalParam . ";");
            if (!$result) {
                addErrorToLog("DB adapter", "Db load all failed: " . $this->tableName, true);
                global $core_current_error;
                $core_current_error = "#103 DB load all entries action failed";
                global $core_error_code;
                $core_error_code = 103;
                return false;
            } else {
                while ($row = mysqli_fetch_row($result)) {
                    $loadedArray = [];
                    $i = 0;
                    foreach ($row as $key => $val) {
                        $loadedArray[$this->tableFields[$i++]->colName] = htmlspecialchars_decode($val);
                    }
                    $this->tableRecords[] = $loadedArray;
                }
            }
            return true;
        } else {
            addErrorToLog("DB load all control point", "Operation stopped cause of critical state");
            return false;
        }
    }

    function loadFromDatabaseByKey($keyName, $value, $additionalParam = null)
    {
        global $core_criticalError;
        if (!$core_criticalError) {
            $this->lastLoadAction = "key";
            $this->lastLoadKeyValue = new keyValueRecord($keyName, $value);
            unset($this->tableRecords);
            $result = db_runQuery("SELECT * FROM " . $this->tableName . " WHERE `$keyName`=\"$value\" " . $additionalParam . ";");
            if (!$result) {
                addErrorToLog("DB adapter", "Db load all failed: " . $this->tableName, true);
                global $core_current_error;
                $core_current_error = "#104 DB load all entries by key failed";
                global $core_error_code;
                $core_error_code = 104;
                return false;
            } else {
                while ($row = mysqli_fetch_row($result)) {
                    $loadedArray = [];
                    $i = 0;
                    foreach ($row as $key => $val) {
                        $loadedArray[$this->tableFields[$i++]->colName] = htmlspecialchars_decode($val);
                    }
                    $this->tableRecords[] = $loadedArray;
                }
            }
            return true;
        } else {
            addErrorToLog("DB load by key control point", "Operation stopped cause of critical state");
            return false;
        }
    }

    function updateRecord($key, $value, $record)
    {
        global $core_criticalError;
        if (!$core_criticalError) {
            $index = -1;
            foreach ($this->tableRecords as $recKey => $recVal) {
                foreach ($recVal as $recValKey => $recValVal) {
                    if ($recValKey === $key && $recValVal === $value) {
                        $index = $recKey;
                        break;
                    }
                }
                if ($index >= 0) break;
            }
            $this->editedItems[] = new keyValueRecord($index, $record);
        } else {
            addErrorToLog("DB update record control point", "Operation stopped cause of critical state", true);
        }
    }

    function count($additionalParam = '')
    {
        global $core_criticalError;
        if (!$core_criticalError) {
            $this->lastLoadAction = "all";
            unset($this->tableRecords);
            $result = db_runQuery("SELECT COUNT(*) FROM " . $this->tableName . " " . $additionalParam . ";");
            if (!$result) {
                addErrorToLog("DB adapter", "Db count failed: " . $this->tableName, true);
                global $core_current_error;
                $core_current_error = "#109 DB count elements in table failed";
                global $core_error_code;
                $core_error_code = 109;
                return false;
            } else {
                return mysqli_fetch_row($result)[0];
            }
        } else {
            addErrorToLog("DB count control point", "Operation stopped cause of critical state");
            return false;
        }
    }

    function addNewRecord(array $record)
    {
        global $core_criticalError;
        if (!$core_criticalError) {
            $copyOfFields = $this->tableFields;

            foreach ($record as $key => $value) {
                $keyIsNotDefined = true;
                foreach ($copyOfFields as $fieldKey => $field) {
                    if ($field->colName === "id") unset($copyOfFields[$fieldKey]);
                    else if ($key === $field->colName) {
                        unset($copyOfFields[$fieldKey]);
                        $keyIsNotDefined = false;
                        break;
                    }
                }
                if ($keyIsNotDefined) {
                    addErrorToLog("Add record to table " . $this->tableName, "unknown key for database: " . $key);
                    return false;
                }
            }
            if (count($copyOfFields) > 0) {
                foreach ($copyOfFields as $field) {
                    $record[$field->colName] = "";
                }
            }
            $this->newItems[] = $record;
            return true;
        } else {
            addErrorToLog("DB add record control point", "Operation stopped cause of critical state");
            return false;
        }
    }

    function deleteRecord($key, $value)
    {
        $this->itemKeysToDelete[] = new keyValueRecord($key, $value);
    }

    function getOneElementWhere($key, $value)
    {
        foreach ($this->tableRecords as $currentRecord) {
            foreach ($currentRecord as $key_db => $value_db) {
                if ($key_db === $key && $value_db === $value) {
                    return $currentRecord;
                }
            }
        }
        return null;
    }

    function getFields()
    {
        return $this->tableFields;
    }
}

/* =====================SERVICE; DO NOT CHANGE OR DELETE===================================*/
$engine_db_driver_loaded = 1; //DO NOT CHANGE