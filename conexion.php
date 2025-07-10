<?php
class DatabaseConnection {
    private $dbconn;

    // Data
    private $user = 'SYSTEM';           
    private $pass = 'admin';       
    private $host = 'localhost/XE';    

    public function __construct() {
        $this->dbconn = oci_connect($this->user, $this->pass, $this->host, 'AL32UTF8');

        if (!$this->dbconn) {
            $e = oci_error();
            trigger_error(htmlentities($e['message'], ENT_QUOTES), E_USER_ERROR);
        }
    }

    public function getConnection() {
        return $this->dbconn;
    }

    public function closeConnection() {
        if ($this->dbconn) {
            oci_close($this->dbconn);
        }
    }

/*USO
require_once 'conexion.php';
$db = new DatabaseConnection();
$conn = $db->getConnection();
 Usas $conn normalmente
*/
}
?>
