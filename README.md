# kode/express-api

通用快递API集成包，支持多种快递公司接口，提供统一的调用方式，便于快速集成到各种PHP项目中。

## 功能特性

- 支持多种快递公司API，覆盖「国内快递 + 国内货运（零担/整车/快运）+ 国际物流（跨境 / 货运）」完整物流链：
  - 国内快递：EMS、顺丰SF、韵达、中通、申通、菜鸟网络、京东快递/京东物流
  - 国内货运：德邦物流、安能物流、天地华宇（零担 / 整车 / 快运，支持网点查询、运费报价）
  - 国际物流：4PX递四方、顺丰国际、DHL国际、云途物流、EMS国际、燕文物流
    （支持海运 / 空运下单、海关申报、清关查询、运费报价）
  - 聚合查询：快递100、快递鸟、聚合数据（运单轨迹 + 运单号自动识别，作为承运商自动识别的权威回退）
- 当前版本：v2.3.0
- **物流链自动关联（v2.3.0）**：仅凭运单号即可 `recognize()` 自动识别承运商；给定发货意图（起止国 / 重量 / 运输方式）即可 `buildChain()` 自动拼装「揽收 → 干线 → 跨境 → 清关 → 末端」全链路，无需逐段指定承运商
- 统一的接口调用方式，简化开发流程
- 灵活的面单布局管理，支持可视化编辑
- 完善的错误处理和响应标准化（按快递商注册响应策略）
- 传输层韧性增强：指数退避重试（瞬时故障自动重试，4xx 不重试）、连接超时、最近请求诊断
- 跨快递商聚合能力：`batchQueryTracking()` 批量轨迹（单条失败隔离）
- 支持PSR-12代码规范
- 支持PHP 8.3+
- 支持多语言字段配置
- 丰富的测试用例，测试覆盖率高

## 安装

使用Composer安装：

```bash
composer require kode/express-api
```

## 配置说明

### 获取API密钥

要使用各快递公司API，您需要在相应开放平台注册并获取API密钥：

