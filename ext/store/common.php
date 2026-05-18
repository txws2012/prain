<?php
if($page == 'admin'){
	hook('admin_sidebar_menu_3','<a href="'.URL.'admin/store">应用市场</a>');
}

function storeUrl($params){
	return !empty($params['url']) ? $params['url'] : URL;
}

function storeMenu($url, $active = ''){
	return '<div class="headline">应用中心</div>'
		.'<div class="app-menu"><div class="app-menu-left">'
		.'<a href="'.$url.'admin/app/tpl" class="'.($active==='tpl'?'active':'').'">主题</a>'
		.'<a href="'.$url.'admin/app/ext" class="'.($active==='ext'?'active':'').'">扩展</a>'
		.'<a href="'.$url.'admin/app/user" class="'.($active==='user'?'active':'').'">用户中心</a>'
		.'</div></div>';
}

function storeJson($error, $message, $data = null){
	$arr = ['error' => $error, 'message' => $message];
	if($data !== null) $arr['data'] = $data;
	header('Content-Type: application/json; charset=utf-8');
	exit(json_encode($arr, JSON_UNESCAPED_UNICODE));
}

function storeGetUser($params){
	if(empty($params['key'])) return null;
	$users = db('store/user');
	if(!$users) return null;
	foreach($users as $u){
		if(isset($u['key']) && $u['key'] === $params['key']) return $u;
	}
	return null;
}

function storeRequireUser($params){
	$user = storeGetUser($params);
	if(!$user) storeJson(true, '请登录！', 4001);
	return $user;
}

function storeInitDb(){
	if(!is_dir(ROOT.'db/store')) mkdir(ROOT.'db/store', 0777, true);
	$tables = ['user','app','comment','order','sale','withdraw'];
	foreach($tables as $t){
		$path = ROOT.'db/store/'.$t.'.php';
		if(!is_file($path)) save($path, []);
	}
}

function storeIconUrl($id){
	return HOME.'ext/store/data/'.$id.'/icon.png';
}

function storeHandleApi(){
	if(session_status() === PHP_SESSION_ACTIVE) session_write_close();
	storeInitDb();
	$endpoint = get(1,'str','');
	$sub = get(2,'str','');
	$params = $_POST;

	switch($endpoint){
		case 'login': $res = storeApiLogin($params); break;
		case 'register': $res = storeApiRegister($params); break;
		case 'forget': $res = storeApiForget($sub, $params); break;
		case 'tpl': $res = storeApiList('tpl', $params); break;
		case 'ext': $res = storeApiList('ext', $params); break;
		case 'view': $res = storeApiView($params); break;
		case 'comment': $res = storeApiComment($params); break;
		case 'deleteComment': $res = storeApiDeleteComment($params); break;
		case 'user': $res = storeApiUser($sub, $params); break;
		case 'apply': $res = storeApiApply($params); break;
		case 'apply-developer': $res = storeApiApplyDeveloper($params); break;
		case 'developer': $res = storeApiDeveloper($params); break;
		case 'manage': $res = storeApiDeveloper($params); break;
		case 'sales': $res = storeApiSales($params); break;
		case 'withdraw': $res = storeApiWithdraw($params); break;
		case 'withdraw-submit': $res = storeApiWithdrawSubmit($params); break;
		case 'publish': $res = storeApiPublish($params); break;
		case 'publish-view': $res = storeApiPublishView($params); break;
		case 'publish-upload': $res = storeApiPublishUpload($params); break;
		case 'editor': $res = storeApiEditor($params); break;
		case 'editorForm': $res = storeApiEditorForm($params); break;
		case 'put': $res = storeApiStatus('put', $params); break;
		case 'pull': $res = storeApiStatus('pull', $params); break;
		case 'del': $res = storeApiStatus('del', $params); break;
		case 'upload': $res = storeApiUpload($params); break;
		case 'download': $res = storeApiDownload($params); break;
		case 'pay': $res = storeApiPay($sub, $params); break;
		case 'system': $res = storeApiSystem($params); break;
		default: $res = ['error'=>true,'message'=>'非法操作','data'=>4000];
	}

	if(is_array($res)){
		header('Content-Type: application/json; charset=utf-8');
		echo json_encode($res, JSON_UNESCAPED_UNICODE);
	}else{
		header('Content-Type: text/html; charset=utf-8');
		echo $res;
	}
	exit;
}

function storeApiLogin($params){
	$url = storeUrl($params);
	if(!empty($params['form'])){
		$username = isset($params['username']) ? $params['username'] : '';
		$password = isset($params['password']) ? $params['password'] : '';
		$users = db('store/user');
		if(!$users) return ['error'=>true,'message'=>'用户不存在'];
		foreach($users as $u){
			if($u['username'] === $username && $u['password'] === md5($password)){
				$key = md5($u['id'].$u['username'].time().randStr(16));
				dbUpdate('store/user', ['id'=>$u['id']], ['key'=>$key, 'lastLogin'=>time()]);
				return ['error'=>false,'message'=>'登录成功','data'=>$key];
			}
		}
		return ['error'=>true,'message'=>'用户名或密码错误'];
	}
	$html = storeMenu($url, 'user')
		.'<div class="title">用户登录</div>'
		.'<ul><li>使用应用中心需要登录账号，没有账号？请点击下方链接注册账号</li>'
		.'<li>已有账号，请直接登录</li></ul>'
		.'<div id="ajaxForm">'
		.'<input type="hidden" name="form" value="1"/>'
		.'<input type="text" name="username" placeholder="账号"/>'
		.'<input type="password" name="password" placeholder="密码"/>'
		.'<div class="btn" onclick="submit()">登录</div>'
		.'<p><a href="'.$url.'admin/app/register" class="red">注册账号</a> ‧ <a href="'.$url.'admin/app/forget/username" class="red">忘记账号</a> ‧ <a href="'.$url.'admin/app/forget/password" class="red">忘记密码</a></p>'
		.'</div>'
		.'<script>function submit(){sx.ajax(SX.CONF.URL+\'admin/app/login\',\'#ajaxForm\').then(res=>{if(res.error){sx.pop(res.message)}else{window.location.href=SX.CONF.URL+\'admin/app/user\'}})}</script>';
	return $html;
}

