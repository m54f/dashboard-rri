<?php
// File: api/inventaris.php

header('Content-Type: application/json; charset=utf-8');

 $configPath = __DIR__ . '/../config/notion.php';
if (!file_exists($configPath)) {
    http_response_code(500);
    echo json_encode(['error' => 'File konfigurasi Notion tidak ditemukan.']);
    exit;
}
require_once $configPath;

if (!defined('NOTION_TOKEN') || !defined('DATABASE_ID_INVENTARIS')) {
    http_response_code(500);
    echo json_encode(['error' => 'NOTION_TOKEN atau DATABASE_ID_INVENTARIS belum di-set.']);
    exit;
}

function notionCurl($method, $url, $body = []) {
    $ch = curl_init($url);
    $opts = [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER     => [
            'Authorization: Bearer ' . NOTION_TOKEN,
            'Notion-Version: 2022-06-28',
            'Content-Type: application/json',
        ],
    ];
    if ($method === 'POST') {
        $opts[CURLOPT_POST] = true;
        $opts[CURLOPT_POSTFIELDS] = json_encode($body, JSON_UNESCAPED_UNICODE);
    } elseif ($method === 'PATCH') {
        $opts[CURLOPT_CUSTOMREQUEST] = 'PATCH';
        $opts[CURLOPT_POSTFIELDS] = json_encode($body, JSON_UNESCAPED_UNICODE);
    }
    curl_setopt_array($ch, $opts);
    $res = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return ['code' => $code, 'data' => json_decode($res, true)];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    $namaAlat = trim($_POST['nama_alat'] ?? '');
    $kode     = trim($_POST['kode'] ?? '');
    $kategori = trim($_POST['kategori'] ?? '');
    $qty      = (int)($_POST['qty'] ?? 0);
    $lokasi   = trim($_POST['lokasi'] ?? '');
    $status   = trim($_POST['status'] ?? '');
    $type     = trim($_POST['type'] ?? '');
    $catatan  = trim($_POST['catatan'] ?? '');

    if (empty($namaAlat) && $action !== 'delete') {
        http_response_code(400);
        echo json_encode(['error' => 'Nama Alat wajib diisi.']);
        exit;
    }

    // Notion API menerima array kosong [] jika text dikosongkan
    $textKode = !empty($kode) ? [['text' => ['content' => $kode]]] : [];
    $textType = !empty($type) ? [['text' => ['content' => $type]]] : [];
    $textCatatan = !empty($catatan) ? [['text' => ['content' => $catatan]]] : [];

    $properties = [
        'Nama Alat'    => ['title' => [['text' => ['content' => $namaAlat]]]],
        'Kode'         => ['rich_text' => $textKode],
        'Kategori'     => ['select' => ['name' => $kategori]],
        'Qty'          => ['number' => $qty],
        'Lokasi'       => ['select' => ['name' => $lokasi]],
        'Status'       => ['select' => ['name' => $status]],
        'Type'         => ['rich_text' => $textType],
        'Keterangan'   => ['rich_text' => $textCatatan]
    ];

    if ($action === 'create') {
        $body = [
            'parent' => ['database_id' => DATABASE_ID_INVENTARIS],
            'properties' => $properties
        ];
        $res = notionCurl('POST', 'https://api.notion.com/v1/pages', $body);
        
        if ($res['code'] === 200) {
            echo json_encode(['success' => true, 'id' => $res['data']['id']]);
        } else {
            http_response_code(400);
            echo json_encode(['error' => 'Gagal menambah data ke Notion', 'detail' => $res['data']]);
        }
        exit;
    }
    elseif ($action === 'update') {
        $pageId = $_POST['page_id'] ?? '';
        if (empty($pageId)) {
            http_response_code(400);
            echo json_encode(['error' => 'Page ID hilang untuk update.']);
            exit;
        }

        $body = ['properties' => $properties];
        $res = notionCurl('PATCH', 'https://api.notion.com/v1/pages/' . $pageId, $body);
        
        if ($res['code'] === 200) {
            echo json_encode(['success' => true, 'id' => $pageId]);
        } else {
            http_response_code(400);
            echo json_encode(['error' => 'Gagal update data ke Notion', 'detail' => $res['data']]);
        }
        exit;
    }
    elseif ($action === 'delete') {
        $pageId = $_POST['page_id'] ?? '';
        if (empty($pageId)) {
            http_response_code(400);
            echo json_encode(['error' => 'Page ID hilang untuk delete.']);
            exit;
        }

        $body = ['archived' => true];
        $res = notionCurl('PATCH', 'https://api.notion.com/v1/pages/' . $pageId, $body);
        
        if ($res['code'] === 200) {
            echo json_encode(['success' => true, 'id' => $pageId]);
        } else {
            http_response_code(400);
            echo json_encode(['error' => 'Gagal menghapus data di Notion', 'detail' => $res['data']]);
        }
        exit;
    }
    
    http_response_code(400);
    echo json_encode(['error' => 'Action tidak valid.']);
    exit;
}

http_response_code(405);
echo json_encode(['error' => 'Method Not Allowed. Gunakan POST.']);