<?php

function promo_time_to_minutes($timeText) {
    $value = (string)$timeText;
    $parts = explode(':', $value);
    if (count($parts) < 2) {
        return 0;
    }

    $hours = intval($parts[0] ?? 0);
    $minutes = intval($parts[1] ?? 0);
    return ($hours * 60) + $minutes;
}

function promo_calculate_slot_subtotal($basePrice, $startTime, $endTime) {
    $startMinutes = promo_time_to_minutes($startTime);
    $endMinutes = promo_time_to_minutes($endTime);

    if ($endMinutes > $startMinutes && $basePrice > 0) {
        $durationHours = ($endMinutes - $startMinutes) / 60;
        return round($durationHours * floatval($basePrice), 2);
    }

    return round(floatval($basePrice), 2);
}

function promo_validate_for_owner_turf($conn, $ownerId, $promoCode, $subtotal, $slotDate) {
    $code = strtoupper(trim((string)$promoCode));
    if ($code === '') {
        return [
            'success' => true,
            'promo_code' => '',
            'discount_amount' => 0,
            'final_total' => round(floatval($subtotal), 2),
            'message' => 'No promo code applied.'
        ];
    }

    $existsStmt = $conn->prepare('SELECT promo_id, owner_id, code, discount_type, discount_value, min_booking_amount, start_date, end_date, is_active FROM promo_codes WHERE code = ? LIMIT 1');
    if (!$existsStmt) {
        return [
            'success' => false,
            'message' => 'Server error while checking promo code.'
        ];
    }

    $existsStmt->bind_param('s', $code);
    $existsStmt->execute();
    $res = $existsStmt->get_result();
    $promo = $res ? $res->fetch_assoc() : null;
    $existsStmt->close();

    if (!$promo) {
        return [
            'success' => false,
            'message' => 'Promo code not found.'
        ];
    }

    if (intval($promo['owner_id'] ?? 0) !== intval($ownerId)) {
        return [
            'success' => false,
            'message' => 'This promo code is not valid for this turf.'
        ];
    }

    if (intval($promo['is_active'] ?? 0) !== 1) {
        return [
            'success' => false,
            'message' => 'This promo code is inactive.'
        ];
    }

    $startDate = trim((string)($promo['start_date'] ?? ''));
    $endDate = trim((string)($promo['end_date'] ?? ''));
    if ($startDate !== '' && $slotDate < $startDate) {
        return [
            'success' => false,
            'message' => 'This promo code is not active yet.'
        ];
    }
    if ($endDate !== '' && $slotDate > $endDate) {
        return [
            'success' => false,
            'message' => 'This promo code has expired.'
        ];
    }

    $minBookingAmount = floatval($promo['min_booking_amount'] ?? 0);
    if ($minBookingAmount > 0 && floatval($subtotal) < $minBookingAmount) {
        return [
            'success' => false,
            'message' => 'Booking amount is too low for this promo code.'
        ];
    }

    $discountType = (string)($promo['discount_type'] ?? 'fixed');
    $discountValue = floatval($promo['discount_value'] ?? 0);
    $discountAmount = 0;

    if ($discountType === 'percent') {
        $discountAmount = round(floatval($subtotal) * ($discountValue / 100), 2);
    } else {
        $discountAmount = round($discountValue, 2);
    }

    if ($discountAmount < 0) {
        $discountAmount = 0;
    }

    if ($discountAmount > floatval($subtotal)) {
        $discountAmount = round(floatval($subtotal), 2);
    }

    $finalTotal = round(max(0, floatval($subtotal) - $discountAmount), 2);

    return [
        'success' => true,
        'promo_id' => intval($promo['promo_id'] ?? 0),
        'promo_code' => $code,
        'discount_type' => $discountType,
        'discount_value' => $discountValue,
        'discount_amount' => $discountAmount,
        'final_total' => $finalTotal,
        'message' => 'Promo code applied successfully.'
    ];
}

