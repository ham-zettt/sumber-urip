<?php
/**
 * Sumber Urip - Core Helper Functions
 */

require_once __DIR__ . '/../config/config.php';

/**
 * Escape HTML output to prevent XSS
 */
function e(?string $value): string {
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

/**
 * Format integer amount to Indonesian Rupiah representation
 * e.g., Rp 12.500 or -Rp 5.000
 */
function formatRupiah(int|float $amount, bool $withPrefix = true): string {
    $isNegative = $amount < 0;
    $absVal = abs((int)round($amount));
    $formatted = number_format($absVal, 0, ',', '.');
    
    if ($withPrefix) {
        return ($isNegative ? '-Rp ' : 'Rp ') . $formatted;
    }
    return ($isNegative ? '-' : '') . $formatted;
}

/**
 * Format money with muted 'Rp' per DESIGN.md Section 4:
 * "Render Rp in text-ink-muted at ~70% of the number size, the number in text-ink font-semibold."
 */
function formatMoneyHtml(int|float $amount): string {
    $isNegative = $amount < 0;
    $absVal = abs((int)round($amount));
    $numStr = number_format($absVal, 0, ',', '.');
    $minus = $isNegative ? '-' : '';
    return '<span class="inline-flex items-baseline tabular-nums"><span class="text-[0.7em] text-ink-muted font-normal mr-1">' . $minus . 'Rp</span><span class="font-semibold">' . $numStr . '</span></span>';
}

/**
 * Render reusable Stat Card (DESIGN.md Section 7.5)
 */
function renderStatCard(string $label, int|float $amount, ?string $subtext = null, bool $isNetProfit = false): string {
    $isNegative = $amount < 0;
    $formattedVal = formatRupiah($amount);
    
    if ($isNetProfit) {
        if ($isNegative) {
            $classes = "card p-4 border-danger-600/30 bg-danger-50 col-span-2 lg:col-span-1";
            $textClass = "text-danger-700 font-bold";
        } else {
            $classes = "card p-4 border-brand-200 bg-brand-50 col-span-2 lg:col-span-1";
            $textClass = "text-brand-700 font-bold";
        }
        $valClass = "text-display tabular-nums " . $textClass;
    } else {
        $classes = "card p-4";
        $valClass = "text-title tabular-nums text-ink font-semibold";
    }

    $html = '<div class="' . $classes . '">';
    $html .= '<p class="text-micro uppercase text-ink-muted">' . e($label) . '</p>';
    $html .= '<p class="mt-2 ' . $valClass . '">' . $formattedVal . '</p>';
    if ($subtext) {
        $html .= '<p class="mt-1 text-small text-ink-muted">' . e($subtext) . '</p>';
    }
    $html .= '</div>';
    return $html;
}

/**
 * Render Badge per DESIGN.md Section 7.7
 */
function renderBadge(string $type, string $label): string {
    switch ($type) {
        case 'success':
            return '<span class="inline-flex items-center rounded-full bg-success-50 px-2.5 py-0.5 text-small font-medium text-success-700">' . e($label) . '</span>';
        case 'danger':
            return '<span class="inline-flex items-center rounded-full bg-danger-50 px-2.5 py-0.5 text-small font-medium text-danger-700">' . e($label) . '</span>';
        case 'warning':
            return '<span class="inline-flex items-center rounded-full bg-warning-50 px-2.5 py-0.5 text-small font-medium text-warning-700">' . e($label) . '</span>';
        case 'brand':
            return '<span class="inline-flex items-center rounded-full bg-brand-50 px-2.5 py-0.5 text-small font-medium text-brand-700">' . e($label) . '</span>';
        default:
            return '<span class="inline-flex items-center rounded-full bg-surface px-2.5 py-0.5 text-small font-medium text-ink-soft">' . e($label) . '</span>';
    }
}

/**
 * Format date string into Indonesian format: "27 Okt 2026"
 * and optionally include 24h time "HH:mm"
 */
function formatDate(?string $dateStr, bool $withTime = false): string {
    if (empty($dateStr)) {
        return '-';
    }
    
    try {
        $dt = new DateTime($dateStr, new DateTimeZone('Asia/Jakarta'));
    } catch (Exception $e) {
        return '-';
    }

    $months = [
        1 => 'Jan', 2 => 'Feb', 3 => 'Mar', 4 => 'Apr',
        5 => 'Mei', 6 => 'Jun', 7 => 'Jul', 8 => 'Agu',
        9 => 'Sep', 10 => 'Okt', 11 => 'Nov', 12 => 'Des'
    ];

    $day = $dt->format('d');
    $monthNum = (int)$dt->format('n');
    $monthName = $months[$monthNum] ?? $dt->format('M');
    $year = $dt->format('Y');

    if ($withTime) {
        $time = $dt->format('H:i');
        return "{$day} {$monthName} {$year}, {$time}";
    }

    return "{$day} {$monthName} {$year}";
}

/**
 * Get or generate CSRF token for the session
 */
function getCsrfToken(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Render a hidden CSRF token input field
 */
function csrfField(): string {
    $token = getCsrfToken();
    return '<input type="hidden" name="csrf_token" value="' . e($token) . '">';
}

/**
 * Verify submitted CSRF token
 */
function verifyCsrfToken(?string $token = null): bool {
    $sessionToken = $_SESSION['csrf_token'] ?? '';
    if (empty($sessionToken)) {
        return false;
    }

    $submittedToken = $token ?? $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    return hash_equals($sessionToken, $submittedToken);
}

/**
 * Set a flash message for the next request
 * Types: success, error, warning, info
 */
function setFlashMessage(string $type, string $message): void {
    $_SESSION['flash_message'] = [
        'type' => $type,
        'message' => $message
    ];
}

/**
 * Retrieve and clear flash message
 */
function getFlashMessage(): ?array {
    if (isset($_SESSION['flash_message'])) {
        $flash = $_SESSION['flash_message'];
        unset($_SESSION['flash_message']);
        return $flash;
    }
    return null;
}

/**
 * Redirect user to a given URL
 */
function redirect(string $url): void {
    header('Location: ' . $url);
    exit;
}

/**
 * Return JSON response and terminate script
 */
function jsonResponse(array $data, int $statusCode = 200): void {
    http_response_code($statusCode);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

/**
 * Calculate range for Today:
 * [start: 'Y-m-d 00:00:00', end_exclusive: 'Y-m-d 00:00:00 (+1 day)']
 */
function getTodayRange(): array {
    $now = new DateTime('now', new DateTimeZone('Asia/Jakarta'));
    $startDate = $now->format('Y-m-d 00:00:00');
    
    $tomorrow = clone $now;
    $tomorrow->modify('+1 day');
    $endDateExclusive = $tomorrow->format('Y-m-d 00:00:00');

    return [
        'start' => $startDate,
        'end_exclusive' => $endDateExclusive,
        'date_display' => formatDate($now->format('Y-m-d'))
    ];
}

/**
 * Calculate range for a single Day (from Y-m-d):
 */
function getDailyRange(string $dateStr): array {
    try {
        $dt = new DateTime($dateStr, new DateTimeZone('Asia/Jakarta'));
    } catch (Exception $e) {
        $dt = new DateTime('now', new DateTimeZone('Asia/Jakarta'));
    }

    $startDate = $dt->format('Y-m-d 00:00:00');
    $nextDay = clone $dt;
    $nextDay->modify('+1 day');
    $endDateExclusive = $nextDay->format('Y-m-d 00:00:00');

    return [
        'start' => $startDate,
        'end_exclusive' => $endDateExclusive,
        'date_str' => $dt->format('Y-m-d'),
        'date_display' => formatDate($dt->format('Y-m-d'))
    ];
}

/**
 * Calculate range for Weekly mode (Monday to Sunday):
 * Input: any date string within the week
 */
function getWeekRange(string $dateStr): array {
    try {
        $dt = new DateTime($dateStr, new DateTimeZone('Asia/Jakarta'));
    } catch (Exception $e) {
        $dt = new DateTime('now', new DateTimeZone('Asia/Jakarta'));
    }

    // Set to Monday of this week
    $monday = clone $dt;
    $dayOfWeek = (int)$dt->format('N'); // 1 (Mon) to 7 (Sun)
    if ($dayOfWeek > 1) {
        $monday->modify('-' . ($dayOfWeek - 1) . ' days');
    }
    $mondayStr = $monday->format('Y-m-d 00:00:00');

    // Sunday (inclusive)
    $sunday = clone $monday;
    $sunday->modify('+6 days');

    // Next Monday (exclusive)
    $nextMonday = clone $monday;
    $nextMonday->modify('+7 days');
    $nextMondayStr = $nextMonday->format('Y-m-d 00:00:00');

    return [
        'start' => $mondayStr,
        'end_exclusive' => $nextMondayStr,
        'start_date' => $monday->format('Y-m-d'),
        'end_date' => $sunday->format('Y-m-d'),
        'date_display' => formatDate($monday->format('Y-m-d')) . ' - ' . formatDate($sunday->format('Y-m-d')),
        'prev_week_date' => (clone $monday)->modify('-7 days')->format('Y-m-d'),
        'next_week_date' => (clone $monday)->modify('+7 days')->format('Y-m-d')
    ];
}

/**
 * Calculate range for Monthly mode:
 */
function getMonthRange(int $year, int $month): array {
    // Validate range
    if ($year < 2000 || $year > 2100) {
        $year = (int)date('Y');
    }
    if ($month < 1 || $month > 12) {
        $month = (int)date('n');
    }

    $firstDay = sprintf('%04d-%02d-01 00:00:00', $year, $month);
    
    // Calculate first day of next month
    $dt = new DateTime($firstDay, new DateTimeZone('Asia/Jakarta'));
    $nextMonthDt = clone $dt;
    $nextMonthDt->modify('+1 month');
    $nextMonthFirstDay = $nextMonthDt->format('Y-m-d 00:00:00');

    // Last day of current month
    $lastDayDt = clone $nextMonthDt;
    $lastDayDt->modify('-1 day');

    // Prev / Next month calculations
    $prevMonthDt = clone $dt;
    $prevMonthDt->modify('-1 month');

    $monthNames = [
        1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
        5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
        9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
    ];

    return [
        'start' => $firstDay,
        'end_exclusive' => $nextMonthFirstDay,
        'year' => $year,
        'month' => $month,
        'month_name' => $monthNames[$month] ?? '',
        'date_display' => ($monthNames[$month] ?? '') . ' ' . $year,
        'prev_year' => (int)$prevMonthDt->format('Y'),
        'prev_month' => (int)$prevMonthDt->format('n'),
        'next_year' => (int)$nextMonthDt->format('Y'),
        'next_month' => (int)$nextMonthDt->format('n')
    ];
}
