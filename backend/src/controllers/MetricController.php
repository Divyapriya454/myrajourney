<?php
declare(strict_types=1);

namespace Src\Controllers;

use Src\Models\MetricModel;
use Src\Utils\Response;

class MetricController
{
	private MetricModel $metrics;
	public function __construct(){ $this->metrics = new MetricModel(); }

	public function list(): void
	{
		$pid=(int)($_GET['patient_id'] ?? 0);
		$type=$_GET['metric_type'] ?? null;
		$from=$_GET['from'] ?? null;
		$to=$_GET['to'] ?? null;
		$data=$this->metrics->list($pid,$type,$from,$to);
		Response::json(['success'=>true,'data'=>$data]);
	}

	public function create(): void
	{
		$auth = $_SERVER['auth'] ?? [];
		$uid = (int)($auth['uid'] ?? 0);
		$role = $auth['role'] ?? '';
		
		$body=json_decode(file_get_contents('php://input'), true) ?? [];
		
		// Auto-set patient_id for PATIENT role
		if ($role === 'PATIENT') {
			$body['patient_id'] = $uid;
		}
		
		// Handle different field names for value (support both 'value' and 'metric_value')
		if (isset($body['metric_value']) && !isset($body['value'])) {
			$body['value'] = $body['metric_value'];
		}
		
		// Auto-set recorded_at if not provided
		if (!isset($body['recorded_at'])) {
			$body['recorded_at'] = date('Y-m-d H:i:s');
		}
		
		// Validate required fields (use isset to allow 0 values)
		$requiredValue = $body['value'] ?? $body['metric_value'] ?? null;
		foreach(['patient_id','metric_type'] as $k) {
			if (!isset($body[$k]) && !array_key_exists($k, $body)) {
				Response::json(['success'=>false,'error'=>['code'=>'VALIDATION','message'=>"Missing $k"]],422);
				return;
			}
			if ($body[$k] === '' || $body[$k] === null) {
				Response::json(['success'=>false,'error'=>['code'=>'VALIDATION','message'=>"Missing $k"]],422);
				return;
			}
		}
		
		// Check for value field specifically
		if ($requiredValue === null || $requiredValue === '') {
			Response::json(['success'=>false,'error'=>['code'=>'VALIDATION','message'=>"Missing value or metric_value"]],422);
			return;
		}
		
		$id=$this->metrics->create($body);
		Response::json(['success'=>true,'data'=>['id'=>$id]],201);
	}
}




















