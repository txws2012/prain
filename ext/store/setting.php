<?php exit('404');?>
{include header}
<div class="headline">应用市场设置</div>
<form method="POST" style="max-width:500px;margin:20px auto;">
<div style="margin-bottom:12px;">
<label>通信密钥</label>
<input type="text" name="key" class="input" value="{$storeKey.key}" placeholder="用于API认证（可选）"/>
</div>
<button type="submit" class="btn">保存</button>
</form>
<div class="tip">客户端使用：修改 app/main.php 中的 <code>https://prain.cn/api/</code> 为 <code><?=rtrim(URL,'/')?>/api/</code>，然后打包 app 扩展分发给客户。</div>
{include footer}
