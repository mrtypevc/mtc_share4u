<?php
/**
 * MTC_SHARE4U - JSON Database Abstraction Layer
 * High-performance flat-file database system
 */

class Database {
    private static $instances = [];
    private $filePath;
    private $data;
    private $lock;

    /**
     * Get database instance (Singleton pattern)
     */
    public static function getInstance($dbFile) {
        if (!isset(self::$instances[$dbFile])) {
            self::$instances[$dbFile] = new self($dbFile);
        }
        return self::$instances[$dbFile];
    }

    /**
     * Constructor
     */
    private function __construct($dbFile) {
        $this->filePath = $dbFile;
        $this->load();
    }

    /**
     * Load data from JSON file
     */
    private function load() {
        if (!file_exists($this->filePath)) {
            $this->data = [];
            $this->save();
            return;
        }

        $content = file_get_contents($this->filePath);
        $this->data = json_decode($content, true) ?? [];
    }

    /**
     * Save data to JSON file with atomic write
     */
    private function save() {
        $tempFile = $this->filePath . '.tmp';
        $jsonData = json_encode($this->data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        
        if (file_put_contents($tempFile, $jsonData, LOCK_EX) === false) {
            throw new Exception("Failed to write to database file");
        }

        if (!rename($tempFile, $this->filePath)) {
            unlink($tempFile);
            throw new Exception("Failed to move temp file to database file");
        }
    }

    /**
     * Get all records
     */
    public function getAll() {
        return $this->data;
    }

    /**
     * Get record by ID
     */
    public function getById($id) {
        return $this->data[$id] ?? null;
    }

    /**
     * Insert new record
     */
    public function insert($record) {
        $id = $this->generateId();
        $record['id'] = $id;
        $record['created_at'] = date('Y-m-d H:i:s');
        $record['updated_at'] = date('Y-m-d H:i:s');
        
        $this->data[$id] = $record;
        $this->save();
        
        return $id;
    }

    /**
     * Update record by ID
     */
    public function update($id, $data) {
        if (!isset($this->data[$id])) {
            return false;
        }

        $this->data[$id] = array_merge($this->data[$id], $data);
        $this->data[$id]['updated_at'] = date('Y-m-d H:i:s');
        $this->save();
        
        return true;
    }

    /**
     * Delete record by ID
     */
    public function delete($id) {
        if (!isset($this->data[$id])) {
            return false;
        }

        unset($this->data[$id]);
        $this->save();
        
        return true;
    }

    /**
     * Find records by condition
     */
    public function find($condition) {
        return array_filter($this->data, function($record) use ($condition) {
            foreach ($condition as $key => $value) {
                if (!isset($record[$key]) || $record[$key] != $value) {
                    return false;
                }
            }
            return true;
        });
    }

    /**
     * Find one record by condition
     */
    public function findOne($condition) {
        $results = $this->find($condition);
        return !empty($results) ? array_values($results)[0] : null;
    }

    /**
     * Count records by condition
     */
    public function count($condition = null) {
        if ($condition === null) {
            return count($this->data);
        }
        return count($this->find($condition));
    }

    /**
     * Generate unique ID
     */
    private function generateId() {
        return uniqid() . bin2hex(random_bytes(4));
    }

    /**
     * Get last inserted ID
     */
    public function lastInsertId() {
        end($this->data);
        return key($this->data);
    }

    /**
     * Backup database
     */
    public function backup($backupPath) {
        $backupFile = $backupPath . 'backup_' . date('Y-m-d_H-i-s') . '.json';
        return copy($this->filePath, $backupFile);
    }

    /**
     * Optimize database
     */
    public function optimize() {
        $this->save();
        return true;
    }
}
?>