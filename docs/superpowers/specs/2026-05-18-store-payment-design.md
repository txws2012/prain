# Store 支付模块设计方案

## 背景

当前 store 插件的支付功能是模拟的（直接确认订单），无法支持真实的在线收款。需要接入聚合支付平台实现完整的支付流程。

## 设计原则

- 所有代码在 `ext/store/` 目录内，不修改系统核心代码
- 插件化架构，可插拔、可扩展
- 先以 VNative 为例，其他聚合支付平台可按相同接口扩展

## 目录结构

```
ext/store/
├── common.php           # 现有 API 逻辑
├── main.php             # 现有路由
├── pay/                 # 新建支付子目录
│   ├── interface.php     # 支付接口抽象（定义统一接口）
│   ├── vnative.php      # VNative 聚合支付实现
│   ├── callback.php      # 支付回调处理（异步通知）
│   └── config.php        # 支付配置（平台选择、API密钥等）
└── data/pay/            # 支付回调日志目录
```

## 核心接口设计

### 1. 支付接口抽象（interface.php）

定义统一的支付接口，所有支付平台实现此接口：

```php
interface StorePayInterface {
    // 获取支付二维码/链接
    public function createOrder($orderData);

    // 验证回调签名
    public function verifyCallback($data);

    // 解析回调数据
    public function parseCallback($data);
}
```

### 2. 支付配置（config.php）

```php
<?php
return [
    'driver' => 'vnative',           // 当前使用的支付驱动
    'drivers' => [
        'vnative' => [
            'enabled' => true,
            'app_id' => '',          // VNative AppID
            'app_key' => '',         // VNative AppKey
            'notify_url' => '',      // 回调地址（自动生成）
        ],
    ],
];
```

### 3. 支付回调处理（callback.php）

处理聚合支付的异步通知：
- 验证回调签名
- 更新订单状态
- 生成销售记录
- 记录回调日志

## 支付流程

### 完整支付时序

```
用户点击购买
    ↓
storeApiPay('create') → 创建本地订单 → 返回支付页面（含二维码）
    ↓
用户扫码支付
    ↓
聚合支付平台处理 → 异步回调通知 store 服务器
    ↓
callback.php → 验证签名 → 更新订单 state=1 → 生成销售记录
    ↓
用户支付成功页面轮询 → storeApiPay('check') → 返回支付状态
```

### 关键 API 端点变更

| 端点 | 变更 |
|------|------|
| `pay/create` | 返回支付二维码/链接，而非直接跳转 |
| `pay/check` | 新增轮询接口，检查订单支付状态 |
| `callback.php` | 新增支付回调处理路由 |

## 实现步骤

### Step 1: 创建支付配置和接口抽象
- `ext/store/pay/config.php`
- `ext/store/pay/interface.php`

### Step 2: 实现 VNative 支付驱动
- `ext/store/pay/vnative.php`

### Step 3: 修改 storeApiPay 函数
- 支持创建支付订单
- 新增轮询检查接口
- 连接支付驱动

### Step 4: 实现回调处理
- `ext/store/pay/callback.php`
- 路由：`/api/pay/callback`
- 更新订单、销售记录逻辑

### Step 5: 订单管理后台
- 管理后台显示订单列表
- 查看订单状态
- 手动处理异常订单

## 数据变更

### 新增数据表/字段

**订单表 `db/store/order.php`**（现有字段，已满足）：
```php
[
    'id' => 'ORDxxx',      // 订单号
    'userId' => 1,         // 购买用户
    'appId' => 'xxx',      // 应用ID
    'amount' => 19.9,      // 金额
    'state' => 0,          // 0=待支付, 1=已支付
    'payType' => 'vnative', // 支付渠道
    'tradeNo' => '',       // 第三方交易号
    'createTime' => time(),
]
```

### 销售记录（`db/store/sale.php`，现有字段已满足）

## 安全考虑

1. **回调验证**：必须验证回调签名，防止伪造通知
2. **订单幂等**：回调处理需要检查订单状态，防止重复发货
3. **金额校验**：回调中校验实际支付金额与订单金额一致
4. **日志记录**：所有回调写入日志文件，便于排查问题

## 后续扩展

- 添加更多支付驱动（Stripe、PayPal 等）
- 支持订阅制收费
- 添加退款功能