1. **EMS**：
   - 访问[EMS开放平台](https://api.ems.com.cn/)
   - 注册开发者账号并完成企业认证
   - 在API控制台选择需要的服务接口
   - 获取API密钥（AppKey和AppSecret）

2. **顺丰SF**：
   - 访问顺丰开放平台
   - 注册开发者账号并申请API权限
   - 获取API密钥

3. **韵达**：
   - 访问韵达开放平台
   - 注册开发者账号并完成认证
   - 获取API密钥

4. **中通**：
   - 访问中通开放平台
   - 注册开发者账号并完成认证
   - 获取API密钥

5. **申通**：
   - 访问申通开放平台
   - 注册开发者账号并完成认证
   - 获取API密钥

6. **菜鸟网络**：
   - 访问菜鸟开放平台
   - 注册开发者账号并完成认证
   - 获取AppKey、AppSecret和PartnerId

### 环境配置

#### 生产环境

在生产环境中使用真实的EMS API端点：

```php
$config = new \Kode\ExpressApi\EMS\Config([
    'app_key' => 'your_production_app_key',
    'app_secret' => 'your_production_app_secret',
    'sandbox' => false, // 默认值，可省略
]);
```

#### 沙箱环境

在开发和测试阶段，建议使用沙箱环境：

```php
$config = new \Kode\ExpressApi\EMS\Config([
    'app_key' => 'your_sandbox_app_key',
    'app_secret' => 'your_sandbox_app_secret',
    'sandbox' => true,
]);

// 韵达配置示例
$yundaConfig = new \Kode\ExpressApi\Yunda\Config([
    'app_key' => 'your_yunda_app_key',
    'app_secret' => 'your_yunda_app_secret',
    'sandbox' => true,
]);

// 中通配置示例
$ztoConfig = new \Kode\ExpressApi\ZTO\Config([
    'app_key' => 'your_zto_app_key',
    'app_secret' => 'your_zto_app_secret',
    'sandbox' => true,
]);

// 申通配置示例
$stoConfig = new \Kode\ExpressApi\STO\Config([
    'app_key' => 'your_sto_app_key',
    'app_secret' => 'your_sto_app_secret',
    'sandbox' => true,
]);

// 菜鸟网络配置示例
$cainiaoConfig = new \Kode\ExpressApi\Cainiao\Config([
    'app_key' => 'your_cainiao_app_key',
    'app_secret' => 'your_cainiao_app_secret',
    'partner_id' => 'your_cainiao_partner_id',
    'sandbox' => true,
]);
```

### 配置参数详解

#### app_key

- **类型**: string
- **必填**: 是
- **说明**: 应用Key，从各快递公司开放平台获取

#### app_secret

- **类型**: string
- **必填**: 是
- **说明**: 应用密钥，从各快递公司开放平台获取

#### partner_id

- **类型**: string
- **必填**: 否（仅菜鸟网络必需）
- **说明**: 合作伙伴ID，从菜鸟开放平台获取

#### sandbox

- **类型**: boolean
- **默认值**: false
- **说明**: 是否使用沙箱环境
  - `true`: 使用沙箱环境，用于开发测试
  - `false`: 使用生产环境，用于正式业务

#### timeout

- **类型**: integer
- **默认值**: 30
- **说明**: HTTP请求超时时间（秒）

#### version

- **类型**: string
- **默认值**: 'v1'
- **说明**: API版本号

### 认证机制

EMS API使用OAuth 2.0客户端凭证模式进行认证：

1. 使用`app_key`和`app_secret`获取访问令牌
2. 在后续API请求中使用该令牌进行认证

```php
// 认证过程由SDK自动处理
$client = new \Kode\ExpressApi\EMS\Client($config);

// 首次API调用时会自动获取并缓存访问令牌
$result = $client->queryOrder('123456');
```

### 网络要求

#### 生产环境端点

- **EMS**: `https://api.ems.com.cn` (端口: 443)
- **顺丰SF**: `https://sfapi.sf-express.com` (端口: 443)
- **韵达**: `https://api-yunda.kdniao.com` (端口: 443)
- **中通**: `https://api-zto.kdniao.com` (端口: 443)
- **申通**: `https://api-sto.kdniao.com` (端口: 443)
- **菜鸟网络**: `https://api-cainiao.openapi.alibaba.com` (端口: 443)

#### 沙箱环境端点

- **EMS**: `https://api-sandbox.ems.com.cn` (端口: 443)
- **顺丰SF**: `https://sfapi-sandbox.sf-express.com` (端口: 443)
- **韵达**: `https://api-sandbox-yunda.kdniao.com` (端口: 443)
- **中通**: `https://api-sandbox-zto.kdniao.com` (端口: 443)
- **申通**: `https://api-sandbox-sto.kdniao.com` (端口: 443)
- **菜鸟网络**: `https://api-sandbox-cainiao.openapi.alibaba.com` (端口: 443)

确保您的服务器能够访问这些地址。

## 快速开始

### 初始化API客户端

```php
use Kode\ExpressApi\ExpressApiClient;

// 创建EMS配置数组
$emsConfig = [
    'app_key' => 'YOUR_EMS_APP_KEY',
    'app_secret' => 'YOUR_EMS_APP_SECRET',
    'sandbox' => true, // 使用沙箱环境
];

// 创建顺丰配置数组
$sfConfig = [
    'app_key' => 'YOUR_SF_APP_KEY',
    'app_secret' => 'YOUR_SF_APP_SECRET',
    'sandbox' => true,
];

// 创建韵达配置数组
$yundaConfig = [
    'app_key' => 'YOUR_YUNDA_APP_KEY',
    'app_secret' => 'YOUR_YUNDA_APP_SECRET',
    'sandbox' => true,
];

// 创建中通配置数组
$ztoConfig = [
    'app_key' => 'YOUR_ZTO_APP_KEY',
    'app_secret' => 'YOUR_ZTO_APP_SECRET',
    'sandbox' => true,
];

// 创建申通配置数组
$stoConfig = [
    'app_key' => 'YOUR_STO_APP_KEY',
    'app_secret' => 'YOUR_STO_APP_SECRET',
    'sandbox' => true,
];

// 创建菜鸟网络配置数组
$cainiaoConfig = [
    'app_key' => 'YOUR_CAINIAO_APP_KEY',
    'app_secret' => 'YOUR_CAINIAO_APP_SECRET',
    'partner_id' => 'YOUR_CAINIAO_PARTNER_ID',
    'sandbox' => true,
];

// 方式1：使用工厂方法创建客户端
$emsClient = ExpressApiClient::create('ems', $emsConfig);
$sfClient = ExpressApiClient::create('sf', $sfConfig);
$yundaClient = ExpressApiClient::create('yunda', $yundaConfig);
$ztoClient = ExpressApiClient::create('zto', $ztoConfig);
$stoClient = ExpressApiClient::create('sto', $stoConfig);
$cainiaoClient = ExpressApiClient::create('cainiao', $cainiaoConfig);

// 方式2：直接创建客户端
$emsClient = new \Kode\ExpressApi\EMS\Client($emsConfig);
$sfClient = new \Kode\ExpressApi\SF\Client($sfConfig);
$yundaClient = new \Kode\ExpressApi\Yunda\Client($yundaConfig);
$ztoClient = new \Kode\ExpressApi\Zto\Client($ztoConfig);
$stoClient = new \Kode\ExpressApi\Sto\Client($stoConfig);
$cainiaoClient = new \Kode\ExpressApi\Cainiao\Client($cainiaoConfig);

// 检查快递公司是否支持
if (ExpressApiClient::isCourierSupported('ems')) {
    echo "EMS快递支持";
}

// 获取支持的快递公司列表
$supportedCouriers = ExpressApiClient::getSupportedCouriers();
print_r($supportedCouriers);
```

## API方法

### 1. 发货通知

```php
// 发货数据
$shipmentData = [
    'order_no' => 'ORD' . date('YmdHis'),
    'sender' => [
        'name' => '张三',
        'phone' => '13800138000',
        'province' => '广东省',
        'city' => '深圳市',
        'district' => '南山区',
        'address' => '科技园南区8栋',
    ],
    'recipient' => [
        'name' => '李四',
        'phone' => '13900139000',
        'province' => '北京市',
        'city' => '北京市',
        'district' => '朝阳区',
        'address' => '建国路88号',
    ],
    'items' => [
        [
            'name' => '商品1',
            'quantity' => 1,
            'weight' => 0.5,
        ],
    ],
    'weight' => 0.5,
    'express_type' => 1,
];

// EMS发货通知
$response = $emsClient->sendShipment($shipmentData);

// 顺丰发货通知
$response = $sfClient->sendShipment($shipmentData);

// 韵达发货通知
$response = $yundaClient->sendShipment($shipmentData);

// 中通发货通知
$response = $ztoClient->sendShipment($shipmentData);

// 申通发货通知
$response = $stoClient->sendShipment($shipmentData);

// 菜鸟网络发货通知
$response = $cainiaoClient->sendShipment($shipmentData);
```

### 2. 批量发货

```php
// 批量发货数据
$batchShipmentData = [
    [
        'order_no' => 'ORD001',
        // 其他发货数据...
    ],
    [
        'order_no' => 'ORD002',
        // 其他发货数据...
    ],
];

// EMS批量发货
$response = $emsClient->batchSendShipment($batchShipmentData);

// 顺丰批量发货
$response = $sfClient->batchSendShipment($batchShipmentData);

// 韵达批量发货
$response = $yundaClient->batchSendShipment($batchShipmentData);

// 中通批量发货
$response = $ztoClient->batchSendShipment($batchShipmentData);

// 申通批量发货
$response = $stoClient->batchSendShipment($batchShipmentData);

// 菜鸟网络批量发货
$response = $cainiaoClient->batchSendShipment($batchShipmentData);
```

### 3. 取件通知

```php
// 取件数据
$pickupData = [
    'order_no' => 'ORD001',
    'pickup_time' => date('Y-m-d H:i:s', strtotime('+1 hour')),
    'sender' => [
        'name' => '张三',
        'phone' => '13800138000',
        'address' => '广东省深圳市南山区科技园',
    ],
];

// EMS取件通知
$response = $emsClient->pickupNotice($pickupData);

// 顺丰取件通知
$response = $sfClient->pickupNotice($pickupData);

// 韵达取件通知
$response = $yundaClient->pickupNotice($pickupData);

// 中通取件通知
$response = $ztoClient->pickupNotice($pickupData);

// 申通取件通知
$response = $stoClient->pickupNotice($pickupData);

// 菜鸟网络取件通知
$response = $cainiaoClient->pickupNotice($pickupData);
```

### 4. 订单查询

```php
// 查询订单（参数为订单ID字符串）
$orderId = 'ORD001';

// EMS订单查询
$response = $emsClient->queryOrder($orderId);

// 顺丰订单查询
$response = $sfClient->queryOrder($orderId);

// 韵达订单查询
$response = $yundaClient->queryOrder($orderId);

// 中通订单查询
$response = $ztoClient->queryOrder($orderId);

// 申通订单查询
$response = $stoClient->queryOrder($orderId);

// 菜鸟网络订单查询
$response = $cainiaoClient->queryOrder($orderId);
```

### 5. 批量查询订单

```php
// 批量查询订单（参数为订单ID字符串数组）
$orderIds = ['ORD001', 'ORD002'];

// EMS批量查询订单
$response = $emsClient->batchQueryOrders($orderIds);

// 顺丰批量查询订单
$response = $sfClient->batchQueryOrders($orderIds);

// 韵达批量查询订单
$response = $yundaClient->batchQueryOrders($orderIds);

// 中通批量查询订单
$response = $ztoClient->batchQueryOrders($orderIds);

// 申通批量查询订单
$response = $stoClient->batchQueryOrders($orderIds);

// 菜鸟网络批量查询订单
$response = $cainiaoClient->batchQueryOrders($orderIds);
```

### 6. 取消订单

```php
// 取消订单（参数：订单ID，可选取消原因）
$orderId = 'ORD001';
$reason = '客户取消';

// EMS取消订单
$response = $emsClient->cancelOrder($orderId, $reason);

// 顺丰取消订单
$response = $sfClient->cancelOrder($orderId, $reason);

// 韵达取消订单
$response = $yundaClient->cancelOrder($orderId, $reason);

// 中通取消订单
$response = $ztoClient->cancelOrder($orderId, $reason);

// 申通取消订单
$response = $stoClient->cancelOrder($orderId, $reason);

// 菜鸟网络取消订单
$response = $cainiaoClient->cancelOrder($orderId, $reason);
```

### 7. 轨迹查询

```php
// 查询轨迹（参数：运单号，可选语言）
$trackingNo = 'SF1234567890';

// EMS轨迹查询
$response = $emsClient->queryTracking($trackingNo);

// 顺丰轨迹查询
$response = $sfClient->queryTracking($trackingNo);

// 韵达轨迹查询
$response = $yundaClient->queryTracking($trackingNo);

// 中通轨迹查询
$response = $ztoClient->queryTracking($trackingNo);

// 申通轨迹查询
$response = $stoClient->queryTracking($trackingNo);

// 菜鸟网络轨迹查询
$response = $cainiaoClient->queryTracking($trackingNo);
```

### 8. 批量轨迹查询

```php
// 批量查询轨迹（参数为运单号字符串数组）
$trackingNumbers = ['SF1234567890', 'SF0987654321'];

// EMS批量轨迹查询
$response = $emsClient->batchQueryTracking($trackingNumbers);

// 顺丰批量轨迹查询
$response = $sfClient->batchQueryTracking($trackingNumbers);

// 韵达批量轨迹查询
$response = $yundaClient->batchQueryTracking($trackingNumbers);

// 中通批量轨迹查询
$response = $ztoClient->batchQueryTracking($trackingNumbers);

// 申通批量轨迹查询
$response = $stoClient->batchQueryTracking($trackingNumbers);

// 菜鸟网络批量轨迹查询
$response = $cainiaoClient->batchQueryTracking($trackingNumbers);
```

### 9. 拦截件

```php
// 拦截件（参数：订单ID + 可选拦截数据数组）
$orderId = 'ORD001';
$interceptData = ['reason' => '发错地址'];

// EMS拦截件
$response = $emsClient->intercept($orderId, $interceptData);

// 顺丰拦截件
$response = $sfClient->intercept($orderId, $interceptData);

// 韵达拦截件
$response = $yundaClient->intercept($orderId, $interceptData);

// 中通拦截件
$response = $ztoClient->intercept($orderId, $interceptData);

// 申通拦截件
$response = $stoClient->intercept($orderId, $interceptData);

// 菜鸟网络拦截件
$response = $cainiaoClient->intercept($orderId, $interceptData);
```

### 10. 改件信息

```php
// 改件信息（参数：订单ID + 修改数据数组）
$orderId = 'ORD001';
$modifyData = [
    'new_address' => [
        'name' => '王五',
        'phone' => '13700137000',
        'province' => '上海市',
        'city' => '上海市',
        'district' => '浦东新区',
        'address' => '陆家嘴金融中心',
    ],
];

// EMS改件信息
$response = $emsClient->modify($orderId, $modifyData);

// 顺丰改件信息
$response = $sfClient->modify($orderId, $modifyData);

// 韵达改件信息
$response = $yundaClient->modify($orderId, $modifyData);

// 中通改件信息
$response = $ztoClient->modify($orderId, $modifyData);

// 申通改件信息
$response = $stoClient->modify($orderId, $modifyData);

// 菜鸟网络改件信息
$response = $cainiaoClient->modify($orderId, $modifyData);
```

### 11. 面单打印

```php
// 面单打印（参数：订单ID + 可选面单配置数组）
$orderId = 'ORD001';
$printData = [
    'template_config' => [
        // 面单配置...
    ],
];

// EMS面单打印
$response = $emsClient->printLabel($orderId, $printData);

// 顺丰面单打印
$response = $sfClient->printLabel($orderId, $printData);

// 韵达面单打印
$response = $yundaClient->printLabel($orderId, $printData);

// 中通面单打印
$response = $ztoClient->printLabel($orderId, $printData);

// 申通面单打印
$response = $stoClient->printLabel($orderId, $printData);

// 菜鸟网络面单打印
$response = $cainiaoClient->printLabel($orderId, $printData);
```

### 12. 批量面单打印

```php
// 批量面单打印（参数：订单ID字符串数组 + 可选打印配置）
$orderIds = ['ORD001', 'ORD002'];
$printData = [
    'template_config' => [
        // 面单配置...
    ],
];

// EMS批量面单打印
$response = $emsClient->batchPrintLabels($orderIds, $printData);

// 顺丰批量面单打印
$response = $sfClient->batchPrintLabels($orderIds, $printData);

// 韵达批量面单打印
$response = $yundaClient->batchPrintLabels($orderIds, $printData);

// 中通批量面单打印
$response = $ztoClient->batchPrintLabels($orderIds, $printData);

// 申通批量面单打印
$response = $stoClient->batchPrintLabels($orderIds, $printData);

// 菜鸟网络批量面单打印
$response = $cainiaoClient->batchPrintLabels($orderIds, $printData);
```

## 国际物流（跨境 / 货运）快速开始

国际物流客户端与国内快递共用同一套工厂入口，差异仅在配置字段与「运输方式 / 海关申报」等国际要素。

```php
use Kode\ExpressApi\ExpressApiClient;

// 顺丰国际配置（HMAC-SHA256 签名鉴权）
$sfIntlConfig = [
    'app_key'       => 'YOUR_SF_INTL_APP_KEY',
    'app_secret'    => 'YOUR_SF_INTL_APP_SECRET',
    'customer_code' => 'YOUR_CUSTOMER_CODE',
    'sandbox'       => true,
];

$client = ExpressApiClient::create('sf_international', $sfIntlConfig);

// 空运下单（自动注入 mode = air）
$response = $client->createAirFreight([
    'order_no'            => 'INTL' . date('YmdHis'),
    'destination_country' => 'US',
    'sender'   => ['name' => '张三', 'phone' => '13800138000', 'address' => '深圳市南山区'],
    'recipient' => ['name' => 'John', 'phone' => '1234567890', 'address' => '1st Ave, New York'],
    'items'    => [['name' => '样品', 'quantity' => 1, 'weight' => 0.5]],
    'hs_code'        => '123456',
    'product_name'   => 'Sample',
    'declared_value' => 10,
    'currency'       => 'USD',
    'origin_country' => 'CN',
]);

// 运费报价
$quote = $client->getQuotation([
    'mode'        => 'air',
    'origin'      => 'CN',
    'destination' => 'US',
    'weight'      => 1.2,
]);

// 海关申报
$customs = $client->declareCustoms([
    'hs_code'        => '123456',
    'product_name'   => 'Sample',
    'declared_value' => 10,
    'currency'       => 'USD',
    'origin_country' => 'CN',
]);
```

### 国内货运（零担 / 整车 / 快运）快速开始

国内货运客户端与国内快递 / 国际物流共用同一套工厂入口，差异仅在配置字段与「服务类型（零担 / 整车 / 快运）」等货运要素。

```php
use Kode\ExpressApi\ExpressApiClient;

// 德邦物流配置（MD5 签名鉴权）
$debangConfig = [
    'app_key'    => 'YOUR_DEBANG_APP_KEY',
    'app_secret' => 'YOUR_DEBANG_APP_SECRET',
    'sandbox'    => true,
];

$client = ExpressApiClient::create('debang', $debangConfig);

// 零担下单（自动注入 service_type = ltl）
$response = $client->createLtl([
    'order_no'    => 'LTL' . date('YmdHis'),
    'sender'      => ['name' => '张三', 'phone' => '13800138000', 'address' => '上海市浦东新区XX路1号'],
    'receiver'    => ['name' => '李四', 'phone' => '13900139000', 'address' => '北京市朝阳区XX街2号'],
    'goods'       => [['name' => '机械设备', 'weight' => 500]],
    'origin'      => '上海',
    'destination' => '北京',
]);

// 整车下单（自动注入 service_type = ftl）
$response = $client->createFtl([ /* 同上结构 */ ]);

// 运费报价
$quote = $client->getQuotation([
    'service_type' => 'ltl',
    'origin'      => '上海',
    'destination' => '北京',
    'weight'      => 500,
]);

// 网点查询
$network = $client->queryNetwork([
    'city'    => '上海',
    'keyword' => '浦东',
]);
```

### 物流链自动关联（v2.3.0，核心能力）

你**不用自己去指定物流链的物流**：给出运单号，SDK 自动识别归属承运商；
给出发货意图（起止国家 / 重量 / 运输方式），SDK 自动挑选每个环节的承运商并拼装整条链路。

**1) 运单号自动识别承运商** —— `ExpressApiClient::recognize()`

