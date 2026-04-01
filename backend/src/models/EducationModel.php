<?php
declare(strict_types=1);

namespace Src\Models;

use PDO;
use Src\Config\DB;

class EducationModel
{
	private PDO $db;
	public function __construct(){ $this->db = DB::conn(); }

	public function list(?string $category, int $page, int $limit): array
	{
		$where=''; $params=[];
		if ($category) { $where='WHERE category=:c'; $params[':c']=$category; }
		$offset = ($page-1)*$limit;
		$total = (int)$this->db->query("SELECT COUNT(*) FROM education_articles" . ($where ? " $where" : ''))->fetchColumn();
		$stmt=$this->db->prepare("SELECT slug,title,category,image_url,published_date,updated_at FROM education_articles $where ORDER BY published_date DESC, updated_at DESC LIMIT :lim OFFSET :off");
		if ($category) $stmt->bindValue(':c',$category);
		$stmt->bindValue(':lim',$limit,PDO::PARAM_INT);
		$stmt->bindValue(':off',$offset,PDO::PARAM_INT);
		$stmt->execute();
		return ['items'=>$stmt->fetchAll(),'total'=>$total];
	}

	public function getBySlug(string $slug): ?array
	{
		$stmt=$this->db->prepare('SELECT * FROM education_articles WHERE slug=:s');
		$stmt->execute([':s'=>$slug]);
		$row=$stmt->fetch();
		return $row ?: null;
	}

	public function getById(int $id): ?array
	{
		$stmt=$this->db->prepare('SELECT * FROM education_articles WHERE id=:id');
		$stmt->execute([':id'=>$id]);
		$row=$stmt->fetch();
		return $row ?: null;
	}
}





