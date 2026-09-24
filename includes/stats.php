<?php
declare(strict_types=1);

function tracker_count_where(PDO $pdo, string $where, array $params = []): int
{
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM visits WHERE $where");
    $stmt->execute($params);
    return (int)$stmt->fetchColumn();
}

function tracker_count_distinct_where(PDO $pdo, string $column, string $where, array $params = []): int
{
    $stmt = $pdo->prepare("SELECT COUNT(DISTINCT $column) FROM visits WHERE $where");
    $stmt->execute($params);
    return (int)$stmt->fetchColumn();
}

function tracker_stat_totals(PDO $pdo): array
{
    $total = (int)$pdo->query("SELECT COUNT(*) FROM visits")->fetchColumn();
    $uniqueVisitors = (int)$pdo->query("SELECT COUNT(DISTINCT visitor_id) FROM visits")->fetchColumn();

    $today = tracker_count_where($pdo, "date(created_at) = :d", [':d' => date('Y-m-d')]);
    $thisMonth = tracker_count_where($pdo, "strftime('%Y-%m', created_at) = :m", [':m' => date('Y-m')]);
    $thisYear = tracker_count_where($pdo, "strftime('%Y', created_at) = :y", [':y' => date('Y')]);
    $uniqueToday = tracker_count_distinct_where($pdo, 'visitor_id', "date(created_at) = :d", [':d' => date('Y-m-d')]);

    return [
        'total' => $total,
        'unique_visitors' => $uniqueVisitors,
        'today' => $today,
        'unique_today' => $uniqueToday,
        'this_month' => $thisMonth,
        'this_year' => $thisYear,
    ];
}

function tracker_series_daily(PDO $pdo, int $days = 30): array
{
    $stmt = $pdo->prepare("
        SELECT date(created_at) AS d, COUNT(*) AS c
        FROM visits
        WHERE date(created_at) >= date('now', :range)
        GROUP BY d
        ORDER BY d ASC
    ");
    $stmt->execute([':range' => '-' . $days . ' days']);
    $rows = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

    $result = [];
    for ($i = $days - 1; $i >= 0; $i--) {
        $day = date('Y-m-d', strtotime("-$i days"));
        $result[$day] = (int)($rows[$day] ?? 0);
    }
    return $result;
}

function tracker_series_monthly(PDO $pdo, int $months = 12): array
{
    $stmt = $pdo->prepare("
        SELECT strftime('%Y-%m', created_at) AS m, COUNT(*) AS c
        FROM visits
        WHERE date(created_at) >= date('now', :range)
        GROUP BY m
        ORDER BY m ASC
    ");
    $stmt->execute([':range' => '-' . $months . ' months']);
    $rows = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

    $result = [];
    for ($i = $months - 1; $i >= 0; $i--) {
        $m = date('Y-m', strtotime("-$i months"));
        $result[$m] = (int)($rows[$m] ?? 0);
    }
    return $result;
}

function tracker_series_yearly(PDO $pdo): array
{
    $stmt = $pdo->query("
        SELECT strftime('%Y', created_at) AS y, COUNT(*) AS c
        FROM visits
        GROUP BY y
        ORDER BY y ASC
    ");
    $rows = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
    return array_map('intval', $rows);
}

function tracker_top(PDO $pdo, string $column, int $limit = 10): array
{
    $allowed = ['path', 'utm_source', 'utm_medium', 'utm_campaign', 'referrer', 'browser', 'os', 'device_type', 'language'];
    if (!in_array($column, $allowed, true)) {
        throw new InvalidArgumentException('Colonne non autorisée');
    }

    $stmt = $pdo->prepare("
        SELECT $column AS label, COUNT(*) AS c
        FROM visits
        WHERE $column IS NOT NULL AND $column != ''
        GROUP BY $column
        ORDER BY c DESC
        LIMIT :limit
    ");
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Connexions uniques par jour et par IP.
 * Si plusieurs requêtes arrivent de la même IP le même jour,
 * seule la première visite enregistrée est conservée dans le résultat.
 */
function tracker_daily_connections(PDO $pdo, int $limit = 100): array
{
    $stmt = $pdo->prepare("
        SELECT v.*
        FROM visits v
        INNER JOIN (
            SELECT
                date(created_at) AS visit_day,
                COALESCE(ip, visitor_id) AS connection_key,
                MIN(id) AS first_id
            FROM visits
            GROUP BY visit_day, connection_key
        ) d ON d.first_id = v.id
        ORDER BY v.created_at DESC, v.id DESC
        LIMIT :limit
    ");
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function tracker_daily_connection_count(PDO $pdo, string $day): int
{
    $stmt = $pdo->prepare("
        SELECT COUNT(*)
        FROM (
            SELECT COALESCE(ip, visitor_id) AS connection_key
            FROM visits
            WHERE date(created_at) = :day
            GROUP BY connection_key
        )
    ");
    $stmt->execute([':day' => $day]);
    return (int)$stmt->fetchColumn();
}

function tracker_recent_visits(PDO $pdo, int $limit = 25): array
{
    return tracker_daily_connections($pdo, $limit);
}
