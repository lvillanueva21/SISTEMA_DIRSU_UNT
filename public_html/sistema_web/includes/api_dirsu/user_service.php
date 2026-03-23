<?php
/**
 * Servicio de consulta de usuarios para API Dirsu.
 * Compatible con entornos legacy (mysqli + SQL simple).
 */

include_once __DIR__ . '/../db_connection.php';

if (!function_exists('rsu_api_user_value')) {
    function rsu_api_user_value($row, $key)
    {
        if (!is_array($row) || !isset($row[$key])) {
            return null;
        }
        return $row[$key];
    }
}

if (!function_exists('rsu_api_user_sede_map')) {
    function rsu_api_user_sede_map()
    {
        return array(
            1 => 'Trujillo',
            2 => 'Jequetepeque',
            3 => 'Huamachuco',
            4 => 'Santiago de Chuco'
        );
    }
}

if (!function_exists('rsu_api_user_table_exists')) {
    function rsu_api_user_table_exists($conexion, $table_name)
    {
        static $cache = array();

        if (!$conexion instanceof mysqli) {
            return false;
        }

        $table_name = trim((string)$table_name);
        if ($table_name === '') {
            return false;
        }

        if (isset($cache[$table_name])) {
            return $cache[$table_name];
        }

        $safe = mysqli_real_escape_string($conexion, $table_name);
        $sql = "SHOW TABLES LIKE '" . $safe . "'";
        $res = @mysqli_query($conexion, $sql);

        $exists = ($res instanceof mysqli_result) && ($res->num_rows > 0);
        if ($res instanceof mysqli_result) {
            mysqli_free_result($res);
        }

        $cache[$table_name] = $exists;
        return $exists;
    }
}

if (!function_exists('rsu_api_user_fetch_contact')) {
    function rsu_api_user_fetch_contact($conexion, $usuario)
    {
        $usuario = trim((string)$usuario);

        $contact = array(
            'email' => null,
            'telefono' => null,
            'telefono_asistente' => null,
            'correo_asistente' => null,
            'origen' => 'sin_registro'
        );

        if ($usuario === '' || !$conexion instanceof mysqli) {
            return $contact;
        }

        $tiene_directorio = rsu_api_user_table_exists($conexion, 'directorio');
        if ($tiene_directorio) {
            $sql_dir = "SELECT email, telefono, telefono_asistente, correo_asistente FROM directorio WHERE usuario = ? LIMIT 1";
            $stmt_dir = @mysqli_prepare($conexion, $sql_dir);
            if ($stmt_dir) {
                mysqli_stmt_bind_param($stmt_dir, 's', $usuario);
                mysqli_stmt_execute($stmt_dir);
                $res_dir = mysqli_stmt_get_result($stmt_dir);
                if ($res_dir instanceof mysqli_result) {
                    $row_dir = mysqli_fetch_assoc($res_dir);
                    mysqli_free_result($res_dir);

                    if (is_array($row_dir)) {
                        $contact['email'] = rsu_api_user_value($row_dir, 'email');
                        $contact['telefono'] = rsu_api_user_value($row_dir, 'telefono');
                        $contact['telefono_asistente'] = rsu_api_user_value($row_dir, 'telefono_asistente');
                        $contact['correo_asistente'] = rsu_api_user_value($row_dir, 'correo_asistente');
                        $contact['origen'] = 'directorio';
                    }
                }
                mysqli_stmt_close($stmt_dir);
            }
        }

        $tiene_usuario_contactos = rsu_api_user_table_exists($conexion, 'usuario_contactos');
        if ($tiene_usuario_contactos) {
            $sql_uc = "SELECT email, telefono FROM usuario_contactos WHERE usuario = ? LIMIT 1";
            $stmt_uc = @mysqli_prepare($conexion, $sql_uc);
            if ($stmt_uc) {
                mysqli_stmt_bind_param($stmt_uc, 's', $usuario);
                mysqli_stmt_execute($stmt_uc);
                $res_uc = mysqli_stmt_get_result($stmt_uc);
                if ($res_uc instanceof mysqli_result) {
                    $row_uc = mysqli_fetch_assoc($res_uc);
                    mysqli_free_result($res_uc);

                    if (is_array($row_uc)) {
                        $email_uc = rsu_api_user_value($row_uc, 'email');
                        $telefono_uc = rsu_api_user_value($row_uc, 'telefono');

                        if (($contact['email'] === null || trim((string)$contact['email']) === '') && $email_uc !== null && trim((string)$email_uc) !== '') {
                            $contact['email'] = $email_uc;
                        }

                        if (($contact['telefono'] === null || trim((string)$contact['telefono']) === '') && $telefono_uc !== null && trim((string)$telefono_uc) !== '') {
                            $contact['telefono'] = $telefono_uc;
                        }

                        if ($contact['origen'] === 'sin_registro') {
                            $contact['origen'] = 'usuario_contactos';
                        } elseif ($contact['origen'] === 'directorio') {
                            $contact['origen'] = 'directorio+fallback_usuario_contactos';
                        }
                    }
                }
                mysqli_stmt_close($stmt_uc);
            }
        }

        return $contact;
    }
}