function storeApiRegister($params){
	$url = storeUrl($params);
	if(!empty($params['form'])){
		$username = isset($params['username']) ? $params['username'] : '';
		$password = isset($params['password']) ? $params['password'] : '';
		$mail = isset($params['mail']) ? $params['mail'] : '';
		if(!$username || !$password) return ['error'=>true,'message'=>'参数不完整'];
		$users = db('store/user');
		if(!$users) $users = [];
		foreach($users as $u){
			if($u['username'] === $username) return ['error'=>true,'message'=>'用户名已存在'];
		}
		$id = $users ? max(array_column($users, 'id')) + 1 : 1;
		$users[] = [
			'id' => $id,
			'username' => $username,
			'password' => md5($password),
			'mail' => $mail,
			'key' => '',
			'isDeveloper' => false,
			'balance' => 0,
			'lastLogin' => 0,
			'createTime' => time(),
		];
		dbSave('store/user', $users);
		return ['error'=>false,'message'=>'注册成功'];
	}
	$html = storeMenu($url, 'user')
		.'<div class="title">注册账号 ‧ <a href="'.$url.'admin/app/login" class="red">用户登录</a></div>'
		.'<div id="register">'
		.'<input type="hidden" name="form" value="1"/>'
		.'<div class="form"><div class="key">账号</div><div class="value"><input type="text" name="username" placeholder="账号"/><small>2-12位汉字、字母、数字</small></div></div>'
		.'<div class="form"><div class="key">密码</div><div class="value"><input type="password" name="password" placeholder="密码"/><small>6-18位字母、数字</small></div></div>'
		.'<div class="form"><div class="key">邮箱</div><div class="value"><input type="text" name="mail" placeholder="邮箱"/><small>您的常用邮箱，用于找回密码</small></div></div>'
		.'<div class="form"><div class="key"></div><div class="value"><div class="btn" onclick="submit()">注册</div></div></div>'
		.'</div>'
		.'<script>function submit(){sx.ajax(SX.CONF.URL+\'admin/app/register\',\'#register\').then(res=>{sx.pop(res.message,function(){if(!res.error)sx.jump(\'admin/app/login\')})})}</script>';
	return $html;
}

function storeApiForget($type, $params){
	$url = storeUrl($params);
	if(!empty($params['form'])){
		$mail = isset($params['mail']) ? $params['mail'] : '';
		if(!$mail) return ['error'=>true,'message'=>'请输入邮箱'];
		$users = db('store/user');
		if(!$users) return ['error'=>true,'message'=>'邮箱未绑定'];
		foreach($users as $u){
			if(isset($u['mail']) && $u['mail'] === $mail) return ['error'=>false,'message'=>'账号已发送到您的邮箱','data'=>''];
		}
		return ['error'=>true,'message'=>'该邮箱未绑定任何账号'];
	}
	$label = $type === 'password' ? '找回密码' : '找回账号';
	$html = storeMenu($url, 'user')
		.'<div class="title">'.$label.' ‧ <a href="'.$url.'admin/app/login" class="red">用户登录</a></div>'
		.'<div id="forget-'.$type.'">'
		.'<input type="hidden" name="form" value="1"/>'
		.'<div class="form"><div class="key">绑定的邮箱</div><div class="value"><input type="text" name="mail" placeholder="邮箱"/><small>输入您账号绑定的邮箱，'.($type==='password'?'重置密码链接':'您的账号').'会发送到该邮箱</small></div></div>'
		.'<div class="form"><div class="key"></div><div class="value"><div class="btn" onclick="submit()">找回</div></div></div>'
		.'</div>'
		.'<script>function submit(){sx.ajax(SX.CONF.URL+\'admin/app/forget/'.$type.'\',\'#forget-'.$type.'\').then(res=>{sx.pop(res.message,function(){if(!res.error)sx.jump(\'admin/app/login\')})})}</script>';
	return $html;
}

function storeApiList($type, $params){
	$url = storeUrl($params);
	$pageNum = isset($params['page']) ? intval($params['page']) : 1;
	$pageSize = isset($params['size']) ? intval($params['size']) : 30;
	$apps = db('store/app');
	$list = [];
	if($apps){
		foreach($apps as $k => $v){
			if($v['type'] === $type && $v['status'] === 'published') $list[$k] = $v;
		}
	}
	$total = count($list);
	$list = array_slice($list, ($pageNum-1)*$pageSize, $pageSize);
	$typeName = $type === 'tpl' ? '主题' : '扩展';

	$html = storeMenu($url, $type)
		.'<div class="title">'.$typeName.' ‧ '.$total.'个</div>';

	$tplList = isset($params['tpl']) && is_array($params['tpl']) ? $params['tpl'] : [];
	$extList = isset($params['ext']) && is_array($params['ext']) ? $params['ext'] : [];

	if($type === 'tpl'){
		$html .= '<ul class="tpl">';
		foreach($list as $app){
			$priceHtml = $app['price'] > 0 ? '<span class="tpl-price">￥'.number_format($app['price'],2).'</span>' : '<span class="tpl-free">免费</span>';
			$html .= '<li>'
				.'<div class="tpl-icon"><a href="'.$url.'admin/app/view/'.$app['id'].'"><img src="'.storeIconUrl($app['id']).'"/></a></div>'
				.'<div class="tpl-info"><div class="tpl-name">'
				.'<a href="'.$url.'admin/app/view/'.$app['id'].'">'.htmlspecialchars($app['name']).'</a>'
				.'<span class="tpl-version">v'.htmlspecialchars($app['version']).'</span>'
				.'</div><div class="tpl-operate">'.$priceHtml.'</div></div></li>';
		}
		$html .= '</ul>';
	}else{
		$html .= '<ul class="ext">';
		foreach($list as $app){
			$installed = false;
			foreach($extList as $e){ if(is_array($e) && $e['id'] === $app['id']){ $installed = true; break; } }
			$priceHtml = $app['price'] > 0 ? '<span class="tpl-price">￥'.number_format($app['price'],2).'</span>' : '<span class="green">免费</span>';
			$liClass = $installed ? 'ext-install' : '';
			$html .= '<li class="'.$liClass.'">'
				.'<div class="ext-icon"><a href="'.$url.'admin/app/view/'.$app['id'].'"><img src="'.storeIconUrl($app['id']).'"/></a></div>'
				.'<div class="ext-info"><div class="ext-name">'
				.'<a href="'.$url.'admin/app/view/'.$app['id'].'">'.htmlspecialchars($app['name']).'</a>'
				.'<span class="ext-version">v'.htmlspecialchars($app['version']).'</span>'
				.$priceHtml.'</div>'
				.'<div class="ext-intro">'.htmlspecialchars($app['intro']).'</div></div></li>';
		}
		$html .= '</ul>';
	}
	return $html;
}

