<?php

declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

// DOI MAT KHAU NAY TRUOC KHI DUA LEN HOST.
const CMS_PASSWORD = '123456';

function json_response(int $statusCode, array $payload): void
{
    http_response_code($statusCode);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(405, [
        'ok' => false,
        'message' => 'Chi ho tro POST.'
    ]);
}

$raw = file_get_contents('php://input');
if ($raw === false || $raw === '') {
    json_response(400, [
        'ok' => false,
        'message' => 'Khong co du lieu gui len.'
    ]);
}

$payload = json_decode($raw, true);
if (!is_array($payload)) {
    json_response(400, [
        'ok' => false,
        'message' => 'JSON khong hop le.'
    ]);
}

$password = (string)($payload['password'] ?? '');
$html = (string)($payload['html'] ?? '');

if ($password === '' || !hash_equals(CMS_PASSWORD, $password)) {
    json_response(401, [
        'ok' => false,
        'message' => 'Sai mat khau luu host.'
    ]);
}

if ($html === '' || strlen($html) < 1000) {
    json_response(400, [
        'ok' => false,
        'message' => 'Noi dung HTML qua ngan hoac rong.'
    ]);
}

if (stripos($html, '<html') === false || stripos($html, '</html>') === false) {
    json_response(400, [
        'ok' => false,
        'message' => 'Noi dung khong phai HTML hop le.'
    ]);
}

if (strlen($html) > 8 * 1024 * 1024) {
    json_response(413, [
        'ok' => false,
        'message' => 'Noi dung qua lon (>8MB).'
    ]);
}

$baseDir = __DIR__;
$indexPath = $baseDir . DIRECTORY_SEPARATOR . 'index.html';
$backupDir = $baseDir . DIRECTORY_SEPARATOR . 'backups';

if (!file_exists($indexPath)) {
    json_response(500, [
        'ok' => false,
        'message' => 'Khong tim thay index.html tren host.'
    ]);
}

if (!is_dir($backupDir)) {
    if (!mkdir($backupDir, 0775, true) && !is_dir($backupDir)) {
        json_response(500, [
            'ok' => false,
            'message' => 'Khong tao duoc thu muc backups.'
        ]);
    }
}

$backupFile = 'index-' . date('Ymd-His') . '.html';
$backupPath = $backupDir . DIRECTORY_SEPARATOR . $backupFile;

$currentContent = file_get_contents($indexPath);
if ($currentContent === false) {
    json_response(500, [
        'ok' => false,
        'message' => 'Khong doc duoc index.html hien tai.'
    ]);
}

if (file_put_contents($backupPath, $currentContent, LOCK_EX) === false) {
    json_response(500, [
        'ok' => false,
        'message' => 'Khong tao duoc file backup.'
    ]);
}

$tmpPath = $indexPath . '.tmp';
if (file_put_contents($tmpPath, $html, LOCK_EX) === false) {
    json_response(500, [
        'ok' => false,
        'message' => 'Khong ghi duoc file tam.'
    ]);
}

if (!rename($tmpPath, $indexPath)) {
    @unlink($tmpPath);
    json_response(500, [
        'ok' => false,
        'message' => 'Khong doi duoc file tam sang index.html.'
    ]);
}

json_response(200, [
    'ok' => true,
    'message' => 'Da cap nhat index.html thanh cong.',
    'backup_file' => 'backups/' . $backupFile
]);
