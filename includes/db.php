<?php
declare(strict_types=1);

/**
 * Retourne une connexion PDO vers la base SQLite (créée si besoin), avec le schéma initialisé.
 */
function tracker_db(): PDO
{
    static $pdo = null;
    if ($pdo !== null) {
        return $pdo;
    }

    $dataDir = __DIR__ . '/../data';
    if (!is_dir($dataDir)) {
        mkdir($dataDir, 0775, true);
    }

    $dbPath = $dataDir . '/tracker.sqlite';
    $pdo = new PDO('sqlite:' . $dbPath);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->exec('PRAGMA journal_mode = WAL;');

    tracker_init_schema($pdo);

    return $pdo;
}

function tracker_init_schema(PDO $pdo): void
{
    $pdo->exec("CREATE TABLE IF NOT EXISTS visits (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        created_at TEXT NOT NULL,
        visitor_id TEXT,
        path TEXT,
        full_url TEXT,
        referrer TEXT,
        utm_source TEXT,
        utm_medium TEXT,
        utm_campaign TEXT,
        utm_term TEXT,
        utm_content TEXT,
        ip TEXT,
        user_agent TEXT,
        browser TEXT,
        os TEXT,
        device_type TEXT,
        language TEXT,
        screen_resolution TEXT
    )");

    $pdo->exec("CREATE INDEX IF NOT EXISTS idx_visits_created_at ON visits(created_at)");
    $pdo->exec("CREATE INDEX IF NOT EXISTS idx_visits_path ON visits(path)");
    $pdo->exec("CREATE INDEX IF NOT EXISTS idx_visits_visitor ON visits(visitor_id)");
    $pdo->exec("CREATE INDEX IF NOT EXISTS idx_visits_utm_source ON visits(utm_source)");
}