function storeApiView($params){
	$url = storeUrl($params);
	$id = isset($params['id']) ? $params['id'] : '';
	if(!$id) return storeMenu($url).'<h3 class="center">应用不存在</h3>';
	$apps = db('store/app');
	if(!$apps || !isset($apps[$id])) return storeMenu($url).'<h3 class="center">应用不存在</h3>';
	$app = $apps[$id];

	$tplList = isset($params['tpl']) && is_array($params['tpl']) ? $params['tpl'] : [];
	$extList = isset($params['ext']) && is_array($params['ext']) ? $params['ext'] : [];
	$installed = false;
	if($app['type'] === 'tpl'){
		foreach($tplList as $t){ if(is_array($t) && $t['id'] === $id){ $installed = true; break; } }
	}else{
		foreach($extList as $e){ if(is_array($e) && $e['id'] === $id){ $installed = true; break; } }
	}

	$priceHtml = $app['price'] > 0
		? '<div class="app-view-price">￥'.number_format($app['price'],2).'</div>'
		: '<div class="app-view-free">免费</div>';

	$installBtn = $installed
		? '<a href="javascript:;" onclick="doInstall(0,\''.$app['id'].'\')" class="app-view-btn">更新</a>'
		: '<a href="javascript:;" onclick="doInstall(1,\''.$app['id'].'\')" class="app-view-btn">安装</a>';

	$comments = db('store/comment');
	$commentList = [];
	if($comments){
		foreach($comments as $c) if($c['appId'] === $id) $commentList[] = $c;
	}
	$commentHtml = '';
	foreach($commentList as $c){
		$replyHtml = '';
		if(!empty($c['pid'])){
			$replyHtml = '<div class="comment-replys"><span onclick="sx.reply('.$c['pid'].')">查看回复</span></div>';
		}
		$commentHtml .= '<li><div class="comment-title"><span class="comment-user id-'.$c['userId'].'">'.htmlspecialchars($c['username']).'</span>'
			.'<span class="comment-time">'.humanDate($c['createTime']).'</span>'
			.'<a href="javascript:;" onclick="sx.reply('.$c['userId'].')" class="comment-reply">回复</a>'
			.'</div><p>'.htmlspecialchars($c['content']).'</p>'.$replyHtml.'</li>';
	}

	$html = storeMenu($url, $app['type'])
		.'<div class="app-view"><div class="app-view-main">'
		.'<div class="app-view-img"><img src="'.storeIconUrl($id).'"/>'
		.($app['price'] <= 0 ? '<div class="app-view-free">免费</div>' : '')
		.'</div>'
		.'<div class="app-view-content">'
		.'<div class="app-view-name">'.htmlspecialchars($app['name']).'</div>'
		.'<div class="app-view-intro">'.htmlspecialchars($app['intro']).'</div>'
		.'<div class="app-view-info"><div class="app-view-info-item">'
		.'<p>ID：'.htmlspecialchars($app['id']).'</p>'
		.'<p>版本：'.htmlspecialchars($app['version']).'</p>'
		.'<p>安装：'.($app['downloadCount'] ?? 0).'次</p>'
		.'<p>最低系统版本：'.htmlspecialchars($app['limit']).'</p>'
		.'</div><div class="app-view-info-item">'
		.'<p>作者：'.htmlspecialchars($app['author']).'</p>'
		.'<p>主页：<a href="'.htmlspecialchars($app['home']).'" target="_blank">'.htmlspecialchars($app['home']).'</a></p>'
		.'<p>发布：'.humanDate($app['createTime']).'</p>'
		.'<p>更新：'.humanDate($app['updateTime']).'</p>'
		.'</div></div>'
		.($app['price'] > 0 ? $priceHtml : '')
		.$installBtn
		.'</div></div>'
		.'<div class="app-view-content"><div class="fk">'.nl2br(htmlspecialchars($app['content'] ?? '')).'</div></div>'
		.'<div class="comment">'
		.'<div class="title">评论留言</div>'
		.'<div id="comment">'
		.'<input type="hidden" name="id" value="'.$id.'"/>'
		.'<input type="hidden" name="pid" value="0"/>'
		.'<textarea name="content" placeholder="评论内容"></textarea>'
		.'<div class="btn bg-blue" onclick="submitComment()">提交</div>'
		.'</div>'
		.'<div class="tip">一共<span>'.count($commentList).'</span>条留言</div>'
		.'<ul>'.$commentHtml.'</ul>'
		.'</div></div>'
		.'<script>'
		.'function submitComment(){sx.ajax(SX.CONF.URL+\'admin/app/comment\',\'#comment\').then(res=>{sx.pop(res.message,function(){if(!res.error)location.reload()})})}'
		.'function doInstall(type,id){sx.ajax(SX.CONF.URL+\'admin/app/install/\'+id).then(res=>{if(res.error){sx.pop(res.message)}else{if(type===1){sx.alert({content:\'安装成功啦，是否需要到【'.($app['type']==='tpl'?'主题管理':'扩展管理').'】中查看？\',btn:[\'取消\',\'转到'.($app['type']==='tpl'?'主题管理':'扩展管理').'\'],yes(){sx.jump(\'admin/'.($app['type']==='tpl'?'tpl':'ext').'\')}})}else{sx.pop(\'更新成功\',function(){location.reload()})}}})}'
		.'</script>';
	return $html;
}

