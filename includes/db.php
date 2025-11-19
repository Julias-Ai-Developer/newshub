<?php
// Prevent direct access
if (!defined('APP_ROOT')) {
    die('Direct access not permitted');
}

// Global database connection
$db = null;

/**
 * Get database connection
 */
function get_db_connection() {
    global $db;
    
    if ($db !== null) {
        return $db;
    }
    
    try {
        $db = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
        
        if ($db->connect_error) {
            error_log("Database connection failed: " . $db->connect_error);
            die("Database connection failed. Please try again later.");
        }
        
        $db->set_charset(DB_CHARSET);
        
        return $db;
    } catch (Exception $e) {
        error_log("Database exception: " . $e->getMessage());
        die("Database connection failed. Please try again later.");
    }
}

/**
 * Execute a prepared statement
 */
function db_query($query, $params = [], $types = '') {
    $db = get_db_connection();
    $stmt = $db->prepare($query);
    
    if (!$stmt) {
        error_log("Prepare failed: " . $db->error);
        return false;
    }
    
    if (!empty($params)) {
        if (empty($types)) {
            // Auto-detect types
            $types = '';
            foreach ($params as $param) {
                if (is_int($param)) {
                    $types .= 'i';
                } elseif (is_double($param)) {
                    $types .= 'd';
                } else {
                    $types .= 's';
                }
            }
        }
        $stmt->bind_param($types, ...$params);
    }
    
    $stmt->execute();
    
    if ($stmt->error) {
        error_log("Execute failed: " . $stmt->error);
        return false;
    }
    
    return $stmt;
}

/**
 * Fetch single row
 */
function db_fetch($query, $params = [], $types = '') {
    $stmt = db_query($query, $params, $types);
    
    if (!$stmt) {
        return null;
    }
    
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stmt->close();
    
    return $row;
}

/**
 * Fetch all rows
 */
function db_fetch_all($query, $params = [], $types = '') {
    $stmt = db_query($query, $params, $types);
    
    if (!$stmt) {
        return [];
    }
    
    $result = $stmt->get_result();
    $rows = [];
    
    while ($row = $result->fetch_assoc()) {
        $rows[] = $row;
    }
    
    $stmt->close();
    
    return $rows;
}

/**
 * Execute insert/update/delete query
 */
function db_execute($query, $params = [], $types = '') {
    $stmt = db_query($query, $params, $types);
    
    if (!$stmt) {
        return false;
    }
    
    $affected_rows = $stmt->affected_rows;
    $insert_id = $stmt->insert_id;
    $stmt->close();
    
    return ['affected_rows' => $affected_rows, 'insert_id' => $insert_id];
}

/**
 * Get last insert ID
 */
function db_insert_id() {
    $db = get_db_connection();
    return $db->insert_id;
}

/**
 * Escape string
 */
function db_escape($string) {
    $db = get_db_connection();
    return $db->real_escape_string($string);
}

/**
 * Close database connection
 */
function db_close() {
    global $db;
    if ($db !== null) {
        $db->close();
        $db = null;
    }
}

// Initialize connection
get_db_connection();