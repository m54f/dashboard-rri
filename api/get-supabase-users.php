<?php
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/supabase-tickets.php';

$users = getSupabaseUsersList();

echo json_encode(['success' => true, 'data' => $users]);