function storeApiComment($params){
	$user = storeRequireUser($params);
	$id = isset($params['id']) ? $params['id'] : '';
	$content = isset($params['content']) ? trim($params['content']) : '';
	if(!$id || !$content) return ['error'=>true,'message'=>'参数不完整'];
	$apps = db('store/app');
	if(!$apps || !isset($apps[$id])) return ['error'=>true,'message'=>'应用不存在'];
	$comments = db('store/comment');
	if(!$comments) $comments = [];
	$cid = $comments ? max(array_column($comments, 'id')) + 1 : 1;
	$comments[] = [
		'id' => $cid,
		'appId' => $id,
		'pid' => isset($params['pid']) ? intval($params['pid']) : 0,
		'userId' => $user['id'],
		'username' => $user['username'],
		'content' => $content,
		'createTime' => time(),
	];
	dbSave('store/comment', $comments);
	return ['error'=>false,'message'=>'评论成功'];
}

function storeApiDeleteComment($params){
	$user = storeRequireUser($params);
	$id = isset($params['id']) ? intval($params['id']) : 0;
	$comments = db('store/comment');
	if(!$comments) return ['error'=>true,'message'=>'评论不存在'];
	$found = false;
	foreach($comments as $k => $c){
		if($c['id'] === $id && ($c['userId'] === $user['id'] || $user['id'] === 1)){
			unset($comments[$k]);
			$found = true;
			break;
		}
	}
	if(!$found) return ['error'=>true,'message'=>'评论不存在'];
	dbSave('store/comment', array_merge($comments));
	return ['error'=>false,'message'=>'删除成功'];
}

function storeApiUser($sub, $params){
	$url = storeUrl($params);
	$user = storeRequireUser($params);
	if($sub === 'settingUpdate'){
		if(isset($params['mail'])){
			$users = db('store/user');
			foreach($users as &$u){
				if($u['id'] === $user['id']){
					$u['mail'] = $params['mail'];
					if(!empty($params['password'])) $u['password'] = md5($params['password']);
					break;
				}
			}
			dbSave('store/user', $users);
			return ['error'=>false,'message'=>'保存成功'];
		}
		return ['error'=>true,'message'=>'参数不完整'];
	}
	$tab = $sub ? $sub : 'install';
	$tabs = ['install'=>'已安装','setting'=>'账号设置'];
	$tabHtml = '';
	foreach($tabs as $k => $v){
		$active = $tab === $k ? 'active' : '';
		$tabHtml .= '<a href="'.$url.'admin/app/user/'.$k.'" class="'.$active.'">'.$v.'</a>';
	}
	$body = storeMenu($url, 'user')
		.'<div class="app-menu"><div class="app-menu-left">'.$tabHtml.'</div></div>';

	if($tab === 'setting'){
		$body .= '<div id="setting">'
			.'<input type="hidden" name="form" value="1"/>'
			.'<div class="form"><div class="key">账号</div><div class="value"><input type="text" value="'.htmlspecialchars($user['username']).'" disabled/></div></div>'
			.'<div class="form"><div class="key">邮箱</div><div class="value"><input type="text" name="mail" value="'.htmlspecialchars(isset($user['mail'])?$user['mail']:'').'"/></div></div>'
			.'<div class="form"><div class="key">密码</div><div class="value"><input type="password" name="password" placeholder="留空不修改"/></div></div>'
			.'<div class="form"><div class="key"></div><div class="value"><div class="btn" onclick="submit()">保存</div></div></div>'
			.'</div>'
			.'<script>function submit(){sx.ajax(SX.CONF.URL+\'admin/app/user/settingUpdate\',\'#setting\').then(res=>{sx.pop(res.message,function(){if(!res.error)sx.jump(\'admin/app/user\')})})}</script>';
	}else{
		$tplList = isset($params['tpl']) && is_array($params['tpl']) ? $params['tpl'] : [];
		$extList = isset($params['ext']) && is_array($params['ext']) ? $params['ext'] : [];
		$body .= '<div class="title">已安装主题</div><ul class="tpl">';
		foreach($tplList as $t){
			if(!is_array($t)) continue;
			$body .= '<li><div class="tpl-icon"><a href="'.$url.'admin/app/view/'.$t['id'].'"><img src="'.storeIconUrl($t['id']).'"/></a></div>'
				.'<div class="tpl-info"><div class="tpl-name"><a href="'.$url.'admin/app/view/'.$t['id'].'">'.htmlspecialchars($t['name']).'</a><span class="tpl-version">v'.htmlspecialchars($t['version']).'</span></div>'
				.'<div class="tpl-operate"><span class="tpl-free">已安装</span></div></div></li>';
		}
		$body .= '</ul><div class="title">已安装扩展</div><ul class="ext">';
		foreach($extList as $e){
			if(!is_array($e)) continue;
			$body .= '<li class="ext-install"><div class="ext-icon"><a href="'.$url.'admin/app/view/'.$e['id'].'"><img src="'.storeIconUrl($e['id']).'"/></a></div>'
				.'<div class="ext-info"><div class="ext-name"><a href="'.$url.'admin/app/view/'.$e['id'].'">'.htmlspecialchars($e['name']).'</a><span class="ext-version">v'.htmlspecialchars($e['version']).'</span><span class="green">已安装</span></div>'
				.'<div class="ext-intro">'.htmlspecialchars($e['intro']).'</div></div></li>';
		}
		$body .= '</ul>';
	}
	return $body;
}

