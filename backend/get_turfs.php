<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit();
}

require_once "db_connection.php";

function send_response($success, $message, $code = 200, $extra = []) {
    http_response_code($code);
    echo json_encode(array_merge([
        "success" => $success,
        "status" => $message
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
    if ($safeTable === '') {
        return false;
    }

    $safeColumn = $conn->real_escape_string($column);
    $res = $conn->query("SHOW COLUMNS FROM `$safeTable` LIKE '$safeColumn'");
    return $res && $res->num_rows > 0;
}

$locationSelect = column_exists($conn, 'turfs', 'location')
    ? "t.location"
    : "'' AS location";

$descriptionSelect = column_exists($conn, 'turfs', 'description')
    ? "t.description"
    : "'' AS description";

$featuredSelect = column_exists($conn, 'turfs', 'is_featured')
    ? "t.is_featured"
    : "0 AS is_featured";

$cancelBeforeHoursSelect = column_exists($conn, 'turfs', 'cancel_before_hours')
    ? "t.cancel_before_hours"
    : "0 AS cancel_before_hours";

$refundPercentSelect = column_exists($conn, 'turfs', 'refund_percent')
    ? "t.refund_percent"
    : "0 AS refund_percent";

$latitudeSelect = column_exists($conn, 'turfs', 'latitude')
    ? "t.latitude"
    : "'' AS latitude";

$longitudeSelect = column_exists($conn, 'turfs', 'longitude')
    ? "t.longitude"
    : "'' AS longitude";

$imageSelect = table_exists($conn, 'turf_images')
    ? "(
        SELECT ti.image_url
        FROM turf_images ti
        WHERE ti.turf_id = t.turf_id
        ORDER BY ti.is_primary DESC, ti.image_id DESC
        LIMIT 1
      ) AS image_url"
    : "'' AS image_url";

$sql = "
SELECT
    t.turf_id,
    t.turf_name,
    t.sport_type,
    t.address,
    t.area,
    t.city,
    $locationSelect,
    $latitudeSelect,
    $longitudeSelect,
    t.price_per_hour,
    $descriptionSelect,
    $featuredSelect,
    $cancelBeforeHoursSelect,
    $refundPercentSelect,
    t.status,
    t.created_at,
    $imageSelect
FROM turfs t
WHERE t.status = 'active'
ORDER BY t.created_at DESC, t.turf_id DESC
";

$result = $conn->query($sql);
if (!$result) {
    send_response(false, "Failed to load turfs.", 500);
}

$turfs = [];
while ($row = $result->fetch_assoc()) {
    $createdAt = (string)($row['created_at'] ?? '');
    $createdTs = $createdAt !== '' ? strtotime($createdAt) : 0;
    $isNew = $createdTs > 0 ? ((time() - $createdTs) <= (7 * 24 * 3600)) : false;

    $turfs[] = [
        "turf_id" => (int)$row["turf_id"],
        "turf_name" => $row["turf_name"],
        "sport_type" => $row["sport_type"],
        "address" => $row["address"],
        "area" => $row["area"],
        "city" => $row["city"],
        "location" => $row["location"] ?? "",
        "latitude" => (string)($row["latitude"] ?? ""),
        "longitude" => (string)($row["longitude"] ?? ""),
        "price_per_hour" => (float)$row["price_per_hour"],
        "description" => $row["description"] ?? "",
        "is_featured" => (int)($row["is_featured"] ?? 0) === 1,
        "cancel_before_hours" => (int)($row["cancel_before_hours"] ?? 0),
        "refund_percent" => (int)($row["refund_percent"] ?? 0),
        "status" => $row["status"],
        "created_at" => $createdAt,
        "is_new" => $isNew,
        "image_url" => $row["image_url"] ?? ""
    ];
}

send_response(true, "Turfs loaded.", 200, [
    "turfs" => $turfs
]);
?>
