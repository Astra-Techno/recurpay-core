<?php
namespace AstraTech\DataForge\Base;

use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Auth;

class Table extends ClassStatic
{
	private static function validate(array $input, string $tableName)
	{
		if (!$input) {
            self::setError('Empty input to save table - '.$tableName);
            return false;
        }

        $tableName = trim(preg_replace('/^jos_/', '', $tableName));
        if(!$tableName) {
            self::setError('Empty tableName to save!');
            return false;
        }

		return $tableName;
	}

    public static function update(array $input, string $tableName, $filterKeys = null, array $appendFields = [])
    {
        // Reuse the process logic for updating
        return self::save($input, $tableName, $filterKeys, $appendFields, true);
    }

    public static function save(array $input, string $tableName, $filterKeys = null, array $appendFields = [], $updateOnly = false)
    {
        //Log::info('Inputs'.print_r($input, true));
    	$tableName = self::validate($input, $tableName);
        if (!$tableName)
            return false;

        // Get schema info.
        if (!$schemaInfo = self::getSchemaInfo($tableName))
            return false;

        //Log::info($tableName.':'.print_r($schemaInfo, true));

        // Set filterKeys as the primary key from schemaInfo if not already provided
        if (!empty($filterKeys) && empty($schemaInfo->primary_key))
            $schemaInfo->primary_key = $filterKeys;

		// Clean input fields.
        $input = array_intersect_key($input, array_flip($schemaInfo->columns));

		// Fetch matching record.
        $record = self::fetchExistingRecord($tableName, $filterKeys, $input);

        //Log::info($tableName.'record:'.print_r($record, true));
        if (!$record && $updateOnly) {
            self::setError('Record not found!');
            return false;
        }

        // Handle field append logic (merge fields)
        if ($record) {
            foreach ($appendFields as $appendField) {
                if (isset($input[$appendField]) && in_array($appendField, $schemaInfo->columns)) {
                    $input[$appendField] = $record->$appendField . ' ' . $input[$appendField];
                }
            }

            if (in_array('modified_by', $schemaInfo->columns))
                $input['modified_by'] = Auth::id();
        } else if (in_array('created_by', $schemaInfo->columns) && empty($input['created_by']) && !$record) {
            // Handle created by.
            $input['created_by'] = Auth::id();
        }

        // Handle auto timestamps
        if (in_array('created', $schemaInfo->columns) && empty($input['created']) && !$record)
            $input['created'] = DataForge::Date();

        if (in_array('modified', $schemaInfo->columns))
            $input['modified'] = DataForge::Date();

        // Perform update or insert
        $dataBeforeSave = [];
        if (!$record)
            $record = new BaseModel($tableName);
        else
            $dataBeforeSave = $record->toArray();

        // Assign input values.
        foreach ($input as $key => $value)
            $record->{$key} = $value;

        // Set primaryKeyInfo on Base model.
        if ($schemaInfo->primary_key) {
        	$filterKeys = $schemaInfo->keys;
            $record->setPrimaryKey($schemaInfo->primary_key);
            if (!$schemaInfo->auto_increment)
                $record->incrementing = false;
        } else
        	$record->incrementing = false;

        if (!self::saveRecord($record))
            return false;

        $dataAfterSave = $record->toArray();

        self::logAudit($tableName, $dataBeforeSave, $dataAfterSave);

        return $dataAfterSave;
    }

	public static function delete($input, $tableName, $filterKeys)
	{
		$tableName = self::validate($input, $tableName);
        if (!$tableName)
            return false;

		// Get schema info.
        if (!$schemaInfo = self::getSchemaInfo($tableName))
            return false;

		// Clean input fiel ds.
        $input = array_intersect_key($input, array_flip($schemaInfo->columns));

		// Fetch matching record.
        $record = self::fetchExistingRecord($tableName, $filterKeys, $input);
        if (!$record) {
            self::setError('Record not found!');
            return false;
        }

		if ($schemaInfo->primary_key) {
		 	$input[$schemaInfo->primary_key] = $record->{$schemaInfo->primary_key};
		 	$filterKeys = $schemaInfo->primary_key;
		}

		if (!$record->delete())
			return false;

        return true;
	}

    // Fetch existing record based on filter keys
    private static function fetchExistingRecord($tableName, $filterKeys, $input)
    {
        $filterConditions = self::parseFilterKeys($filterKeys, $input);
        if (!$filterConditions)
            return false;

        // Build query for filtering
        foreach ($filterConditions as $condition)
        {
            $baseModel = new BaseModel($tableName);
            if ($record = $baseModel->Where($condition)->first())
                return $record;
        }

        return false;
    }

    // Helper function to parse filter keys (like id|email&name)
    public static function parseFilterKeys($filterKeys, $input)
    {
        $conditions = [];
        if (!$filterKeys)
            return $conditions;

        $keyGroups = explode('|', $filterKeys);
        foreach ($keyGroups as $group) {
            $andConditions = [];
            $keys = explode('&', $group);
            foreach ($keys as $key) {
                if (!isset($input[$key])) {
                    $andConditions = [];
                    break;
                }

                $andConditions[$key] = $input[$key];
            }

            if (!empty($andConditions)) {
                $conditions[] = $andConditions;
            }
        }

        return $conditions;
    }