function storeApiApply($params){
	$url = storeUrl($params);
	$user = storeRequireUser($params);
	$html = storeMenu($url, 'user')
		.'<div class="title">申请成为开发者</div>'
		.'<div class="tip">申请成为开发者后可以发布应用到市场</div>'
		.'<div class="btn" onclick="submit()">申请</div>'
		.'<script>function submit(){sx.ajax(SX.CONF.URL+\'admin/app/apply\').then(res=>{sx.pop(res.message)})}</script>';
	return $html;
}

function storeApiApplyDeveloper($params){
	$user = storeRequireUser($params);
	$users = db('store/user');
	foreach($users as &$u){
		if($u['id'] === $user['id']){
			$u['isDeveloper'] = true;
			break;
		}
	}
	dbSave('store/user', $users);
	return ['error'=>false,'message'=>'申请成功'];
}

function storeApiDeveloper($params){
	$url = storeUrl($params);
	$user = storeRequireUser($params);
	if(empty($user['isDeveloper'])) return storeMenu($url, 'user').'<div class="center" style="padding:40px;">你还未成为开发者，<a href="'.$url.'admin/app/apply">去申请</a></div>';
	$tab = isset($params['tab']) ? $params['tab'] : 'tpl';
	$tabs = ['tpl'=>'主题','ext'=>'扩展'];
	$tabHtml = '';
	foreach($tabs as $k => $v){
		$active = $tab === $k ? 'active' : '';
		$tabHtml .= '<a href="'.$url.'admin/app/developer/'.$k.'" class="'.$active.'">'.$v.'</a>';
	}
	$apps = db('store/app');
	$list = [];
	if($apps){
		foreach($apps as $v){
			if($v['authorId'] === $user['id'] && $v['type'] === $tab) $list[] = $v;
		}
	}
	$body = storeMenu($url, 'user')
		.'<div class="app-menu"><div class="app-menu-left">'.$tabHtml
		.'<a href="'.$url.'admin/app/publish" style="margin-left:10px;">发布新应用</a>'
		.'</div></div>';
	if($list){
		$body .= '<ul class="ext">';
		foreach($list as $app){
			$statusLabel = $app['status'] === 'published' ? '<span class="green">已上架</span>' : '<span style="color:#999;">未上架</span>';
			$body .= '<li>'
				.'<div class="ext-icon"><a href="'.$url.'admin/app/view/'.$app['id'].'"><img src="'.storeIconUrl($app['id']).'"/></a></div>'
				.'<div class="ext-info"><div class="ext-name">'
				.'<a href="'.$url.'admin/app/editor/'.$app['id'].'">'.htmlspecialchars($app['name']).'</a>'
				.'<span class="ext-version">v'.htmlspecialchars($app['version']).'</span>'.$statusLabel.'</div>'
				.'<div class="ext-intro">'
				.'<a href="'.$url.'admin/app/editor/'.$app['id'].'" class="ext-btn">编辑</a> '
				.($app['status'] === 'published' ? '<a href="'.$url.'admin/app/pull/'.$app['type'].'/'.$app['id'].'" class="ext-btn">下架</a>' : '<a href="'.$url.'admin/app/put/'.$app['type'].'/'.$app['id'].'" class="ext-btn">上架</a>')
				.' <a href="'.$url.'admin/app/del/'.$app['type'].'/'.$app['id'].'" class="ext-btn" onclick="return confirm(\'确定删除？\')">删除</a>'
				.'</div></div></li>';
		}
		$body .= '</ul>';
	}else{
		$body .= '<div class="center" style="padding:40px;color:#999;">暂无应用</div>';
	}
	return $body;
}

function storeApiSales($params){
	$url = storeUrl($params);
	$user = storeRequireUser($params);
	$sales = db('store/sale');
	$list = [];
	if($sales){
		foreach($sales as $s) if($s['sellerId'] === $user['id']) $list[] = $s;
	}
	$html = storeMenu($url, 'user')
		.'<div class="title">销售记录 ‧ '.count($list).'条</div>';
	if($list){
		$html .= '<table><thead><tr><th>应用</th><th>金额</th><th>时间</th></tr></thead><tbody>';
		foreach($list as $s) $html .= '<tr><td>'.htmlspecialchars($s['appName']).'</td><td>￥'.$s['amount'].'</td><td>'.humanDate($s['createTime']).'</td></tr>';
		$html .= '</tbody></table>';
	}else{
		$html .= '<div class="empty">暂无销售记录</div>';
	}
	return $html;
}

function storeApiWithdraw($params){
	$url = storeUrl($params);
	$user = storeRequireUser($params);
	$withdraws = db('store/withdraw');
	$list = [];
	if($withdraws){
		foreach($withdraws as $w) if($w['userId'] === $user['id']) $list[] = $w;
	}
	$html = storeMenu($url, 'user')
		.'<div class="title">我的提现</div>'
		.'<div class="tip">余额：￥'.number_format($user['balance'],2).'</div>'
		.'<div id="withdraw">'
		.'<input type="hidden" name="form" value="1"/>'
		.'<div class="form"><div class="key">金额</div><div class="value"><input type="number" name="amount" step="0.01" placeholder="提现金额"/></div></div>'
		.'<div class="form"><div class="key"></div><div class="value"><div class="btn" onclick="submit()">申请提现</div></div></div>'
		.'</div>'
		.'<script>function submit(){sx.ajax(SX.CONF.URL+\'admin/app/withdraw\',\'#withdraw\').then(res=>{sx.pop(res.message)})}</script>';
	if($list){
		$html .= '<table><thead><tr><th>金额</th><th>状态</th><th>时间</th></tr></thead><tbody>';
		foreach($list as $w){
			$statusMap = ['pending'=>'待审核','approved'=>'已通过','rejected'=>'已拒绝'];
			$html .= '<tr><td>￥'.$w['amount'].'</td><td>'.$statusMap[$w['status']].'</td><td>'.humanDate($w['createTime']).'</td></tr>';
		}
		$html .= '</tbody></table>';
	}
	return $html;
}

