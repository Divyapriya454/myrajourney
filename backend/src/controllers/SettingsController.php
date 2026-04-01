<?php
declare(strict_types=1);

namespace Src\Controllers;

use Src\Models\SettingsModel;
use Src\Utils\Response;

class SettingsController
{
	private SettingsModel $settings;
	public function __construct(){ $this->settings = new SettingsModel(); }

	public function getMine(): void
	{
		$auth=$_SERVER['auth'] ?? [];
		$uid=(int)($auth['uid'] ?? 0);
		$data=$this->settings->getAll($uid);
		Response::json(['success'=>true,'data'=>$data]);
	}

	public function putMine(): void
	{
		$auth=$_SERVER['auth'] ?? [];
		$uid=(int)($auth['uid'] ?? 0);
		$body=json_decode(file_get_contents('php://input'), true) ?? [];

		$allowed = ['notifications_enabled','medication_reminders','appointment_reminders','theme','language'];

		// Format 1: {"key": "notifications_enabled", "value": "true"}
		if (isset($body['key'])) {
			$this->settings->put($uid, (string)$body['key'], isset($body['value']) ? (string)$body['value'] : null);
			Response::json(['success'=>true]);
			return;
		}

		// Format 2: {"notifications_enabled": true, "medication_reminders": true, ...}
		$updated = false;
		foreach ($allowed as $col) {
			if (array_key_exists($col, $body)) {
				$this->settings->put($uid, $col, $body[$col] !== null ? (string)$body[$col] : null);
				$updated = true;
			}
		}

		if (!$updated) {
			Response::json(['success'=>false,'error'=>['code'=>'VALIDATION','message'=>'Missing key']],422);
			return;
		}

		Response::json(['success'=>true]);
	}
}




















