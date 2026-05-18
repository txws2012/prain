<?php
if($page == 'api'){
	storeHandleApi();
}

if($page == 'admin' && $adminPage == 'store'){
	if(!LOGIN) jump('admin/login');
	$storeAdminTpl = new Tpl([
		'path' => '/ext/',
		'name' => 'store',
		'compile' => $conf['compile'],
	]);
	$storePage = get(2,'str','index');

	if($storePage == 'index'){
		$apps = db('store/app') ?: [];
		$users = db('store/user') ?: [];
		$comments = db('store/comment') ?: [];
		$published = 0;
		foreach($apps as &$a){ $a['icon'] = HOME.'ext/store/data/'.$a['id'].'/icon.png'; if($a['status'] === 'published') $published++; }
		unset($a);
		$html = storeAdminIndexHtml($apps, $users, $published);
		include $storeAdminTpl->view('store');
		exit;
	}

	if($storePage == 'app'){
		$action = get(3,'str');
		$id = get(4,'str');
		$apps = db('store/app') ?: [];

		if($action === 'toggle' && $id && isset($apps[$id])){
			$apps[$id]['status'] = $apps[$id]['status'] === 'published' ? 'draft' : 'published';
			dbSave('store/app', $apps);
			jump('admin/store');
		}
		if($action === 'delete' && $id && isset($apps[$id])){
			unset($apps[$id]);
			dbSave('store/app', $apps);
			$dataDir = ROOT.'ext/store/data/'.$id.'/';
			if(is_dir($dataDir)) $util->delete($dataDir);
			jump('admin/store');
		}
		if($action === 'upload' && $method == 'POST'){
			if(isset($_FILES['file'])){
				$file = $_FILES['file'];
				$sx = file_get_contents($file['tmp_name']);
				if($sx){
					$tmpDir = ROOT.'ext/store/tmp/';
					if(!is_dir($tmpDir)) mkdir($tmpDir, 0777, true);
					$util->delete($tmpDir);
					$util->createDir($tmpDir);
					unsx($sx, $tmpDir);
					unlink($file['tmp_name']);
					$confPath = $tmpDir.'conf.php';
					if(is_file($confPath)){
						$appConf = include $confPath;
						$id = $appConf['id'];
						$dataDir = ROOT.'ext/store/data/'.$id.'/';
						$util->delete($dataDir);
						$util->cut($tmpDir, $dataDir);
						$sxSavePath = $dataDir.$id.'.sx';
						file_put_contents($sxSavePath, $sx);
						$apps = db('store/app') ?: [];
						$appData = [
							'id' => $id,
							'type' => $appConf['type'],
							'author' => $appConf['author'],
							'authorId' => 0,
							'name' => $appConf['name'],
							'intro' => $appConf['intro'],
							'price' => isset($appConf['price']) ? floatval($appConf['price']) : 0,
							'home' => $appConf['home'],
							'version' => $appConf['version'],
							'limit' => $appConf['limit'],
							'content' => '',
							'status' => 'published',
							'downloadCount' => 0,
							'createTime' => time(),
							'updateTime' => time(),
						];
						if(isset($apps[$id])){
							$appData['downloadCount'] = $apps[$id]['downloadCount'];
							$appData['createTime'] = $apps[$id]['createTime'];
						}
						$apps[$id] = $appData;
						dbSave('store/app', $apps);
						msg('导入成功');
					}
					err('应用缺少配置文件');
				}
			}
			err('上传失败');
		}
		jump('admin/store');
	}

	if($storePage == 'user'){
		$action = get(3,'str');
		$id = get(4,'int');
		$users = db('store/user') ?: [];

		if($action === 'toggleDev' && $id){
			foreach($users as &$u){
				if($u['id'] === $id){
					$u['isDeveloper'] = !$u['isDeveloper'];
					break;
				}
			}
			dbSave('store/user', $users);
			jump('admin/store/user');
		}
		if($action === 'delete' && $id){
			$users = arrWhere($users, ['id'=>['!='.$id]]);
			dbSave('store/user', array_merge($users));
			jump('admin/store/user');
		}
		$html = storeAdminUserHtml($users);
		include $storeAdminTpl->view('store');
		exit;
	}

	if($storePage == 'import'){
		$html = storeAdminImportHtml();
		include $storeAdminTpl->view('store');
		exit;
	}
}

if($page == 'admin' && $adminPage == 'ext' && get(2) == 'setting' && get(3) == 'store' && $method == 'POST'){
	$storeKeyPath = EXT.'store/key.php';
	$storeKey = is_file($storeKeyPath) ? include $storeKeyPath : ['key'=>''];
	$storeKey['key'] = post('key','str','');
	save($storeKeyPath, $storeKey);
	jump('admin/ext/setting/store');
}
?>
