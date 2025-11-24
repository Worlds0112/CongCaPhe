<?php
global $conn;

function connect_db()
{
    global $conn;

    if (!$conn) {
        $conn = mysqli_connect('localhost', 'root', '', 'db_quanlycafe')
            or die('Không thể kết nối CSDL');
        mysqli_set_charset($conn, 'utf8');
    }
}
?>