if (!function_exists('rsu_api_user_query_base')) {
    function rsu_api_user_query_base()
    {
        return "SELECT
                    u.id,
                    u.usuario,
                    u.id_rol,
                    u.nombres,
                    u.apellidos,
                    u.id_escuela,
                    u.id_sede,
                    u.id_depa,
                    u.id_py,
                    r.nombre AS rol_nombre,
                    d.nombre AS depa_nombre,
                    d.id_facultad AS depa_facultad_id,
                    f_depa.nombre AS depa_facultad_nombre,
                    e.nombre_escuela,
                    e.id_facultad AS escuela_facultad_id,
                    f_esc.nombre AS escuela_facultad_nombre,
                    f_rol.nombre AS rol_facultad_nombre
                FROM usuarios u
                LEFT JOIN rol r ON r.id = u.id_rol
                LEFT JOIN departamentos d ON d.id = u.id_depa
                LEFT JOIN facultades f_depa ON f_depa.id = d.id_facultad
                LEFT JOIN escuelas e ON e.id_escuela = u.id_escuela
                LEFT JOIN facultades f_esc ON f_esc.id = e.id_facultad
                LEFT JOIN facultades f_rol ON f_rol.id = u.id_escuela";
    }
}

if (!function_exists('rsu_api_user_choose_facultad')) {
    function rsu_api_user_choose_facultad($row)
    {
        $role_id = (int)rsu_api_user_value($row, 'id_rol');
        $id_escuela = (int)rsu_api_user_value($row, 'id_escuela');
        $id_depa = (int)rsu_api_user_value($row, 'id_depa');

        $depa_facultad_id = (int)rsu_api_user_value($row, 'depa_facultad_id');
        $depa_facultad_nombre = rsu_api_user_value($row, 'depa_facultad_nombre');
        $escuela_facultad_id = (int)rsu_api_user_value($row, 'escuela_facultad_id');
        $escuela_facultad_nombre = rsu_api_user_value($row, 'escuela_facultad_nombre');
        $rol_facultad_nombre = rsu_api_user_value($row, 'rol_facultad_nombre');

        $facultad_id = 0;
        $facultad_nombre = null;
        $origen = 'sin_facultad';

        if ($role_id === 3 || $role_id === 5) {
            if ($id_escuela > 0) {
                $facultad_id = $id_escuela;
                $facultad_nombre = $rol_facultad_nombre;
                $origen = 'id_escuela_como_facultad';
            }
        } elseif ($id_depa > 0 && $depa_facultad_id > 0) {
            $facultad_id = $depa_facultad_id;
            $facultad_nombre = $depa_facultad_nombre;
            $origen = 'departamento';
        } elseif ($escuela_facultad_id > 0) {
            $facultad_id = $escuela_facultad_id;
            $facultad_nombre = $escuela_facultad_nombre;
            $origen = 'escuela';
        } elseif ($id_escuela > 0) {
            $facultad_id = $id_escuela;
            $facultad_nombre = $rol_facultad_nombre;
            $origen = 'id_escuela_directo';
        }

        return array(
            'id' => $facultad_id > 0 ? $facultad_id : null,
            'nombre' => ($facultad_nombre !== null && trim((string)$facultad_nombre) !== '') ? $facultad_nombre : null,
            'origen' => $origen
        );
    }
}