```php
use Kode\ExpressApi\ExpressApiClient;

// 仅凭运单号推断承运商（无需手动指定）
$courier = ExpressApiClient::recognize('SF1234567890123'); // => 'sf'
$courier = ExpressApiClient::recognize('JD0091234567890'); // => 'jd'
```

底层由 `CourierRecognizer` 完成，内置各服务商运单号特征规则（前缀 / 长度 / 字符集）；
规则未命中且已配置聚合解析器时，自动回退到快递100 / 快递鸟 / 聚合数据等权威解析：

```php
use Kode\ExpressApi\Common\CourierRecognizer;

// 注册 / 覆盖规则
CourierRecognizer::registerPattern('my_courier', '/^MY\d{6}$/i');
// 设置聚合解析器（权威回退）
CourierRecognizer::setResolver(function (string $no) {
    // 调用快递100 等聚合计费的识别接口，返回承运商代码或 null
    return $someAggregator->recognize($no);
});
```

**2) 按发货意图自动拼装物流链** —— `ExpressApiClient::buildChain()`

```php
$chain = ExpressApiClient::buildChain(
    ['origin' => 'CN', 'dest' => 'US', 'weight' => 5, 'mode' => 'air'],
    [
        'sf' => $sfConfig, 'debang' => $debangConfig,
        'dhl' => $dhlConfig, 'ems_international' => $emsIntlConfig, 'jd' => $jdConfig,
    ]
    // 可选：环节级覆盖，如 ['crossborder' => 'fourpx']
);

$legs = $chain->toArray()['legs'];
// => [揽收(sf), 干线(debang), 跨境(dhl), 清关(ems_international), 末端(jd)]
```

