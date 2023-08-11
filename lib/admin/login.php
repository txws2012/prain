<?php exit('404');?>
{include header}
<!-- hook.admin_login_header -->
<form action="{url admin/login}" method="post">
	<div class="intro">登录 · 请输入密码</div>
	<input type="password" name="password" placeholder="密码" />
	<!-- hook.admin_login_form -->
	<input type="submit" class="btn" value="提交"/>
	<!-- hook.admin_login_form_bottom -->
</form>
<!-- hook.admin_login_footer -->
{include footer}