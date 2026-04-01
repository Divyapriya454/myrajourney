<?php
declare(strict_types=1);

namespace Src\Models;

use PDO;
use Src\Config\DB;

class SymptomModel
{
	private PDO $db;
	public function __construct(){ $this->db = DB::conn(); }

	public function list(int $patientId, ?string $from, ?string $to): array
	{
		$where='WHERE patient_id=:pid'; $p=[':pid'=>$patientId];
		if ($from) { $where.=' AND `date` >= :f'; $p[':f']=$from; }
		if ($to) { $where.=' AND `date` <= :t'; $p[':t']=$to; }
		$stmt=$this->db->prepare("SELECT * FROM symptoms $where ORDER BY `created_at` DESC, `date` DESC");
		$stmt->execute($p);
		return $stmt->fetchAll();
	}

	public function create(array $d): int
	{
		// Validate joint_count if provided
		if (isset($d['joint_count'])) {
			$jointCount = (int)$d['joint_count'];
			if ($jointCount < 0 || $jointCount > 10) {
				throw new \InvalidArgumentException("Joint count must be between 0 and 10");
			}
		}
		
		$stmt=$this->db->prepare('INSERT INTO symptoms (patient_id, `date`, pain_level, stiffness_level, fatigue_level, joint_count, notes, created_at) VALUES (:pid,:date,:pain,:stiff,:fatigue,:joints,:notes,NOW())');
		$stmt->execute([
			':pid'=>(int)$d['patient_id'],
			':date'=>$d['date'],
			':pain'=>$d['pain_level'],
			':stiff'=>$d['stiffness_level'],
			':fatigue'=>$d['fatigue_level'],
			':joints'=>isset($d['joint_count']) ? (int)$d['joint_count'] : null,
			':notes'=>$d['notes'] ?? null,
		]);
		return (int)$this->db->lastInsertId();
	}
}




















