<?php exit('404');?>
{include header}
<div class="headline">应用市场管理</div>
<div class="tip">一共<span>{#count($apps)}</span>个应用 ‧ 已上架<span>{#$published}</span>个 · <a href="{url admin/store/import}" class="red">导入应用</a></div>
{if $apps}
<div class="title">应用列表</div>
<ul class="ext">
{foreach $apps}
	<li class="{if $item.status==='published'}ext-uninstall{else}ext-install{/if}">
		<div class="ext-icon"><img src="{$item.icon}"/></div>
		<div class="ext-info">
			<div class="ext-name">
				{$item.name}<span class="ext-version">v{$item.version}</span>
				<span class="{if $item.price>0}tpl-price{else}green{/if}">{if $item.price>0}￥{$item.price}{else}免费{/if}</span>
				{if $item.status==='published'}<span class="green">已上架</span>{else}<span style="color:#999;">未上架</span>{/if}
				<a href="{url admin/store/app/toggle/$item.id}" class="ext-btn">{if $item.status==='published'}下架{else}上架{/if}</a>
				<a href="{url admin/store/app/delete/$item.id}" class="ext-btn bg-red" onclick="return SX.confirm(this,'确定删除？')">删除</a>
			</div>
			<div class="ext-intro">{$item.intro} ‧ {$item.type} ‧ 下载{$item.downloadCount}次 ‧ 作者：{$item.author}</div>
		</div>
	</li>
{/foreach}
</ul>
{/if}
<div class="title">用户管理</div>
<div class="tip">一共<span>{#count($users)}</span>个用户 · <a href="{url admin/store/user}">管理用户</a></div>
{include footer}
