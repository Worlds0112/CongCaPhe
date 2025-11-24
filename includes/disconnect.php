<?php
function disconnect_db()
{
    global $conn;
    if ($conn) {
        mysqli_close($conn);
        $conn = null;
    }
}
?>