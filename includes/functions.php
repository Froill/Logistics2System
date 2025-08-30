<?php

require 'db.php';

// Fetch all rows from a table
function fetchAll($table)
{
    global $conn;
    $result = $conn->query("SELECT * FROM $table");
    return $result->fetch_all(MYSQLI_ASSOC);
}

// Fetch all rows from a custom query with parameters (prepared statement)
function fetchAllQuery($sql, $params = [])
{
    global $conn;
    $stmt = $conn->prepare($sql);
    if ($params && $stmt) {
        // Dynamically generate types string
        $types = str_repeat('s', count($params));
        $stmt->bind_param($types, ...$params);
    }
    if ($stmt && $stmt->execute()) {
        $result = $stmt->get_result();
        $rows = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        return $rows;
    }
    return [];
}

// Insert into table
function insertData($table, $data)
{
    global $conn;
    $columns = implode(",", array_keys($data));
    $values  = implode("','", array_map([$conn, 'real_escape_string'], array_values($data)));
    $sql = "INSERT INTO $table ($columns) VALUES ('$values')";
    return $conn->query($sql);
}


// Fetch a single row by ID
function fetchById($table, $id)
{
    global $conn;
    $id = (int)$id;
    $sql = "SELECT * FROM $table WHERE id = ? LIMIT 1";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);
    if ($stmt->execute()) {
        $result = $stmt->get_result();
        return $result->fetch_assoc();
    }
    return null;
}

// Update row by id
function updateData($table, $id, $data)
{
    global $conn;
    $id = (int)$id;
    $set = [];
    foreach ($data as $col => $val) {
        $set[] = "$col='" . $conn->real_escape_string($val) . "'";
    }
    $setStr = implode(", ", $set);
    $sql = "UPDATE $table SET $setStr WHERE id = $id";
    return $conn->query($sql);
}

// Delete row
function deleteData($table, $id)
{
    global $conn;
    $id = (int)$id;
    return $conn->query("DELETE FROM $table WHERE id = $id");
}

