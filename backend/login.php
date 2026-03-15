<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit();
}

require_once 'db_connection.php';

function send_response($success, $status, $code = 200, $extra = []) {
    http_response_code($code);
    echo json_encode(array_merge([
        'success' => $success,
        'status' => $status
    ], $extra));
    exit();
}

function table_exists($conn, $table) {
    $name = $conn->real_escape_string($table);
    $res = $conn->query("SHOW TABLES LIKE '$name'");
    return $res && $res->num_rows > 0;
}

function column_exists($conn, $table, $column) {
    $safeTable = preg_replace('/[^a-zA-Z0-9_]/', '', $table);
    if ($safeTable === '') return false;
    $col = $conn->real_escape_string($column);
    $res = $conn->query("SHOW COLUMNS FROM `$safeTable` LIKE '$col'");
    return $res && $res->num_rows > 0;
}

function get_owner_id($conn, $userId, $email) {
    if (!table_exists($conn, 'turf_owners')) return 0;

    if (column_exists($conn, 'turf_owners', 'user_id') && $userId > 0) {
        $stmt = $conn->prepare('SELECT owner_id FROM turf_owners WHERE user_id = ? LIMIT 1');
        if ($stmt) {
            $stmt->bind_param('i', $userId);
            $stmt->execute();
            $res = $stmt->get_result();
            $row = $res ? $res->fetch_assoc() : null;
            $stmt->close();
            if ($row && isset($row['owner_id'])) {
                return (int)$row['owner_id'];
            }
        }
    }

    if ($email !== '' && column_exists($conn, 'turf_owners', 'email')) {
        $stmt = $conn->prepare('SELECT owner_id FROM turf_owners WHERE LOWER(email) = LOWER(?) LIMIT 1');
        if ($stmt) {
            $stmt->bind_param('s', $email);
            $stmt->execute();
            $res = $stmt->get_result();
            $row = $res ? $res->fetch_assoc() : null;
            $stmt->close();
            if ($row && isset($row['owner_id'])) {
                return (int)$row['owner_id'];
            }
        }
    }

    return 0;
}

function verify_password_with_upgrade($conn, $table, $pkColumn, $pkValue, $storedHash, $plainPassword) {
    if ($storedHash === '') {
        return false;
    }

    $passwordOk = password_verify($plainPassword, $storedHash);
    if ($passwordOk) {
        return true;
    }

    if (!hash_equals($storedHash, $plainPassword)) {
        return false;
    }

    $newHash = password_hash($plainPassword, PASSWORD_DEFAULT);
    $sql = "UPDATE `$table` SET password_hash = ? WHERE `$pkColumn` = ? LIMIT 1";
    $stmt = $conn->prepare($sql);
    if ($stmt) {
        if (is_int($pkValue)) {
            $stmt->bind_param('si', $newHash, $pkValue);
        } else {
            $stmt->bind_param('ss', $newHash, $pkValue);
        }
        $stmt->execute();
        $stmt->close();
    }

    return true;
}

$contentType = $_SERVER['CONTENT_TYPE'] ?? '';
if (stripos($contentType, 'application/json') !== false) {
    $data = json_decode(file_get_contents('php://input'), true);
} else {
    $data = $_POST;
    if (empty($data)) {
        parse_str(file_get_contents('php://input'), $data);
    }
}

if (!is_array($data) || empty($data)) {
    send_response(false, 'No data received.', 400);
}

$email = strtolower(trim((string)($data['email'] ?? '')));
$password = (string)($data['password'] ?? '');

if ($email === '' || $password === '') {
    send_response(false, 'Email and password are required.', 400);
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    send_response(false, 'Invalid email address.', 400);
}

$userFound = false;
$userPasswordMatched = false;
$userStatus = 'active';

