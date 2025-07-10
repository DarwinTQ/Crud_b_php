<?php
// Conexion a la base de datos
require_once 'conexion.php';

// --- LÓGICA DE MANEJO DE DATOS (CONTROLADOR) ---

try {
    // Instancia de la clase de conexión a la base de datos
    $db = new DatabaseConnection();
    $conn = $db->getConnection();

    // Variable para almacenar los datos del cliente que se va a editar
    $cliente_a_editar = null;
    $notification = null; // Para mensajes de éxito o error

    // --- LÓGICA PARA PROCESAR ACCIONES (POST y GET) ---

    //-- 1. LÓGICA PARA BORRAR (DELETE)--
    if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {
        $query = "DELETE FROM Clientes WHERE ID_cliente = :id_cliente";
        $stmt = oci_parse($conn, $query);
        oci_bind_by_name($stmt, ':id_cliente', $_GET['id']);
        
        if (oci_execute($stmt)) {
            // UUsamos sesiones para notificaciones
            session_start();
            $_SESSION['notification'] = ['type' => 'success', 'message' => 'Cliente eliminado con éxito.'];
            header("Location: " . $_SERVER['PHP_SELF']);
            exit;
        } else {
            $e = oci_error($stmt);
            throw new Exception("Error al borrar el cliente: " . $e['message']);
        }
    }

    // 2. LÓGICA DE CREAR (CREATE) Y ACTUALIZAR (UPDATE) -  manejando con POST
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        session_start();
        $id_cliente = $_POST['id_cliente'] ?? null;
        $dni = $_POST['dni'];
        $nombres = $_POST['nombres'];
        $aPaterno = $_POST['aPaterno'];
        $aMaterno = $_POST['aMaterno'];
        $direccion = $_POST['direccion'];
        $telefono = $_POST['telefono'];
        $email = $_POST['email'];

        if (isset($_POST['action']) && $_POST['action'] == 'update') {
            $query = "UPDATE Clientes SET DNI = :dni, Nombres = :nombres, APaterno = :aPaterno, AMaterno = :aMaterno, Direccion = :direccion, Telefono = :telefono, Email = :email WHERE ID_cliente = :id_cliente";
            $stmt = oci_parse($conn, $query);
            oci_bind_by_name($stmt, ':id_cliente', $id_cliente);
        } else {
            $id_nuevo_cliente = 'CLI-' . time(); 
            $query = "INSERT INTO Clientes (ID_cliente, DNI, Nombres, APaterno, AMaterno, Direccion, Telefono, Email, Fecha_registro) VALUES (:id_cliente, :dni, :nombres, :aPaterno, :aMaterno, :direccion, :telefono, :email, SYSDATE)";
            $stmt = oci_parse($conn, $query);
            oci_bind_by_name($stmt, ':id_cliente', $id_nuevo_cliente);
        }
        
        oci_bind_by_name($stmt, ':dni', $dni);
        oci_bind_by_name($stmt, ':nombres', $nombres);
        oci_bind_by_name($stmt, ':aPaterno', $aPaterno);
        oci_bind_by_name($stmt, ':aMaterno', $aMaterno);
        oci_bind_by_name($stmt, ':direccion', $direccion);
        oci_bind_by_name($stmt, ':telefono', $telefono);
        oci_bind_by_name($stmt, ':email', $email);

        if (oci_execute($stmt)) {
            $action_type = ($_POST['action'] == 'update') ? 'actualizado' : 'creado';
            $_SESSION['notification'] = ['type' => 'success', 'message' => "Cliente {$action_type} correctamente."];
        } else {
            $e = oci_error($stmt);
            $_SESSION['notification'] = ['type' => 'error', 'message' => 'Error al guardar: ' . $e['message']];
        }
        header("Location: " . $_SERVER['PHP_SELF']);
        exit;
    }
    
    // Iniciar sesión para recuperar notificaciones ss
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }
    if (isset($_SESSION['notification'])) {
        $notification = $_SESSION['notification'];
        unset($_SESSION['notification']);
    }

    // 3. LÓGICA DE CARGADO (READ) - Cargar datos del cliente a editar
    if (isset($_GET['action']) && $_GET['action'] == 'edit' && isset($_GET['id'])) {
        $query = "SELECT ID_cliente, DNI, Nombres, APaterno, AMaterno, Direccion, Telefono, Email FROM Clientes WHERE ID_cliente = :id_cliente";
        $stmt = oci_parse($conn, $query);
        oci_bind_by_name($stmt, ':id_cliente', $_GET['id']);
        oci_execute($stmt);
        $cliente_a_editar = oci_fetch_assoc($stmt);
    }

    // --- LÓGICA PARA LEER (READ) ---
    $query_select = "SELECT ID_cliente, DNI, Nombres, APaterno, AMaterno, Telefono, Email, TO_CHAR(Fecha_registro, 'DD/MM/YYYY HH24:MI') AS Fecha_registro_f FROM Clientes ORDER BY Fecha_registro DESC";
    $stid = oci_parse($conn, $query_select);
    oci_execute($stid);

} catch (Exception $e) {
    $error_message = $e->getMessage();
}

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CRUD</title>
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- Google Fonts: Poppins -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
    
    <!-- Phosphor Icons -->
    <script src="https://unpkg.com/@phosphor-icons/web"></script>
    
    <style>
        /* --- ESTILOS FUTURISTAS --- */
        :root {
            --color-bg: #111827; /* Azul oscuro casi negro */
            --color-primary: #00f2ea; /* Cian neón */
            --color-secondary: #ff00ff; /* Magenta neón */
            --color-accent: #39ff14; /* Verde lima neón */
            --color-text: #e5e7eb; /* Gris claro */
            --color-glass: rgba(31, 41, 55, 0.6); /* Gris azulado semitransparente */
            --color-border: rgba(0, 242, 234, 0.3); /* Borde cian semitransparente */
        }

        body {
            background-color: var(--color-bg);
            background-image: linear-gradient(45deg, rgba(0,0,0,0.95) 0%, rgba(17, 24, 39, 0.95) 100%),
                              url('https://www.transparenttextures.com/patterns/carbon-fibre-v2.png');
            color: var(--color-text);
            font-family: 'Poppins', sans-serif;
        }

        .container-glass {
            background: var(--color-glass);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            border: 1px solid var(--color-border);
            border-radius: 16px;
            box-shadow: 0 8px 32px 0 rgba(0, 242, 234, 0.1);
        }

        .title-neon {
            text-shadow: 0 0 5px var(--color-primary), 0 0 10px var(--color-primary), 0 0 20px var(--color-primary);
        }

        .input-futuristic {
            background: rgba(17, 24, 39, 0.8);
            border: 1px solid var(--color-border);
            color: var(--color-text);
            transition: all 0.3s ease;
        }
        .input-futuristic:focus {
            outline: none;
            border-color: var(--color-primary);
            box-shadow: 0 0 15px rgba(0, 242, 234, 0.5);
        }
        .input-futuristic::placeholder {
            color: #9ca3af;
        }

        .btn-glow {
            position: relative;
            overflow: hidden;
            z-index: 1;
            transition: all 0.3s ease;
        }
        .btn-glow:before {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 300%;
            height: 300%;
            background: radial-gradient(circle, rgba(255,255,255,0.2) 0%, rgba(255,255,255,0) 60%);
            transform: translate(-50%, -50%) scale(0);
            transition: transform 0.5s ease;
            z-index: -1;
        }
        .btn-glow:hover:before {
            transform: translate(-50%, -50%) scale(1);
        }
        .btn-glow:hover {
            box-shadow: 0 0 20px var(--shadow-color);
        }
        
        .table-futuristic th {
            color: var(--color-primary);
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        .table-futuristic tr {
            border-color: var(--color-border);
            transition: background-color 0.3s ease;
        }
        .table-futuristic tr:hover {
            background-color: rgba(0, 242, 234, 0.05);
        }
    </style>
</head>
<body class="p-4 md:p-8">

    <div class="max-w-7xl mx-auto">
        
        <header class="text-center mb-10">
            <h1 class="text-4xl md:text-5xl font-bold title-neon tracking-widest">CRUD</h1>
            <p class="text-cyan-400/80">Sistema de gestio CRUDD</p>
        </header>

        <!-- Notificaciones -->
        <?php if (isset($error_message)): ?>
            <div class="container-glass p-4 mb-6 border-red-500/50 text-red-300">
                <strong class="font-bold">Error de Sistema:</strong>
                <span><?php echo htmlspecialchars($error_message); ?></span>
            </div>
        <?php elseif ($notification): ?>
            <div class="container-glass p-4 mb-6 <?php echo $notification['type'] == 'success' ? 'border-green-400/50 text-green-300' : 'border-red-500/50 text-red-300'; ?>">
                <?php echo htmlspecialchars($notification['message']); ?>
            </div>
        <?php endif; ?>

        <!-- Formulario -->
        <div class="container-glass p-6 md:p-8 mb-10">
            <h2 class="text-2xl font-semibold mb-6 flex items-center gap-2">
                <i class="ph-bold <?php echo $cliente_a_editar ? 'ph-user-circle-gear' : 'ph-user-circle-plus'; ?> text-3xl text-cyan-300"></i>
                <span><?php echo $cliente_a_editar ? 'MODIFICAR REGISTRO' : 'NUEVO REGISTRO'; ?></span>
            </h2>
            <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="POST">
                <input type="hidden" name="action" value="<?php echo $cliente_a_editar ? 'update' : 'create'; ?>">
                <?php if ($cliente_a_editar): ?>
                    <input type="hidden" name="id_cliente" value="<?php echo htmlspecialchars($cliente_a_editar['ID_CLIENTE']); ?>">
                <?php endif; ?>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <input class="input-futuristic p-3 rounded-lg" type="text" name="dni" placeholder="DNI" required value="<?php echo htmlspecialchars($cliente_a_editar['DNI'] ?? ''); ?>">
                    <input class="input-futuristic p-3 rounded-lg" type="text" name="nombres" placeholder="Nombres" required value="<?php echo htmlspecialchars($cliente_a_editar['NOMBRES'] ?? ''); ?>">
                    <input class="input-futuristic p-3 rounded-lg" type="text" name="aPaterno" placeholder="Apellido Paterno" required value="<?php echo htmlspecialchars($cliente_a_editar['APATERNO'] ?? ''); ?>">
                    <input class="input-futuristic p-3 rounded-lg" type="text" name="aMaterno" placeholder="Apellido Materno" required value="<?php echo htmlspecialchars($cliente_a_editar['AMATERNO'] ?? ''); ?>">
                    <input class="input-futuristic p-3 rounded-lg" type="text" name="direccion" placeholder="Dirección" required value="<?php echo htmlspecialchars($cliente_a_editar['DIRECCION'] ?? ''); ?>">
                    <input class="input-futuristic p-3 rounded-lg" type="text" name="telefono" placeholder="Teléfono" required value="<?php echo htmlspecialchars($cliente_a_editar['TELEFONO'] ?? ''); ?>">
                    <input class="input-futuristic p-3 rounded-lg col-span-1 md:col-span-2" type="email" name="email" placeholder="Email" required value="<?php echo htmlspecialchars($cliente_a_editar['EMAIL'] ?? ''); ?>">
                </div>
                <div class="mt-6 flex justify-end space-x-4">
                    <?php if ($cliente_a_editar): ?>
                        <a href="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" class="btn-glow bg-gray-600/80 hover:bg-gray-500/80 text-white font-bold py-2 px-4 rounded-lg flex items-center gap-2" style="--shadow-color: #9ca3af;">
                            <i class="ph-bold ph-x-circle"></i> Cancelar
                        </a>
                    <?php endif; ?>
                    <button type="submit" class="btn-glow bg-cyan-600/80 hover:bg-cyan-500/80 text-white font-bold py-2 px-4 rounded-lg flex items-center gap-2" style="--shadow-color: var(--color-primary);">
                        <i class="ph-bold ph-database"></i> <?php echo $cliente_a_editar ? 'Actualizar Datos' : 'Guardar en Base de Datos'; ?>
                    </button>
                </div>
            </form>
        </div>

        <!-- Tabla -->
        <div class="container-glass p-6 md:p-8">
            <h2 class="text-2xl font-semibold mb-6 flex items-center gap-2">
                 <i class="ph-bold ph-list-numbers text-3xl text-cyan-300"></i>
                 <span>REGISTROS ACTIVOS</span>
            </h2>
            <div class="overflow-x-auto">
                <table class="w-full text-left table-futuristic">
                    <thead>
                        <tr class="border-b-2 border-cyan-500/30">
                            <th class="p-4">DNI</th>
                            <th class="p-4">Nombre Completo</th>
                            <th class="p-4 hidden md:table-cell">Email</th>
                            <th class="p-4 hidden lg:table-cell">Fecha Registro</th>
                            <th class="p-4 text-center">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (isset($stid) && oci_fetch_all($stid, $rows, 0, -1, OCI_FETCHSTATEMENT_BY_ROW) > 0): ?>
                            <?php foreach ($rows as $row): ?>
                                <tr class="border-b">
                                    <td class="p-4 font-mono"><?php echo htmlspecialchars($row['DNI']); ?></td>
                                    <td class="p-4"><?php echo htmlspecialchars($row['NOMBRES'] . ' ' . $row['APATERNO'] . ' ' . $row['AMATERNO']); ?></td>
                                    <td class="p-4 hidden md:table-cell"><?php echo htmlspecialchars($row['EMAIL']); ?></td>
                                    <td class="p-4 hidden lg:table-cell font-mono"><?php echo htmlspecialchars($row['FECHA_REGISTRO_F']); ?></td>
                                    <td class="p-4 flex justify-center space-x-2">
                                        <a href="?action=edit&id=<?php echo $row['ID_CLIENTE']; ?>" class="btn-glow bg-green-600/80 hover:bg-green-500/80 p-2 rounded-full" style="--shadow-color: var(--color-accent);" title="Editar">
                                            <i class="ph ph-pencil-simple text-xl"></i>
                                        </a>
                                        <a href="?action=delete&id=<?php echo $row['ID_CLIENTE']; ?>" class="btn-glow bg-red-600/80 hover:bg-red-500/80 p-2 rounded-full" style="--shadow-color: #ff0000;" onclick="return confirm('Confirmar eliminación de registro. Esta acción es irreversible.');" title="Eliminar">
                                            <i class="ph ph-trash-simple text-xl"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                             <tr>
                                <td colspan="5" class="text-center p-8 text-gray-400">-- No se encontraron registros en la base de datos --</td>
                             </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <?php
    if (isset($stid)) oci_free_statement($stid);
    if (isset($db)) $db->closeConnection();
    ?>
<!-- ========== INICIO DEL FOOTER DD ========== -->
<footer class="mt-12">
    <div class="container-glass max-w-7xl mx-auto p-6 text-center md:text-left">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 items-center">
            
            <!-- Sección de Copyright y Versión -->
            <div class="text-sm text-gray-400">
                <p>&copy; 2025 PAPITA OWNER</p>
                <p>Todos los derechos reservados.</p>
            </div>

            <!-- Sección de Enlaces y Redes Sociales -->
            <div class="flex justify-center items-center gap-4">
                <a href="#" class="btn-glow text-2xl text-gray-300 hover:text-cyan-400" style="--shadow-color: var(--color-primary);" title="GitHub">
                    <i class="ph-bold ph-github-logo"></i>
                </a>
                <a href="#" class="btn-glow text-2xl text-gray-300 hover:text-cyan-400" style="--shadow-color: var(--color-primary);" title="LinkedIn">
                    <i class="ph-bold ph-linkedin-logo"></i>
                </a>
                <a href="#" class="btn-glow text-2xl text-gray-300 hover:text-cyan-400" style="--shadow-color: var(--color-primary);" title="Website">
                    <i class="ph-bold ph-globe"></i>
                </a>
            </div>

            <!-- Sección de Estado del Sistema -->
            <div class="flex justify-center md:justify-end items-center gap-3">
                <div class="relative flex h-3 w-3">
                    <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-green-400 opacity-75"></span>
                    <span class="relative inline-flex rounded-full h-3 w-3 bg-green-500"></span>
                </div>
                <span class="text-sm font-semibold text-green-300">SYSTEM STATUS: OPERATIONAL</span>
            </div>

        </div>
    </div>
</footer>
<!-- ========== FIN DEL FOOTER ========== -->

</body>
</html>
