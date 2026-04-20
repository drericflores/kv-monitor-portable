<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=UTF-8');

$config = require __DIR__ . '/config.php';
date_default_timezone_set($config['timezone'] ?? 'UTC');

function getHostNameSafe(): string
{
    return gethostname() ?: php_uname('n') ?: getenv('HOSTNAME') ?: 'unknown';
}

function formatUptime(int $seconds): string
{
    $days = intdiv($seconds, 86400);
    $seconds %= 86400;
    $hours = intdiv($seconds, 3600);
    $seconds %= 3600;
    $minutes = intdiv($seconds, 60);

    $parts = [];
    if ($days > 0) {
        $parts[] = "{$days}d";
    }
    if ($hours > 0) {
        $parts[] = "{$hours}h";
    }
    $parts[] = "{$minutes}m";

    return implode(' ', $parts);
}

function getUptime(): string
{
    if (is_readable('/proc/uptime')) {
        $raw = @file_get_contents('/proc/uptime');
        if ($raw !== false && $raw !== '') {
            $seconds = (int) explode(' ', trim($raw))[0];
            return formatUptime($seconds);
        }
    }

    $out = @shell_exec('uptime -p 2>/dev/null');
    return $out ? trim($out) : 'Unavailable';
}

function getCpuLoad(): float
{
    $load = sys_getloadavg();
    return round((float)($load[0] ?? 0), 2);
}

function getMemoryPercent(): float
{
    if (!is_readable('/proc/meminfo')) {
        return 0.0;
    }

    $lines = file('/proc/meminfo', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if ($lines === false) {
        return 0.0;
    }

    $total = 0;
    $available = 0;

    foreach ($lines as $line) {
        if (strpos($line, 'MemTotal:') === 0) {
            $total = (int) filter_var($line, FILTER_SANITIZE_NUMBER_INT);
        }
        if (strpos($line, 'MemAvailable:') === 0) {
            $available = (int) filter_var($line, FILTER_SANITIZE_NUMBER_INT);
        }
    }

    if ($total <= 0) {
        return 0.0;
    }

    return round((($total - $available) / $total) * 100, 1);
}

function getDiskPercent(): float
{
    $total = @disk_total_space('/');
    $free = @disk_free_space('/');

    if (!$total || !$free) {
        return 0.0;
    }

    return round((($total - $free) / $total) * 100, 1);
}

function checkTcp(string $host, int $port, float &$latencyMs = 0.0): bool
{
    $start = microtime(true);
    $conn = @fsockopen($host, $port, $errno, $errstr, 1.0);
    $latencyMs = round((microtime(true) - $start) * 1000, 1);

    if ($conn) {
        fclose($conn);
        return true;
    }

    return false;
}

$groupedServices = [];
$totalUp = 0;
$totalDown = 0;

foreach (($config['groups'] ?? []) as $groupName => $services) {
    $groupedServices[$groupName] = [];

    foreach ($services as $svc) {
        $latency = 0.0;
        $up = false;

        if (($svc['type'] ?? 'tcp') === 'tcp') {
            $up = checkTcp((string)$svc['host'], (int)$svc['port'], $latency);
        }

        if ($up) {
            $totalUp++;
        } else {
            $totalDown++;
        }

        $groupedServices[$groupName][] = [
            'name' => $svc['name'],
            'status' => $up ? 'UP' : 'DOWN',
            'icon' => $svc['icon'] ?? '⚙️',
            'description' => $svc['description'] ?? '',
            'latency_ms' => $latency,
            'url' => $svc['url'] ?? null,
            'host' => $svc['host'] ?? '',
            'port' => $svc['port'] ?? 0,
        ];
    }
}

echo json_encode([
    'app_name' => $config['app_name'] ?? 'KV Monitor Enterprise',
    'generated_at' => date('Y-m-d H:i:s'),
    'hostname' => getHostNameSafe(),
    'uptime' => getUptime(),
    'cpu' => getCpuLoad(),
    'memory' => getMemoryPercent(),
    'disk' => getDiskPercent(),
    'summary' => [
        'up' => $totalUp,
        'down' => $totalDown,
        'total' => $totalUp + $totalDown,
    ],
    'groups' => $groupedServices
], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