if (!function_exists('rsu_api_user_build_payload')) {
    function rsu_api_user_build_payload($row, $contact)
    {
        $sede_map = rsu_api_user_sede_map();

        $id_sede = (int)rsu_api_user_value($row, 'id_sede');
        $id_escuela = (int)rsu_api_user_value($row, 'id_escuela');
        $id_depa = (int)rsu_api_user_value($row, 'id_depa');
        $role_id = (int)rsu_api_user_value($row, 'id_rol');

        $facultad = rsu_api_user_choose_facultad($row);

        $escuela_nombre = rsu_api_user_value($row, 'nombre_escuela');
        if ($role_id === 3 || $role_id === 5) {
            $escuela_nombre = null;
        }

        return array(
            'id' => (int)rsu_api_user_value($row, 'id'),
            'usuario' => (string)rsu_api_user_value($row, 'usuario'),
            'codigo_usuario' => (string)rsu_api_user_value($row, 'usuario'),
            'nombres' => (string)rsu_api_user_value($row, 'nombres'),
            'apellidos' => (string)rsu_api_user_value($row, 'apellidos'),
            'nombres_completos' => trim((string)rsu_api_user_value($row, 'nombres') . ' ' . (string)rsu_api_user_value($row, 'apellidos')),
            'rol' => array(
                'id' => $role_id,
                'nombre' => rsu_api_user_value($row, 'rol_nombre')
            ),
            'sede' => array(
                'id' => $id_sede > 0 ? $id_sede : null,
                'nombre' => isset($sede_map[$id_sede]) ? $sede_map[$id_sede] : null
            ),
            'facultad' => $facultad,
            'escuela' => array(
                'id' => $id_escuela > 0 ? $id_escuela : null,
                'nombre' => $escuela_nombre
            ),
            'departamento_academico' => array(
                'id' => $id_depa > 0 ? $id_depa : null,
                'nombre' => rsu_api_user_value($row, 'depa_nombre')
            ),
            'contacto' => array(
                'email' => rsu_api_user_value($contact, 'email'),
                'telefono' => rsu_api_user_value($contact, 'telefono'),
                'telefono_asistente' => rsu_api_user_value($contact, 'telefono_asistente'),
                'correo_asistente' => rsu_api_user_value($contact, 'correo_asistente'),
                'origen' => rsu_api_user_value($contact, 'origen')
            ),
            'proyecto' => array(
                'id' => (int)rsu_api_user_value($row, 'id_py')
            )
        );
    }
}

if (!function_exists('rsu_api_user_get')) {
    function rsu_api_user_get($id, $usuario)
    {
        $id = (int)$id;
        $usuario = trim((string)$usuario);

        if ($id <= 0 && $usuario === '') {
            return array(
                'ok' => false,
                'error_code' => 'missing_filter',
                'error_message' => 'Debes indicar id o usuario para la consulta.'
            );
        }

        $conexion = rsu_db_connect();
        if (!$conexion) {
            return array(
                'ok' => false,
                'error_code' => 'db_connection_error',
                'error_message' => 'No fue posible conectar con la base de datos.'
            );
        }

        $sql = rsu_api_user_query_base();
        $bind_type = '';
        $bind_value = null;
        $search_mode = '';

        if ($id > 0) {
            $sql .= ' WHERE u.id = ? LIMIT 1';
            $bind_type = 'i';
            $bind_value = $id;
            $search_mode = 'id';
        } else {
            $sql .= ' WHERE u.usuario = ? LIMIT 1';
            $bind_type = 's';
            $bind_value = $usuario;
            $search_mode = 'usuario';
        }

        $stmt = @mysqli_prepare($conexion, $sql);
        if (!$stmt) {
            return array(
                'ok' => false,
                'error_code' => 'db_prepare_error',
                'error_message' => 'No se pudo preparar la consulta del usuario.'
            );
        }

        mysqli_stmt_bind_param($stmt, $bind_type, $bind_value);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);

        $row = null;
        if ($res instanceof mysqli_result) {
            $row = mysqli_fetch_assoc($res);
            mysqli_free_result($res);
        }

        mysqli_stmt_close($stmt);

        if (!is_array($row)) {
            return array(
                'ok' => false,
                'error_code' => 'not_found',
                'error_message' => 'No se encontro un usuario con el criterio enviado.'
            );
        }

        $contact = rsu_api_user_fetch_contact($conexion, (string)rsu_api_user_value($row, 'usuario'));
        $payload = rsu_api_user_build_payload($row, $contact);

        return array(
            'ok' => true,
            'data' => $payload,
            'meta' => array(
                'search_mode' => $search_mode,
                'search_value' => $search_mode === 'id' ? $id : $usuario
            )
        );
    }
}