	private static function getSchemaInfo($table)
	{
		$tableName = $table;

		// Unique cache key for primary key info
		$cacheKey = 'table_pk_info_1' . $tableName;
		$schemaTimestampKey = 'schema_last_updated_' . $tableName;

		// Fetch the last schema update time
		$result = DB::select("
		        SELECT
		            UPDATE_TIME
		        FROM information_schema.tables AS T
		        WHERE T.table_schema = ?
		            AND T.table_name = ?
		    ", [DB::getDatabaseName(), $tableName]);

		if (!$result) {
            self::setError('Table ('.$tableName.') - not found!');
			return false;
        }

		$schemaUpdateTime = $result[0]->UPDATE_TIME;

		// Get the last stored schema update time from cache
		$lastCachedUpdateTime = Cache::get($schemaTimestampKey);

		// If schema was updated, clear cache
		if ($schemaUpdateTime && $schemaUpdateTime !== $lastCachedUpdateTime) {
		    Cache::forget($cacheKey);
		    Cache::put($schemaTimestampKey, $schemaUpdateTime, 86400); // Store schema update time for 1 day
		}

		$schemaInfo = (object) Cache::remember($cacheKey, 86400, function () use ($tableName, $table) {
		    // Fetch primary key and auto_increment info
		    $result = DB::select("
		        SELECT
		            GROUP_CONCAT(C.column_name) AS primary_key,
		            GROUP_CONCAT(DISTINCT C.extra) AS extra_info
		        FROM information_schema.columns AS C
		        JOIN information_schema.tables AS T
		            ON C.table_schema = T.table_schema
		            AND C.table_name = T.table_name
		        WHERE C.table_schema = DATABASE()
		        AND C.table_name = ?
		        AND C.column_key = 'PRI'
		    ", [$tableName]);

		    $out = ['primary_key' => '', 'keys' => '', 'auto_increment' => 0, 'columns' => []];

		    if ($result) {
		        $keys = explode(',', $result[0]->primary_key);
		        $out = [
		            'primary_key' => $keys[0],
		            'keys' => implode('&', $keys),
		            'auto_increment' => strpos($result[0]->extra_info, 'auto_increment') !== false ? 1 : 0,
		            'columns'	=> Schema::getColumnListing($table)
		        ];
		    }

		    return $out;
		});

        if (empty($schemaInfo->columns)) {
        	self::setError('Invalid table to save!');
            return false;
        }

		return $schemaInfo;
	}

    // Batch save method to handle multiple records
    public static function saveBatch(array $inputs, string $tableName, $filterKeys = null, array $appendFields = [])
    {
        DB::transaction(function () use ($inputs, $tableName, $filterKeys, $appendFields) {
            foreach ($inputs as $input) {
                self::save($input, $tableName, $filterKeys, $appendFields);
            }
        });
    }

    // Batch update method to handle multiple records
    public static function updateBatch(array $inputs, string $tableName, $filterKeys = null, array $appendFields = [])
    {
        DB::transaction(function () use ($inputs, $tableName, $filterKeys, $appendFields) {
            foreach ($inputs as $input) {
                self::save($input, $tableName, $filterKeys, $appendFields, true);
            }
        });
    }

    private static function logAudit($tableName, $dataBeforeSave, $dataAfterSave)
    {
        $diff = array_diff_assoc($dataAfterSave, $dataBeforeSave);

        if (isset($diff['created']))
            unset($diff['created']);

        if (isset($diff['updated']))
            unset($diff['updated']);

        if (!$diff && !$dataBeforeSave)
            return true;

        $changes = array();
        foreach ($diff as $key => $value)
        {
            if ($dataBeforeSave) {
                if ($dataAfterSave[$key] == $dataBeforeSave[$key]) {
                    unset($diff[$key]);
                    continue;
                }

                $changes[$key] = [$dataBeforeSave[$key], $dataAfterSave[$key]];
            } else
                $changes[$key] = ['', $dataAfterSave[$key]];
        }

        $changes = json_encode($changes);
        if (!$diff)
            return true;

        $historyTable = self::getHistoryTable($tableName);
        if (!$historyTable || empty($historyTable->primary_key) || $historyTable->enabled == 0)
            return true;

        $historyRecord = new BaseModel('history_log');
        $historyRecord->table_id = $historyTable->id;
		$historyRecord->record_id = $dataAfterSave[$historyTable->primary_key];
		$historyRecord->changed_by = Auth::id();
        $historyRecord->changed = DataForge::Date();
		$historyRecord->is_first = empty($dataBeforeSave) ? 1 : 0;

        if ($historyTable->new_table == 1) {
            $historyChangesRecord = new BaseModel('history_log_changes');
            $historyChangesRecord->changes = $changes;
            self::saveRecord($historyChangesRecord);

            $historyRecord->changes_id = $historyChangesRecord->id;
        } else
    		$historyRecord->changes = $changes;

        return self::saveRecord($historyRecord);
    }

    private static function getHistoryTable($tableName)
	{
        $historyTable = new BaseModel('history_log_tables');
        if ($record = $historyTable->Where('name', $tableName)->first())
            return $record;

        $primaryKeyInfo = self::getSchemaInfo($tableName);
        if ($primaryKeyInfo)
            $historyTable->primary_key = $primaryKeyInfo->primary_key;

        $historyTable->name = $tableName;
        $historyTable->enabled = 1;

		return self::saveRecord($historyTable);
	}

    private static function saveRecord(&$record)
    {
        try {
            $record->save();

            $record = $record->fresh();
        } catch(Exception $e) {
            self::setError($e->getMessage());
            return false;
        }

        return $record;
    }
}
