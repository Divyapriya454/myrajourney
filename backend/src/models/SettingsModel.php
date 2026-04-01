<?php
declare(strict_types=1);

namespace Src\Models;

use PDO;
use Src\Config\DB;

class SettingsModel
{
	private PDO $db;
	public function __construct(){ $this->db = DB::conn(); }

	public function getAll(int $userId): array
	{
		$stmt=$this->db->prepare('SELECT notifications_enabled, medication_reminders, appointment_reminders, theme, language FROM settings WHERE user_id=:u');
		$stmt->execute([':u'=>$userId]);
		$row = $stmt->fetch();
		if (!$row) {
			// Return defaults if no settings row exists
			return [
				'notifications_enabled' => 1,
				'medication_reminders' => 1,
				'appointment_reminders' => 1,
				'theme' => 'light',
				'language' => 'en'
			];
		}
		return $row;
	}

	public function put(int $userId, string $key, ?string $value): void
	{
		// Map generic key-value to actual columns
		$allowed = ['notifications_enabled','medication_reminders','appointment_reminders','theme','language'];
		if (!in_array($key, $allowed)) {
			// Silently ignore unknown keys
			return;
		}
		$stmt=$this->db->prepare("INSERT INTO settings (user_id, $key, updated_at) VALUES (:u,:v,NOW()) ON DUPLICATE KEY UPDATE $key=VALUES($key), updated_at=VALUES(updated_at)");
		$stmt->execute([':u'=>$userId,':v'=>$value]);
	}
}




















