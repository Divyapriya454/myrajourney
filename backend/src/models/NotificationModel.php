<?php
declare(strict_types=1);

namespace Src\Models;

use PDO;
use Src\Config\DB;

class NotificationModel
{
    private PDO $db;

    public function __construct()
    {
        $this->db = DB::conn();
    }

    /**
     * Create notification
     */
    public function create(int $userId, string $type, string $title, ?string $body = null): int
    {
        $sql = 'INSERT INTO notifications (user_id, type, title, body, message, created_at)
                VALUES (:uid, :type, :title, :body, :message, NOW())';

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':uid'     => $userId,
            ':type'    => $type,
            ':title'   => $title,
            ':body'    => $body,
            ':message' => $body  // Use same content for both fields
        ]);

        return (int)$this->db->lastInsertId();
    }

    /**
     * List notifications with pagination + unread filter
     */
    public function list(int $userId, int $page, int $limit, ?bool $unread): array
    {
        $where = 'WHERE user_id = :uid';

        // unread = true → read_at IS NULL
        if ($unread !== null) {
            $where .= $unread ? ' AND read_at IS NULL' : ' AND read_at IS NOT NULL';
        }

        $offset = ($page - 1) * $limit;

        /** COUNT QUERY */
        $stmtCount = $this->db->prepare("SELECT COUNT(*) FROM notifications $where");
        $stmtCount->bindValue(':uid', $userId, PDO::PARAM_INT);
        $stmtCount->execute();
        $total = (int)$stmtCount->fetchColumn();

        /** MAIN QUERY */
        $sql = "SELECT
                    id,
                    title,
                    COALESCE(body, message) AS message,
                    read_at,
                    created_at
                FROM notifications
                $where
                ORDER BY created_at DESC
                LIMIT :lim OFFSET :off";

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':uid', $userId, PDO::PARAM_INT);
        $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':off', $offset, PDO::PARAM_INT);
        $stmt->execute();

        return [
            'items' => $stmt->fetchAll(PDO::FETCH_ASSOC),
            'total' => $total
        ];
    }

    /**
     * Mark a notification as read
     */
    public function markRead(int $id, int $userId): void
    {
        $sql = 'UPDATE notifications
                SET read_at = NOW()
                WHERE id = :id AND user_id = :uid';

        $this->db->prepare($sql)->execute([
            ':id'  => $id,
            ':uid' => $userId
        ]);
    }

    public function markAllRead(int $userId): void
    {
        $sql = 'UPDATE notifications
                SET read_at = NOW()
                WHERE user_id = :uid AND read_at IS NULL';

        $this->db->prepare($sql)->execute([
            ':uid' => $userId
        ]);
    }
}
