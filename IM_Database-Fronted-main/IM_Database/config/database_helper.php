<?php
/**
 * Database Helper Functions
 * Provides PostgreSQL-compatible functions to replace SQL Server functions
 */

/**
 * Execute a query with parameters
 */
function db_query($conn, $sql, $params = []) {
    if (empty($params)) {
        return pg_query($conn, $sql);
    }
    
    // Convert parameters to PostgreSQL format
    $escaped_params = array_map(function($param) use ($conn) {
        return pg_escape_literal($conn, $param);
    }, $params);
    
    // Replace ? placeholders with escaped parameters
    $param_count = 0;
    $sql = preg_replace_callback('/\?/', function() use ($escaped_params, &$param_count) {
        return $escaped_params[$param_count++];
    }, $sql);
    
    return pg_query($conn, $sql);
}

/**
 * Fetch a single row as associative array
 */
function db_fetch_assoc($result) {
    return pg_fetch_assoc($result);
}

/**
 * Fetch all rows as associative array
 */
function db_fetch_all($result) {
    $rows = [];
    while ($row = pg_fetch_assoc($result)) {
        $rows[] = $row;
    }
    return $rows;
}

/**
 * Begin transaction
 */
function db_begin_transaction($conn) {
    return pg_query($conn, "BEGIN");
}

/**
 * Commit transaction
 */
function db_commit($conn) {
    return pg_query($conn, "COMMIT");
}

/**
 * Rollback transaction
 */
function db_rollback($conn) {
    return pg_query($conn, "ROLLBACK");
}

/**
 * Get last inserted ID
 */
function db_last_insert_id($conn, $table = null) {
    $result = pg_query($conn, "SELECT lastval() AS id");
    $row = pg_fetch_assoc($result);
    return $row['id'];
}

/**
 * Check if query failed
 */
function db_error($conn) {
    return pg_last_error($conn);
}