不传 `prefer` 时，编排器按「已签约配置」自动挑选每个环节的最优承运商；
某环节未签约则自动回退同类目其他承运商，仍缺失则标记 `unavailable` 而不中断整链。

**3) 按运单号自动识别并推断完整链路** —— `ExpressApiClient::chainFromTracking()`

```php
$chain = ExpressApiClient::chainFromTracking('SF1234567890123', ['sf' => $sfConfig]);
$info = $chain->toArray();
// $info['detected_courier'] => 'sf'            // 自动识别的承运商
// $info['suggested_chain']  => [...]          // 推断的完整 5 环节链路模板
```

> 三类入口均不触网：`recognize()` 与 `buildChain()/chainFromTracking()` 仅做本地识别与编排，
> 真正查询需调用 `$chain->track($trackingNo)`（单段失败相互隔离）。

### 自动发现能力菜单（物流链总览）

`getApiMenu()` 会基于各客户端实际方法，按 `order / query / label / freight / customs` 分类自动生成能力目录，
无需手工维护，便于前端动态渲染菜单或生成文档：

```php
$menu = ExpressApiClient::getApiMenu();
// $menu['couriers']['fourpx']['operations']['freight'] => ['createSeaFreight','createAirFreight','getQuotation']
// $menu['couriers']['fourpx']['operations']['customs'] => ['declareCustoms','queryCustoms']
```