function storeApiWithdrawSubmit($params){
	$user = storeRequireUser($params);
	$amount = isset($params['amount']) ? floatval($params['amount']) : 0;
	if($amount <= 0) return ['error'=>true,'message'=>'金额必须大于0'];
	if($amount > $user['balance']) return ['error'=>true,'message'=>'余额不足'];
	$withdraws = db('store/withdraw');
	if(!$withdraws) $withdraws = [];
	$withdraws[] = [
		'id' => $withdraws ? max(array_column($withdraws, 'id')) + 1 : 1,
		'userId' => $user['id'],
		'amount' => $amount,
		'status' => 'pending',
		'createTime' => time(),
	];
	dbSave('store/withdraw', $withdraws);
	$users = db('store/user');
	foreach($users as &$u){
		if($u['id'] === $user['id']){ $u['balance'] -= $amount; break; }
	}
	dbSave('store/user', $users);
	return ['error'=>false,'message'=>'提现申请已提交'];
}

function storeApiPublish($params){
	$url = storeUrl($params);
	$user = storeRequireUser($params);
	if(empty($user['isDeveloper'])) return storeMenu($url, 'user').'<div class="center" style="padding:40px;">你还未成为开发者，<a href="'.$url.'admin/app/apply">去申请</a></div>';
	$html = storeMenu($url, 'user')
		.'<div class="title">发布新应用</div>'
		.'<div class="tip">请上传.sx格式的应用包文件，上传后将进入应用信息编辑页面</div>'
		.'<div class="btn" onclick="doUpload()">选择应用包</div>'
		.'<script>function doUpload(){var el=document.createElement(\'input\');el.type=\'file\';el.accept=\'.sx\';el.onchange=function(){var formData=new FormData();formData.append(\'file\',this.files[0]);sx.ajax(SX.CONF.URL+\'admin/app/publish/upload\',formData).then(function(res){if(res.error){sx.pop(res.message)}else{sx.jump(\'admin/app/publish/submit/\'+res.data)}})};el.click()}</script>';
	return $html;
}

function storeApiPublishView($params){
	$url = storeUrl($params);
	$user = storeRequireUser($params);
	$app = isset($params['app']) ? $params['app'] : [];
	if(!$app) return storeMenu($url, 'user').'<h3 class="center">应用数据不存在</h3>';
	$html = storeMenu($url, 'user')
		.'<div class="title">确认发布</div>'
		.'<div id="publish">'
		.'<input type="hidden" name="id" value="'.htmlspecialchars($app['id']).'"/>'
		.'<div class="form"><div class="key">ID</div><div class="value"><input type="text" value="'.htmlspecialchars($app['id']).'" disabled/></div></div>'
		.'<div class="form"><div class="key">类型</div><div class="value"><input type="text" value="'.htmlspecialchars($app['type']).'" disabled/></div></div>'
		.'<div class="form"><div class="key">名称</div><div class="value"><input type="text" name="name" value="'.htmlspecialchars($app['name']).'"/></div></div>'
		.'<div class="form"><div class="key">简介</div><div class="value"><input type="text" name="intro" value="'.htmlspecialchars($app['intro']).'"/></div></div>'
		.'<div class="form"><div class="key">价格</div><div class="value"><input type="number" name="price" step="0.01" value="'.$app['price'].'"/></div></div>'
		.'<div class="form"><div class="key">主页</div><div class="value"><input type="text" name="home" value="'.htmlspecialchars($app['home']).'"/></div></div>'
		.'<div class="form"><div class="key">版本</div><div class="value"><input type="text" name="version" value="'.htmlspecialchars($app['version']).'"/></div></div>'
		.'<div class="form"><div class="key">兼容版本</div><div class="value"><input type="text" name="limit" value="'.htmlspecialchars($app['limit']).'"/></div></div>'
		.'<div class="form"><div class="key">详细介绍</div><div class="value"><textarea name="content" style="height:100px;">'.htmlspecialchars(isset($app['content'])?$app['content']:'').'</textarea></div></div>'
		.'<div class="form"><div class="key"></div><div class="value"><div class="btn" onclick="submit()">确认发布</div></div></div>'
		.'</div>'
		.'<script>function submit(){sx.ajax(SX.CONF.URL+\'admin/app/publish-submit\',\'#publish\').then(res=>{sx.pop(res.message,function(){if(!res.error)sx.jump(\'admin/app/developer\')})})}</script>';
	return $html;
}

