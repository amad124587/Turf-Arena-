<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit();
}

include 'db_connection.php';

function send_response($success, $status, $code = 200, $extra = []) {
    http_response_code($code);
    echo json_encode(array_merge([
        'success' => $success,
        'status' => $status
    ], $extra));
    exit();
}

function save_turf_image_file($uploadedFile, $turfId) {
    if (!is_array($uploadedFile)) {
        return ['ok' => false, 'error' => 'Turf image is required.'];
    }

    if (($uploadedFile['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        return ['ok' => false, 'error' => 'Image upload failed. Please select a valid image.'];
    }

    $maxSize = 5 * 1024 * 1024; // 5MB
    if (($uploadedFile['size'] ?? 0) > $maxSize) {
        return ['ok' => false, 'error' => 'Image size must be 5MB or less.'];
    }

    $originalName = (string)($uploadedFile['name'] ?? '');
    $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
    $allowedExt = ['jpg', 'jpeg', 'png', 'webp'];
    if (!in_array($ext, $allowedExt, true)) {
        return ['ok' => false, 'error' => 'Only JPG, PNG, and WEBP images are allowed.'];
    }

    $tmpPath = (string)($uploadedFile['tmp_name'] ?? '');
    if ($tmpPath === '' || !is_uploaded_file($tmpPath)) {
        return ['ok' => false, 'error' => 'Invalid uploaded file.'];
    }

    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = $finfo ? finfo_file($finfo, $tmpPath) : '';
    if ($finfo) finfo_close($finfo);

    $allowedMime = ['image/jpeg', 'image/png', 'image/webp'];
    if (!in_array($mime, $allowedMime, true)) {
        return ['ok' => false, 'error' => 'Uploaded file is not a valid image.'];
    }

    $uploadDir = __DIR__ . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'turf_images';
    if (!is_dir($uploadDir) && !mkdir($uploadDir, 0755, true)) {
        return ['ok' => false, 'error' => 'Server cannot create image upload directory.'];
    }

    $safeRandom = bin2hex(random_bytes(6));
    $fileName = 'turf_' . (int)$turfId . '_' . time() . '_' . $safeRandom . '.' . $ext;
    $absolutePath = $uploadDir . DIRECTORY_SEPARATOR . $fileName;
    $relativeUrl = 'uploads/turf_images/' . $fileName;

    if (!move_uploaded_file($tmpPath, $absolutePath)) {
        return ['ok' => false, 'error' => 'Failed to save uploaded image.'];
    }

    return [
        'ok' => true,
        'absolute_path' => $absolutePath,
        'relative_url' => $relativeUrl
    ];
}

function get_owner_id_by_email($conn, $ownerEmail) {
    if ($ownerEmail === '') return 0;

    $stmt = $conn->prepare("SELECT owner_id FROM turf_owners WHERE LOWER(email) = LOWER(?) LIMIT 1");
    if (!$stmt) return 0;

    $stmt->bind_param('s', $ownerEmail);
    $stmt->execute();
    $res = $stmt->get_result();
    $row = $res ? $res->fetch_assoc() : null;
    $stmt->close();

    return $row && isset($row['owner_id']) ? (int)$row['owner_id'] : 0;
}

function find_user_for_owner_seed($conn, $ownerId, $ownerEmail) {
    if ($ownerEmail !== '') {
        $stmt = $conn->prepare("SELECT full_name, email, password_hash FROM users WHERE LOWER(email) = LOWER(?) LIMIT 1");
        if ($stmt) {
            $stmt->bind_param('s', $ownerEmail);
            $stmt->execute();
            $res = $stmt->get_result();
            $row = $res ? $res->fetch_assoc() : null;
            $stmt->close();
            if ($row) return $row;
        }
    }

    if ($ownerId > 0) {
        $stmt = $conn->prepare("SELECT full_name, email, password_hash FROM users WHERE user_id = ? LIMIT 1");
        if ($stmt) {
            $stmt->bind_param('i', $ownerId);
            $stmt->execute();
            $res = $stmt->get_result();
            $row = $res ? $res->fetch_assoc() : null;
            $stmt->close();
            if ($row) return $row;
        }
    }

    return null;
}

function create_owner_from_user_seed($conn, $userSeed) {
    if (!$userSeed || !is_array($userSeed)) return 0;

    $ownerEmail = strtolower(trim((string)($userSeed['email'] ?? '')));
    if ($ownerEmail === '') return 0;

    $existingOwnerId = get_owner_id_by_email($conn, $ownerEmail);
    if ($existingOwnerId > 0) return $existingOwnerId;

    $ownerName = trim((string)($userSeed['full_name'] ?? 'Owner'));
    if ($ownerName === '') $ownerName = 'Owner';

    $passwordHash = trim((string)($userSeed['password_hash'] ?? ''));
    if ($passwordHash === '') {
        $passwordHash = password_hash(uniqid('owner_seed_', true), PASSWORD_DEFAULT);
    }

    $stmt = $conn->prepare("INSERT INTO turf_owners (owner_name, email, password_hash, status) VALUES (?, ?, ?, 'verified')");
    if (!$stmt) return 0;

    $stmt->bind_param('sss', $ownerName, $ownerEmail, $passwordHash);
    $ok = $stmt->execute();
    $insertedId = $ok ? (int)$stmt->insert_id : 0;
    $stmt->close();

    if ($insertedId > 0) return $insertedId;

    return get_owner_id_by_email($conn, $ownerEmail);
}

function find_owner_id($conn, $ownerId, $ownerEmail) {
    if ($ownerId > 0) {
        $stmt = $conn->prepare("SELECT owner_id FROM turf_owners WHERE owner_id = ? LIMIT 1");
        if ($stmt) {
            $stmt->bind_param('i', $ownerId);
            $stmt->execute();
            $res = $stmt->get_result();
            $row = $res ? $res->fetch_assoc() : null;
            $stmt->close();
            if ($row && isset($row['owner_id'])) {
                return (int)$row['owner_id'];
            }
        }
    }

    $ownerIdFromEmail = get_owner_id_by_email($conn, $ownerEmail);
    if ($ownerIdFromEmail > 0) {
        return $ownerIdFromEmail;
    }

    $userSeed = find_user_for_owner_seed($conn, $ownerId, $ownerEmail);
    return create_owner_from_user_seed($conn, $userSeed);
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

$owner_id = intval($data['owner_id'] ?? 0);
$owner_email = strtolower(trim((string)($data['owner_email'] ?? '')));
$turf_name = trim((string)($data['turf_name'] ?? ''));
$sport_type = strtolower(trim((string)($data['sport_type'] ?? 'football')));
$address = trim((string)($data['address'] ?? ''));
$area = trim((string)($data['area'] ?? ''));
$city = trim((string)($data['city'] ?? ''));
$location = trim((string)($data['location'] ?? ''));
$latitude = trim((string)($data['latitude'] ?? ''));
$longitude = trim((string)($data['longitude'] ?? ''));
$price_per_hour = $data['price_per_hour'] ?? null;
$description = trim((string)($data['description'] ?? ''));
$is_featured = intval($data['is_featured'] ?? 0) === 1 ? 1 : 0;
$status = 'pending';
$cancel_before_hours = intval($data['cancel_before_hours'] ?? 24);
$refund_percent = intval($data['refund_percent'] ?? 80);

if ($turf_name === '') {
    send_response(false, 'Turf name is required.', 400);
}

if ($address === '') {
    send_response(false, 'Address is required.', 400);
}

if (!is_numeric($price_per_hour) || floatval($price_per_hour) <= 0) {
    send_response(false, 'Price per hour must be a positive number.', 400);
}

$price_per_hour = floatval($price_per_hour);

$allowedSports = ['football', 'cricket', 'badminton', 'basketball', 'tennis', 'other'];
if (!in_array($sport_type, $allowedSports, true)) {
    $sport_type = 'other';
}

// Owner turf always goes to admin verification queue first.
$status = 'pending';

if ($cancel_before_hours < 0) {
    $cancel_before_hours = 24;
}

if ($refund_percent < 0 || $refund_percent > 100) {
    $refund_percent = 80;
}

if ($latitude !== '' && !is_numeric($latitude)) {
    send_response(false, 'Latitude must be numeric.', 400);
}

if ($longitude !== '' && !is_numeric($longitude)) {
    send_response(false, 'Longitude must be numeric.', 400);
}

if ($latitude !== '') {
    $latValue = floatval($latitude);
    if ($latValue < -90 || $latValue > 90) {
        send_response(false, 'Latitude must be between -90 and 90.', 400);
    }
}

if ($longitude !== '') {
    $lngValue = floatval($longitude);
    if ($lngValue < -180 || $lngValue > 180) {
        send_response(false, 'Longitude must be between -180 and 180.', 400);
    }
}

$resolved_owner_id = find_owner_id($conn, $owner_id, $owner_email);
if ($resolved_owner_id <= 0) {
    send_response(false, 'Owner account not found in turf_owners table.', 400);
}

if (!isset($_FILES['turf_image'])) {
    send_response(false, 'Turf image is required.', 400);
}

$sql = "INSERT INTO turfs (
    owner_id,
    turf_name,
    sport_type,
    address,
    area,
    city,
    location,
    latitude,
    longitude,
    price_per_hour,
    description,
    is_featured,
    status,
    cancel_before_hours,
    refund_percent
) VALUES (
    ?, ?, ?, ?, NULLIF(?, ''), NULLIF(?, ''), NULLIF(?, ''), NULLIF(?, ''), NULLIF(?, ''), ?, NULLIF(?, ''), ?, ?, ?, ?
)";

$stmt = $conn->prepare($sql);
if (!$stmt) {
    send_response(false, 'Server error while preparing turf insert.', 500);
}

$stmt->bind_param(
    'issssssssdsisii',
    $resolved_owner_id,
    $turf_name,
    $sport_type,
    $address,
    $area,
    $city,
    $location,
    $latitude,
    $longitude,
    $price_per_hour,
    $description,
    $is_featured,
    $status,
    $cancel_before_hours,
    $refund_percent
);

$conn->begin_transaction();

if (!$stmt->execute()) {
    $errorText = $stmt->error;
    $stmt->close();
    $conn->rollback();
    send_response(false, 'Failed to add turf. ' . $errorText, 500);
}

$turfId = (int)$stmt->insert_id;
$stmt->close();

$imageResult = save_turf_image_file($_FILES['turf_image'], $turfId);
if (!$imageResult['ok']) {
    $conn->rollback();
    send_response(false, $imageResult['error'], 400);
}

$imageUrl = (string)$imageResult['relative_url'];
$insertImageSql = "INSERT INTO turf_images (turf_id, image_url, is_primary) VALUES (?, ?, 1)";
$imageStmt = $conn->prepare($insertImageSql);

if (!$imageStmt) {
    if (file_exists($imageResult['absolute_path'])) {
        @unlink($imageResult['absolute_path']);
    }
    $conn->rollback();
    send_response(false, 'Server error while saving turf image metadata.', 500);
}

$imageStmt->bind_param('is', $turfId, $imageUrl);
if (!$imageStmt->execute()) {
    $imgError = $imageStmt->error;
    $imageStmt->close();
    if (file_exists($imageResult['absolute_path'])) {
        @unlink($imageResult['absolute_path']);
    }
    $conn->rollback();
    send_response(false, 'Failed to save turf image. ' . $imgError, 500);
}

$imageStmt->close();
$conn->commit();

send_response(true, 'Turf submitted for admin verification.', 200, [
    'turf_id' => $turfId,
    'image_url' => $imageUrl
]);
?>