## 韧性与可观测性（v2.2.0）

### 失败重试（指数退避）

传输层在遇到**瞬时故障**（连接错误、超时、HTTP 5xx）时会按指数退避自动重试；
**客户端错误（HTTP 4xx）视为终态，不重试**。默认关闭重试，可在进程启动时全局开启：

```php
use Kode\ExpressApi\Common\HttpClient;

// 额外重试 2 次，基础延迟 200ms（实际延迟 200ms / 400ms）
HttpClient::setRetry(2, 200);

// 关闭重试（默认）
HttpClient::setRetry(0);
```

> 重试为全局静态配置，作用于全部 19 家快递商。SSL 强制校验默认开启，可用 `HttpClient::setVerifySsl(false)` 关闭（仅测试/内网自签场景）。

### 响应归一化策略

所有响应经由 `ResponseHandler` 按快递商（provider）注册的策略统一判定成功/失败并解包业务数据，
避免散落在各客户端的重复判断。EMS / 顺丰已预置精确策略；其余快递商回退到保守默认策略
（仅在 `error` 字段、`success === false`、或 `code ∈ [400,599]` 时判定失败，不擅自改包结构）。

如需为某家快递商定制策略：

```php
use Kode\ExpressApi\Common\ResponseHandler;

ResponseHandler::registerPolicy(
    'my_courier',
    static fn(array $r): bool => ($r['result'] ?? '') === 'FAIL', // 错误判定
    static fn(array $r): array => $r['body'] ?? $r                // 数据解包
);
```

### 跨快递商批量轨迹查询

`ExpressApiClient::batchQueryTracking()` 接收「快递商 + 运单号」条目列表，逐单调用对应客户端，
单条失败相互隔离（不中断其余查询），最终汇总 `results / success / failed`：

```php
use Kode\ExpressApi\ExpressApiClient;

$result = ExpressApiClient::batchQueryTracking([
    ['courier' => 'ems',  'number' => 'EMS123'],
    ['courier' => 'sf',   'number' => 'SF456'],
    ['courier' => 'dhl',  'number' => 'DHL789'],
]);

// $result['success'] => 成功条数
// $result['failed']  => 失败条数
// $result['results'][0] => ['index'=>0,'courier'=>'ems','number'=>'EMS123','ok'=>true,'data'=>[...]]
```