function storeApiPublishUpload($params){
	$user = storeRequireUser($params);
	$sx = isset($params['sx']) ? $params['sx'] : '';
	$icon = isset($params['icon']) ? $params['icon'] : '';
	$conf = isset($params['conf']) ? $params['conf'] : [];
	$content = isset($params['content']) ? $params['content'] : '';
	if(!$sx || !$conf) return ['error'=>true,'message'=>'参数不完整'];
	$id = $conf['id'];
	$apps = db('store/app');
	if(!$apps) $apps = [];
	$appData = [
		'id' => $id,
		'type' => $conf['type'],
		'author' => $conf['author'],
		'authorId' => $user['id'],
		'name' => $conf['name'],
		'intro' => $conf['intro'],
		'price' => isset($conf['price']) ? floatval($conf['price']) : 0,
		'home' => $conf['home'],
		'version' => $conf['version'],
		'limit' => $conf['limit'],
		'contact' => isset($conf['contact']) ? $conf['contact'] : '',
		'content' => $content,
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
	$dataDir = EXT.'store/data/'.$id.'/';
	if(!is_dir(ROOT.$dataDir)) mkdir(ROOT.$dataDir, 0777, true);
	if($icon) file_put_contents(ROOT.$dataDir.'icon.png', $icon);
	file_put_contents(ROOT.$dataDir.$id.'.sx', $sx);
	return ['error'=>false,'message'=>'发布成功'];
}

function storeApiEditor($params){
	$url = storeUrl($params);
	$user = storeRequireUser($params);
	$id = isset($params['id']) ? $params['id'] : '';
	$apps = db('store/app');
	if(!$apps || !isset($apps[$id])) return storeMenu($url, 'user').'<h3 class="center">应用不存在</h3>';
	$app = $apps[$id];
	$html = storeMenu($url, 'user')
		.'<div class="title">编辑应用</div>'
		.'<div id="editor">'
		.'<input type="hidden" name="id" value="'.htmlspecialchars($app['id']).'"/>'
		.'<div class="form"><div class="key">名称</div><div class="value"><input type="text" name="name" value="'.htmlspecialchars($app['name']).'"/></div></div>'
		.'<div class="form"><div class="key">简介</div><div class="value"><input type="text" name="intro" value="'.htmlspecialchars($app['intro']).'"/></div></div>'
		.'<div class="form"><div class="key">价格</div><div class="value"><input type="number" name="price" step="0.01" value="'.$app['price'].'"/></div></div>'
		.'<div class="form"><div class="key">主页</div><div class="value"><input type="text" name="home" value="'.htmlspecialchars($app['home']).'"/></div></div>'
		.'<div class="form"><div class="key">版本</div><div class="value"><input type="text" name="version" value="'.htmlspecialchars($app['version']).'"/></div></div>'
		.'<div class="form"><div class="key">兼容版本</div><div class="value"><input type="text" name="limit" value="'.htmlspecialchars($app['limit']).'"/></div></div>'
		.'<div class="form"><div class="key">详细介绍</div><div class="value"><textarea name="content" style="height:100px;">'.htmlspecialchars($app['content'] ?? '').'</textarea></div></div>'
		.'<div class="form"><div class="key"></div><div class="value"><div class="btn" onclick="submit()">保存</div></div></div>'
		.'</div>'
		.'<script>function submit(){sx.ajax(SX.CONF.URL+\'admin/app/editor\',\'#editor\').then(res=>{sx.pop(res.message)})}</script>';
	return $html;
}

function storeApiEditorForm($params){
	$user = storeRequireUser($params);
	$id = isset($params['id']) ? $params['id'] : '';
	$apps = db('store/app');
	if(!$apps || !isset($apps[$id])) return ['error'=>true,'message'=>'应用不存在'];
	$apps[$id]['name'] = isset($params['name']) ? $params['name'] : $apps[$id]['name'];
	$apps[$id]['intro'] = isset($params['intro']) ? $params['intro'] : $apps[$id]['intro'];
	$apps[$id]['price'] = isset($params['price']) ? floatval($params['price']) : $apps[$id]['price'];
	$apps[$id]['home'] = isset($params['home']) ? $params['home'] : $apps[$id]['home'];
	$apps[$id]['version'] = isset($params['version']) ? $params['version'] : $apps[$id]['version'];
	$apps[$id]['limit'] = isset($params['limit']) ? $params['limit'] : $apps[$id]['limit'];
	$apps[$id]['content'] = isset($params['content']) ? $params['content'] : $apps[$id]['content'];
	$apps[$id]['updateTime'] = time();
	dbSave('store/app', $apps);
	return ['error'=>false,'message'=>'保存成功'];
}

function storeApiStatus($action, $params){
	$user = storeRequireUser($params);
	$type = isset($params['type']) ? $params['type'] : '';
	$id = isset($params['id']) ? $params['id'] : '';
	$apps = db('store/app');
	if(!$apps || !isset($apps[$id])) return ['error'=>true,'message'=>'应用不存在'];
	if($apps[$id]['authorId'] !== $user['id']) return ['error'=>true,'message'=>'无权操作'];
	if($action === 'put') $apps[$id]['status'] = 'published';
	elseif($action === 'pull') $apps[$id]['status'] = 'draft';
	elseif($action === 'del') unset($apps[$id]);
	dbSave('store/app', $apps);
	return ['error'=>false,'message'=>'操作成功'];
}

function storeApiUpload($params){
	$user = storeRequireUser($params);
	$id = isset($params['id']) ? $params['id'] : '';
	$img = isset($params['img']) ? $params['img'] : '';
	if(!$id || !$img) return ['error'=>true,'message'=>'参数不完整'];
	$dataDir = EXT.'store/data/'.$id.'/';
	if(!is_dir(ROOT.$dataDir)) mkdir(ROOT.$dataDir, 0777, true);
	file_put_contents(ROOT.$dataDir.'icon.png', $img);
	return ['error'=>false,'message'=>'上传成功'];
}

function storeApiDownload($params){
	$id = isset($params['id']) ? $params['id'] : '';
	if(!$id) return ['error'=>true,'message'=>'ID不能为空'];
	$apps = db('store/app');
	if(!$apps || !isset($apps[$id])) return ['error'=>true,'message'=>'应用不存在'];
	$app = $apps[$id];
	if($app['status'] !== 'published') return ['error'=>true,'message'=>'应用未上架'];
	$sxFile = ROOT.'ext/store/data/'.$id.'/'.$id.'.sx';
	if(!is_file($sxFile)) return ['error'=>true,'message'=>'应用包不存在'];
	$sxContent = file_get_contents($sxFile);
	$apps[$id]['downloadCount'] = ($apps[$id]['downloadCount'] ?? 0) + 1;
	dbSave('store/app', $apps);
	return ['error'=>false,'message'=>'操作成功','data'=>['type'=>$app['type'],'sx'=>$sxContent]];
}

function storeApiPay($sub, $params){
	$user = storeRequireUser($params);
	$id = isset($params['id']) ? $params['id'] : '';
	if(!$id) return ['error'=>true,'message'=>'ID不能为空'];
	$apps = db('store/app');
	if(!$apps || !isset($apps[$id])) return ['error'=>true,'message'=>'应用不存在'];
	$app = $apps[$id];
	if($sub === 'create'){
		$orders = db('store/order');
		if(!$orders) $orders = [];
		$orderId = 'ORD'.time().randStr(8);
		$orders[$orderId] = [
			'id' => $orderId,
			'userId' => $user['id'],
			'appId' => $id,
			'amount' => $app['price'],
			'state' => 0,
			'createTime' => time(),
		];
		dbSave('store/order', $orders);
		$payUrl = storeUrl($params).'pay/select/'.$orderId;
		return ['error'=>false,'data'=>['pay_url'=>$payUrl]];
	}
	if($sub === 'select'){
		$orders = db('store/order');
		if(!$orders) return ['error'=>true,'message'=>'订单不存在'];
		$orderId = $params['id'];
		if(!isset($orders[$orderId])) return ['error'=>true,'message'=>'订单不存在'];
		$order = $orders[$orderId];
		if($order['state'] === 0){
			$order['state'] = 1;
			$orders[$orderId] = $order;
			dbSave('store/order', $orders);
			$sales = db('store/sale');
			if(!$sales) $sales = [];
			$sales[] = [
				'id' => $sales ? max(array_column($sales, 'id')) + 1 : 1,
				'orderId' => $orderId,
				'appId' => $order['appId'],
				'sellerId' => $apps[$order['appId']]['authorId'],
				'buyerId' => $order['userId'],
				'amount' => $order['amount'],
				'appName' => $apps[$order['appId']]['name'],
				'createTime' => time(),
			];
			dbSave('store/sale', $sales);
			$users = db('store/user');
			foreach($users as &$u){
				if($u['id'] === $apps[$order['appId']]['authorId']){ $u['balance'] += $order['amount']; break; }
			}
			dbSave('store/user', $users);
		}
		return ['error'=>false,'data'=>['state'=>true,'orderId'=>$orderId]];
	}
	return ['error'=>true,'message'=>'未知支付操作'];
}

function storeApiSystem($params){
	$url = storeUrl($params);
	$apps = db('store/app');
	$users = db('store/user');
	$comments = db('store/comment');
	$html = storeMenu($url, 'user')
		.'<div class="title">系统信息</div>'
		.'<div class="tip">应用数量：'.count($apps ?: []).' · 用户数量：'.count($users ?: []).' · 评论数量：'.count($comments ?: []).'</div>';
	return $html;
}

function storeAdminIndexHtml($apps, $users, $published){
	$html = '<div class="headline">应用市场管理</div>'
		.'<div class="tip">一共<span>'.count($apps).'</span>个应用 ‧ 已上架<span>'.$published.'</span>个 · <a href="'.URL.'admin/store/import" class="red">导入应用</a></div>';
	if($apps){
		$html .= '<div class="title">应用列表</div><ul class="ext">';
		foreach($apps as $item){
			$liClass = $item['status'] === 'published' ? 'ext-uninstall' : 'ext-install';
			$priceHtml = $item['price'] > 0 ? '<span class="tpl-price">￥'.$item['price'].'</span>' : '<span class="green">免费</span>';
			$statusHtml = $item['status'] === 'published' ? '<span class="green">已上架</span>' : '<span style="color:#999;">未上架</span>';
			$toggleLabel = $item['status'] === 'published' ? '下架' : '上架';
			$html .= '<li class="'.$liClass.'">'
				.'<div class="ext-icon"><img src="'.$item['icon'].'"/></div>'
				.'<div class="ext-info"><div class="ext-name">'
				.htmlspecialchars($item['name']).'<span class="ext-version">v'.htmlspecialchars($item['version']).'</span>'
				.$priceHtml.$statusHtml
				.'<a href="'.URL.'admin/store/app/toggle/'.$item['id'].'" class="ext-btn">'.$toggleLabel.'</a>'
				.'<a href="'.URL.'admin/store/app/delete/'.$item['id'].'" class="ext-btn bg-red" onclick="return SX.confirm(this,\'确定删除？\')">删除</a>'
				.'</div>'
				.'<div class="ext-intro">'.htmlspecialchars($item['intro']).' ‧ '.$item['type'].' ‧ 下载'.($item['downloadCount'] ?? 0).'次 ‧ 作者：'.htmlspecialchars($item['author']).'</div>'
				.'</div></li>';
		}
		$html .= '</ul>';
	}
	$html .= '<div class="title">用户管理</div>'
		.'<div class="tip">一共<span>'.count($users).'</span>个用户 · <a href="'.URL.'admin/store/user">管理用户</a></div>';
	return $html;
}

function storeAdminUserHtml($users){
	$html = '<div class="headline">用户管理</div>'
		.'<div class="tip">一共<span>'.count($users).'</span>个用户 · <a href="'.URL.'admin/store">返回应用管理</a></div>';
	if($users){
		$html .= '<ul class="ext">';
		foreach($users as $item){
			$devHtml = !empty($item['isDeveloper']) ? '<span class="green">开发者</span>' : '';
			$toggleLabel = !empty($item['isDeveloper']) ? '取消开发者' : '设为开发者';
			$html .= '<li class="ext-uninstall"><div class="ext-info"><div class="ext-name">'
				.htmlspecialchars($item['username']).'<span class="ext-version">ID:'.$item['id'].'</span>'
				.$devHtml
				.'<a href="'.URL.'admin/store/user/toggleDev/'.$item['id'].'" class="ext-btn">'.$toggleLabel.'</a>'
				.'<a href="'.URL.'admin/store/user/delete/'.$item['id'].'" class="ext-btn bg-red" onclick="return SX.confirm(this,\'确定删除？\')">删除</a>'
				.'</div>'
				.'<div class="ext-intro">余额：￥'.number_format($item['balance'],2).' ‧ 注册：'.humanDate($item['createTime']).' ‧ 邮箱：'.htmlspecialchars($item['mail'] ?? '').'</div>'
				.'</div></li>';
		}
		$html .= '</ul>';
	}
	return $html;
}

function storeAdminImportHtml(){
	$html = '<div class="headline">导入应用</div>'
		.'<div class="tip">上传.sx格式的应用包文件进行导入 · <a href="'.URL.'admin/store">返回应用管理</a></div>'
		.'<form enctype="multipart/form-data" method="POST" action="'.URL.'admin/store/app/upload">'
		.'<div class="form"><div class="key">应用包</div><div class="value"><input type="file" name="file" accept=".sx"/></div></div>'
		.'<div class="form"><div class="key"></div><div class="value"><div class="btn" onclick="this.closest(\'form\').submit()">上传导入</div></div></div>'
		.'</form>';
	return $html;
}
