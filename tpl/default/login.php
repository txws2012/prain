<?php exit('404');?>
{include header}
<form action="{url admin/login}" method="post" class="box login">
	<div class="headline">登录 · 请输入密码</div>
	<input type="password" name="password" placeholder="密码" />
	<input type="submit" class="btn" value="提交"/>
</form>
{include footer}