if (table_exists($conn, 'users')) {
    $hasRoleColumn = column_exists($conn, 'users', 'role');
    $selectRole = $hasRoleColumn ? ', role' : '';

    $sql = "SELECT user_id, full_name, email, password_hash, status$selectRole
            FROM users
            WHERE LOWER(email) = LOWER(?)
            LIMIT 1";

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        send_response(false, 'Server error while preparing login.', 500);
    }

    $stmt->bind_param('s', $email);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result ? $result->fetch_assoc() : null;
    $stmt->close();

    if ($user) {
        $userFound = true;
        $userStatus = strtolower(trim((string)($user['status'] ?? 'active')));

        if ($userStatus === 'active') {
            $storedHash = (string)($user['password_hash'] ?? '');
            $userPasswordMatched = verify_password_with_upgrade($conn, 'users', 'user_id', (int)$user['user_id'], $storedHash, $password);
        }

        if ($userStatus === 'active' && $userPasswordMatched) {
            $roleValue = $hasRoleColumn ? strtolower(trim((string)($user['role'] ?? 'user'))) : 'user';
            $role = in_array($roleValue, ['user', 'owner', 'admin'], true) ? $roleValue : 'user';

            $ownerId = get_owner_id($conn, (int)$user['user_id'], (string)$user['email']);
            if ($role !== 'admin' && $ownerId > 0) {
                $role = 'owner';
            }

            $adminId = 0;
            if ($role === 'admin' && table_exists($conn, 'admins')) {
                $adminStmt = $conn->prepare('SELECT admin_id FROM admins WHERE LOWER(email) = LOWER(?) LIMIT 1');
                if ($adminStmt) {
                    $adminStmt->bind_param('s', $email);
                    $adminStmt->execute();
                    $adminRes = $adminStmt->get_result();
                    $adminRow = $adminRes ? $adminRes->fetch_assoc() : null;
                    $adminStmt->close();
                    $adminId = (int)($adminRow['admin_id'] ?? 0);
                }
            }

            // Compatibility: if admin role lives in users table only, keep admin access using that user id.
            if ($role === 'admin' && $adminId <= 0) {
                $adminId = (int)$user['user_id'];
            }

            send_response(true, 'Login successful.', 200, [
                'user' => [
                    'user_id' => (int)$user['user_id'],
                    'full_name' => $user['full_name'],
                    'email' => $user['email'],
                    'status' => $userStatus,
                    'role' => $role,
                    'owner_id' => $ownerId,
                    'admin_id' => $adminId
                ]
            ]);
        }
    }
}

if (table_exists($conn, 'admins')) {
    $adminSql = 'SELECT admin_id, full_name, email, password_hash FROM admins WHERE LOWER(email) = LOWER(?) LIMIT 1';
    $adminStmt = $conn->prepare($adminSql);
    if (!$adminStmt) {
        send_response(false, 'Server error while preparing admin login.', 500);
    }

    $adminStmt->bind_param('s', $email);
    $adminStmt->execute();
    $adminRes = $adminStmt->get_result();
    $admin = $adminRes ? $adminRes->fetch_assoc() : null;
    $adminStmt->close();

    if ($admin) {
        $storedHash = (string)($admin['password_hash'] ?? '');
        if (!verify_password_with_upgrade($conn, 'admins', 'admin_id', (int)$admin['admin_id'], $storedHash, $password)) {
            send_response(false, 'Invalid password.', 200);
        }

        send_response(true, 'Admin login successful.', 200, [
            'user' => [
                'user_id' => (int)$admin['admin_id'],
                'admin_id' => (int)$admin['admin_id'],
                'full_name' => $admin['full_name'],
                'email' => $admin['email'],
                'status' => 'active',
                'role' => 'admin',
                'owner_id' => 0
            ]
        ]);
    }
}

if ($userFound) {
    if ($userStatus !== 'active') {
        send_response(false, 'Account is not active.', 200);
    }

    if (!$userPasswordMatched) {
        send_response(false, 'Invalid password.', 200);
    }
}

send_response(false, 'User not found.', 200);
?>



