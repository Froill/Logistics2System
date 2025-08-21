<?php
require 'db.php';

// Fetch all rows from a table
function fetchAll($table)
{
    global $conn;
    $result = $conn->query("SELECT * FROM $table");
    return $result->fetch_all(MYSQLI_ASSOC);
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

// Delete row
function deleteData($table, $id)
{
    global $conn;
    $id = (int)$id;
    return $conn->query("DELETE FROM $table WHERE id = $id");
}