### 版本号入口

```php
echo ExpressApiClient::version(); // 例如 "2.3.0"
```

### 请求诊断

无需引入日志依赖，可随时读取最近一次请求的诊断元信息（含耗时、HTTP 状态码、重试次数）：

```php
$meta = HttpClient::getLastMeta();
// ['url'=>'...','method'=>'GET','http_code'=>200,'attempt'=>1,'duration_ms'=>37]
```

## 面单布局功能设计

### 功能概述

面单布局功能用于生成和管理快递面单的打印布局配置，支持不同快递公司的面单模板。

### 面单模板结构

```php
$template = [
    'id' => 'ems_standard_100x150',
    'name' => 'EMS标准面单(100mm×150mm)',
    'size' => [
        'width' => 100,   // 宽度(mm)
        'height' => 150,  // 高度(mm)
    ],
    'fields' => [
        'sender_name' => [
            'x' => 10,      // X坐标(mm)
            'y' => 20,      // Y坐标(mm)
            'width' => 50,  // 宽度(mm)
            'height' => 5,  // 高度(mm)
            'font_size' => 12,
            'align' => 'left',
        ],
        'receiver_name' => [
            'x' => 10,
            'y' => 40,
            'width' => 50,
            'height' => 5,
            'font_size' => 12,
            'align' => 'left',
        ],
        // 更多字段...
    ],
];
```

### 布局管理器

```php
use Kode\ExpressApi\Label\LayoutManager;

$layoutManager = new LayoutManager();

// 创建面单模板
$template = $layoutManager->createTemplate([
    'name' => 'EMS标准面单',
    'size' => ['width' => 100, 'height' => 150],
]);

// 添加字段
$template->addField('sender_name', [
    'x' => 10,
    'y' => 20,
    'width' => 50,
    'height' => 5,
]);

// 保存模板
$layoutManager->saveTemplate($template);
```

### 获取和使用字段值

```php
// 读取模板
$template = $layoutManager->getTemplate('ems_standard');

// 获取所有字段
$fields = $template->getFields();

// 为字段设置实际值
if (isset($fields['sender_name'])) {
    $fields['sender_name']->setValue('张三');
}

if (isset($fields['receiver_name'])) {
    $fields['receiver_name']->setValue('李四');
}

// 获取字段值
$senderName = $fields['sender_name']->getValue();
$receiverName = $fields['receiver_name']->getValue();

// 转换为数组格式（用于API传输或存储）
$fieldArray = $fields['sender_name']->toArray();
```

### 核心类设计

#### LayoutManager

负责面单布局的管理，包括创建、读取、更新、删除模板。

#### Template

表示一个面单模板，包含尺寸信息和字段定义。

### 使用面单可视化编辑器

1. 启动PHP内置服务器：

```bash
php -S localhost:8000 -t src/Label/Visualizer/
```

2. 访问 http://localhost:8000/ 进入面单可视化编辑器

3. 使用编辑器功能：
   - 拖拽调整元素位置
   - 调整元素大小
   - 修改元素属性（字体、颜色、边框等）
   - 添加文本、条形码、二维码元素
   - 选择面单规格
   - 预览和导出配置

### 面单模板配置示例

```php
$templateConfig = [
    'size' => 'ems_default',
    'dimensions' => [
        'width' => 100,
        'height' => 140,
    ],
    'fields' => [
        [
            'id' => 'tracking_number',
            'label' => '物流单号',
            'type' => 'text',
            'x' => 10,
            'y' => 10,
            'width' => 80,
            'height' => 15,
            'fontSize' => 10,
            'fontFamily' => 'Arial',
            'fontWeight' => 'bold',
            'align' => 'center',
            'showLabel' => true,
            'labelPosition' => 'top',
        ],
        [
            'id' => 'tracking_barcode',
            'label' => '条形码',
            'type' => 'barcode',
            'x' => 5,
            'y' => 30,
            'width' => 90,
            'height' => 30,
            'barcodeType' => 'code128',
            'showLabel' => false,
        ],
        // 更多字段配置...
    ],
];
```

## 多语言支持

AdvancedLayoutManager 支持创建和管理多语言字段，使得面单模板可以在不同语言环境下使用。

### MultilingualField 类

`MultilingualField` 类继承自 `Field` 类，提供了多语言标签的支持。

#### 构造函数

```php
new MultilingualField(string $id, array $config = [])
```

#### 配置参数

- `labels`: 关联数组，键为语言代码，值为对应语言的标签
- 所有其他 `Field` 类的配置参数同样适用

#### 方法

- `addLabel(string $language, string $label): self` - 添加语言标签
- `getLabelByLanguage(string $language): ?string` - 根据语言获取标签
- `getLabels(): array` - 获取所有语言标签
- `setLanguage(string $language): self` - 设置当前语言
- `getLanguage(): string` - 获取当前语言
- `setLabel(string $label): self` - 设置默认标签
- `getLabel(): string` - 获取当前语言的标签

#### 使用示例

```php
use Kode\ExpressApi\Label\MultilingualField;

// 创建多语言字段
$field = new MultilingualField('product_name', [
    'labels' => [
        'zh' => '产品名称',
        'en' => 'Product Name',
        'ja' => '商品名',
        'ko' => '제품명'
    ],
    'x' => 10,
    'y' => 30,
    'width' => 50,
    'height' => 5,
    'font_size' => 10
]);

// 设置当前语言
$field->setLanguage('en');  // 显示英文标签
echo $field->getLabel();    // 输出: Product Name

$field->setLanguage('ja');  // 显示日文标签
echo $field->getLabel();    // 输出: 商品名

// 获取特定语言的标签
echo $field->getLabelByLanguage('ko');  // 输出: 제품명

// 添加新的语言标签
$field->addLabel('fr', 'Nom du produit');
echo $field->getLabelByLanguage('fr');  // 输出: Nom du produit
```

