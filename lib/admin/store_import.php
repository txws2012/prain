<?php exit('404');?>
{include header}
<div class="headline">导入应用</div>
<div class="tip">上传.sx格式的应用包文件进行导入 · <a href="{url admin/store}">返回应用管理</a></div>
<form enctype="multipart/form-data" method="POST" action="{url admin/store/app/upload}">
	<div class="form">
		<div class="key">应用包</div>
		<div class="value"><input type="file" name="file" accept=".sx"/></div>
	</div>
	<div class="form">
		<div class="key"></div>
		<div class="value"><div class="btn" onclick="this.closest('form').submit()">上传导入</div></div>
	</div>
</form>
{include footer}
