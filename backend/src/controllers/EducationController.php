<?php
declare(strict_types=1);

namespace Src\Controllers;

use Src\Models\EducationModel;
use Src\Utils\Response;

class EducationController
{
	private EducationModel $edu;
	public function __construct(){ $this->edu = new EducationModel(); }

	public function list(): void
	{
		$page = max(1,(int)($_GET['page'] ?? 1));
		$limit = max(1,min(100,(int)($_GET['limit'] ?? 20)));
		$category = $_GET['category'] ?? null;
		$r = $this->edu->list($category, $page, $limit);
		Response::json(['success'=>true,'data'=>$r['items'],'meta'=>['total'=>$r['total'],'page'=>$page,'limit'=>$limit]]);
	}

	public function getBySlug(string $slug): void
	{
		$item=$this->edu->getBySlug($slug);
		if(!$item){ Response::json(['success'=>false,'error'=>['code'=>'NOT_FOUND','message'=>'Not found']],404); return; }
		Response::json(['success'=>true,'data'=>$item]);
	}

	public function getById(int $id): void
	{
		$item=$this->edu->getById($id);
		if(!$item){ Response::json(['success'=>false,'error'=>['code'=>'NOT_FOUND','message'=>'Not found']],404); return; }
		Response::json(['success'=>true,'data'=>$item]);
	}
}




