### 在模板中使用多语言字段

在创建模板时，可以通过在字段配置中添加 `labels` 参数来创建多语言字段：

```php
use Kode\ExpressApi\Label\AdvancedLayoutManager;

$layoutManager = new AdvancedLayoutManager('/path/to/templates');

$config = [
    'id' => 'multilingual_template',
    'name' => '多语言模板',
    'courier' => 'ems',
    'size' => ['width' => 100, 'height' => 150],
    'fields' => [
        'sender' => [
            'label' => '发件人',
            'x' => 5,
            'y' => 5,
            'width' => 40,
            'height' => 10
        ],
        'product_name' => [
            'labels' => [
                'zh' => '产品名称',
                'en' => 'Product Name',
                'ja' => '商品名'
            ],
            'x' => 5,
            'y' => 20,
            'width' => 50,
            'height' => 5,
            'font_size' => 8
        ]
    ]
];
```

## 错误处理

所有 API 调用失败都会抛出 `Kode\ExpressApi\Common\Exception\ExpressApiException`，其 `getDetails()`
可获取原始响应体，便于排查。响应是否成功、如何解包由 `ResponseHandler` 的**按快递商策略**统一决定
（详见上文「响应归一化策略」）。约定上的标准响应结构如下：

```php
// 成功响应
[
    'success' => true,
    'data' => [...], // API返回的数据
    'message' => 'success',
    'code' => 0,
]

// 失败响应
[
    'success' => false,
    'data' => null,
    'message' => '错误信息',
    'code' => 错误代码,
]
```

瞬时网络故障（连接错误 / 超时 / 5xx）会按 `HttpClient::setRetry()` 自动重试；
4xx 客户端错误不会重试，直接抛出 `ExpressApiException`。

## 开发指南

### 集成新的快递公司

1. 创建新的配置类（继承AbstractConfig）
2. 创建认证类（实现AuthInterface）
3. 创建客户端类（实现ClientInterface）
4. 更新ExpressApiClient.php，添加新的快递公司支持
5. 如该快递商响应结构与通用约定不同，调用 `ResponseHandler::registerPolicy()` 注册专属的错误判定 / 数据解包策略（可选，未注册则回退保守默认策略）

### 代码规范

本项目遵循PSR-12代码规范。

#### 代码检查

使用PHP_CodeSniffer检查代码规范：

```bash
composer cs-check
```

#### 自动修复

自动修复代码规范问题：

```bash
composer cs-fix
```

### 运行测试

运行所有测试用例：

```bash
composer test
```

### 测试覆盖率

生成测试覆盖率报告：

```bash
composer test -- --coverage-html coverage-report
```

### 测试要求

1. **测试覆盖率**: 所有代码都必须有相应的测试用例，测试覆盖率应达到90%以上
2. **测试类型**: 包含单元测试和集成测试
3. **测试环境**: 测试应在沙箱环境中进行，避免影响生产数据

### Git工作流

#### 分支策略

- `main`: 主分支，包含稳定版本
- `develop`: 开发分支，包含最新功能
- `feature/*`: 功能分支，用于开发新功能
- `hotfix/*`: 热修复分支，用于紧急修复

#### 提交信息规范

提交信息应遵循以下格式：

```
<type>(<scope>): <subject>

<body>

<footer>
```

**type类型**:
- `feat`: 新功能
- `fix`: 修复bug
- `docs`: 文档更新
- `style`: 代码格式调整
- `refactor`: 代码重构
- `test`: 测试相关
- `chore`: 构建过程或辅助工具的变动

**示例**:
```
feat(ems-client): 添加面单布局功能

- 实现面单布局配置生成
- 支持自定义面单模板

Closes #123
```

### 版本管理

遵循语义化版本规范（SemVer）：

- 主版本号(MAJOR): 不兼容的API修改
- 次版本号(MINOR): 向后兼容的功能性新增
- 修订号(PATCH): 向后兼容的问题修正

## 支持的快递公司

SDK 覆盖一条完整的物流链：**国内快递 → 国际运输（海运 / 空运）→ 海关清关 → 末端派送**。

### 国内快递（快递）

| 代码 | 名称 | 备注 |
| --- | --- | --- |
| `ems` | 邮政EMS | OAuth2 鉴权 |
| `sf` | 顺丰速运 | 签名鉴权 |
| `yunda` | 韵达快递 | 签名鉴权 |
| `zto` | 中通快递 | 签名鉴权 |
| `sto` | 申通快递 | 签名鉴权 |
| `cainiao` | 菜鸟网络 | 需 PartnerId |
| `jd` | 京东快递/京东物流 | 签名鉴权 |

### 国际物流（跨境 / 货运）

| 代码 | 名称 | 鉴权方式 | 运输方式 |
| --- | --- | --- | --- |
| `fourpx` | 4PX递四方 | OAuth2 + MD5 签名 | 海运 / 空运 |
| `sf_international` | 顺丰国际 | HMAC-SHA256 签名 | 海运 / 空运 |
| `dhl` | DHL国际 | HTTP Basic | 空运为主 |
| `yunexpress` | 云途物流 | HMAC-SHA256 签名 | 海运 / 空运 |
| `ems_international` | EMS国际 | MD5 签名 | 海运 / 空运 |
| `yanwen` | 燕文物流 | MD5 签名 | 海运 / 空运 |

国际物流客户端统一继承 `International\AbstractInternationalClient`，提供：

