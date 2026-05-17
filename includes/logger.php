<?php

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth.php';

/**
 * Insert a record into activity_logs.
 *
 * @param string $action      CREATE | READ | UPDATE | DELETE
 * @param string $entityType  Department | Employee
 * @param int    $entityId    Primary key of the affected row
 * @param string $entityName  Human-readable name (e.g. "CSIT - Computer Science & IT")
 * @param string $details     Full description of what happened (e.g. old vs new values)
 */
function logActivity(
    string $action,
    string $entityType,
    int    $entityId,
    string $entityName,
    string $details
): void {
    $db       = getDB();
    $userId   = currentUserId();
    $username = currentUsername();

    if (!$userId) return; // should never happen if requireLogin() is used

    $stmt = $db->prepare("
        INSERT INTO activity_logs (user_id, username, action, entity_type, entity_id, entity_name, details)
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute([$userId, $username, $action, $entityType, $entityId, $entityName, $details]);
}

/**
 * Fetch all activity logs (newest first) for the logs page.
 * Optional filters: action type, entity type, username keyword, date range.
 */
function getActivityLogs(array $filters = []): array {
    $db     = getDB();
    $where  = [];
    $params = [];

    if (!empty($filters['action'])) {
        $where[]  = "action = ?";
        $params[] = $filters['action'];
    }
    if (!empty($filters['entity_type'])) {
        $where[]  = "entity_type = ?";
        $params[] = $filters['entity_type'];
    }
    if (!empty($filters['username'])) {
        $where[]  = "username LIKE ?";
        $params[] = '%' . $filters['username'] . '%';
    }
    if (!empty($filters['date_from'])) {
        $where[]  = "DATE(performed_at) >= ?";
        $params[] = $filters['date_from'];
    }
    if (!empty($filters['date_to'])) {
        $where[]  = "DATE(performed_at) <= ?";
        $params[] = $filters['date_to'];
    }

    $sql = "SELECT * FROM activity_logs";
    if ($where) {
        $sql .= " WHERE " . implode(" AND ", $where);
    }
    $sql .= " ORDER BY performed_at DESC";

    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}
