<?php exit('404');?>
{include header}
<div class="headline">用户管理</div>
<div class="tip">一共<span>{#count($users)}</span>个用户 · <a href="{url admin/store}">返回应用管理</a></div>
{if $users}
<ul class="ext">
{foreach $users}
	<li class="ext-uninstall">
		<div class="ext-info">
			<div class="ext-name">
				{$item.username}<span class="ext-version">ID:{$item.id}</span>
				{if $item.isDeveloper}<span class="green">开发者</span>{/if}
				<a href="{url admin/store/user/toggleDev/$item.id}" class="ext-btn">{if $item.isDeveloper}取消开发者{else}设为开发者{/if}</a>
				<a href="{url admin/store/user/delete/$item.id}" class="ext-btn bg-red" onclick="return SX.confirm(this,'确定删除？')">删除</a>
			</div>
			<div class="ext-intro">余额：￥{$item.balance} ‧ 注册：{#humanDate($item.createTime)} ‧ 邮箱：{$item.mail}</div>
		</div>
	</li>
{/foreach}
</ul>
{/if}
{include footer}