- `sendShipment()` 下单（含海关申报要素）
- `createSeaFreight()` / `createAirFreight()` 海运 / 空运便捷入口
- `getQuotation()` 运费报价
- `declareCustoms()` 海关申报
- `queryCustoms()` 清关查询
- `queryOrder()` / `queryTracking()` / `cancelOrder()` / `printLabel()` 标准能力

> 注：各服务商真实接口路径与字段以签约后的开放平台文档为准，SDK 已提供标准鉴权与传输骨架，接入时按文档核对即可。

### 国内货运（零担 / 整车 / 快运）

| 代码 | 名称 | 鉴权方式 | 服务类型 |
| --- | --- | --- | --- |
| `debang` | 德邦物流 | MD5 签名 | 零担 / 整车 / 快运 |
| `ane` | 安能物流 | HMAC-SHA256 签名 | 零担 / 整车 / 快运 |
| `hoau` | 天地华宇 | MD5 签名 | 零担 / 整车 / 快运 |

国内货运客户端统一继承 `DomesticFreight\AbstractDomesticFreightClient`，提供：
- `sendShipment()` 下单（零担 / 整车 / 快运，含发货人 / 收货人 / 货物 / 起止地）
- `createLtl()` / `createFtl()` 零担 / 整车便捷入口
- `getQuotation()` 运费报价
- `queryNetwork()` 网点查询
- `queryOrder()` / `queryTracking()` / `cancelOrder()` / `printLabel()` 标准能力

> 注：各服务商真实接口路径与字段以签约后的开放平台文档为准，SDK 已提供标准鉴权与传输骨架，接入时按文档核对即可。

### 聚合查询（运单轨迹 + 运单号自动识别）

| 代码 | 名称 | 鉴权方式 | 说明 |
| --- | --- | --- | --- |
| `kuaidi100` | 快递100 | MD5 签名 | 运单轨迹 + 智能识别承运商 |
| `kuaidiniao` | 快递鸟 | MD5(Base64) 签名 | 运单轨迹 + 即时识别 |
| `juhe` | 聚合数据 | API Key | 运单轨迹 + 自动判定 |

聚合查询服务商**只提供轨迹查询与运单号自动识别**，并不承接下单 / 打单 / 拦截等实操业务；
调用其实操方法会抛出明确的「不支持」异常。它们更重要的角色是 **`CourierRecognizer` 的权威回退解析器**：
当运单号规则无法命中时，可将其接入为动态解析器，确定性地识别归属承运商。

- 已支持：京东快递/京东物流、快递100、快递鸟、聚合数据（见上表）

## 各快递公司特定配置参数

### 邮政EMS (ems)

```php
$emsConfig = [
    'app_key' => 'YOUR_EMS_APP_KEY',
    'app_secret' => 'YOUR_EMS_APP_SECRET',
    'sandbox' => true, // 使用沙箱环境
];
```

参数说明：
- `app_key`: EMS分配的应用Key
- `app_secret`: EMS分配的应用密钥
- `sandbox`: 是否使用沙箱环境（测试环境）

### 顺丰速运 (sf)

```php
$sfConfig = [
    'app_key' => 'YOUR_SF_APP_KEY',
    'app_secret' => 'YOUR_SF_APP_SECRET',
    'sandbox' => true,
];
```

参数说明：
- `app_key`: 顺丰分配的应用Key
- `app_secret`: 顺丰分配的应用密钥
- `sandbox`: 是否使用沙箱环境（测试环境）

### 韵达快递 (yunda)

```php
$yundaConfig = [
    'app_key' => 'YOUR_YUNDA_APP_KEY',
    'app_secret' => 'YOUR_YUNDA_APP_SECRET',
    'sandbox' => true,
];
```

参数说明：
- `app_key`: 韵达分配的应用Key
- `app_secret`: 韵达分配的应用密钥
- `sandbox`: 是否使用沙箱环境（测试环境）

### 中通快递 (zto)

```php
$ztoConfig = [
    'app_key' => 'YOUR_ZTO_APP_KEY',
    'app_secret' => 'YOUR_ZTO_APP_SECRET',
    'sandbox' => true,
];
```

参数说明：
- `app_key`: 中通分配的应用Key
- `app_secret`: 中通分配的应用密钥
- `sandbox`: 是否使用沙箱环境（测试环境）

### 申通快递 (sto)

```php
$stoConfig = [
    'app_key' => 'YOUR_STO_APP_KEY',
    'app_secret' => 'YOUR_STO_APP_SECRET',
    'sandbox' => true,
];
```

参数说明：
- `app_key`: 申通分配的应用Key
- `app_secret`: 申通分配的应用密钥
- `sandbox`: 是否使用沙箱环境（测试环境）

### 菜鸟网络 (cainiao)

```php
$cainiaoConfig = [
    'app_key' => 'YOUR_CAINIAO_APP_KEY',
    'app_secret' => 'YOUR_CAINIAO_APP_SECRET',
    'partner_id' => 'YOUR_CAINIAO_PARTNER_ID',
    'sandbox' => true,
];
```

参数说明：
- `app_key`: 菜鸟网络分配的应用Key
- `app_secret`: 菜鸟网络分配的应用密钥
- `partner_id`: 菜鸟网络分配的合作伙伴ID
- `sandbox`: 是否使用沙箱环境（测试环境）

## 技术依赖

- PHP 8.3+
- PHPUnit 12（测试框架）

## 许可证

MIT License

## 贡献

欢迎提交Issue和Pull Request！

## 联系我们

如有问题或建议，请通过以下方式联系：

- 邮箱：382601296@qq.com
- GitHub：https://github.com/kode/express-api