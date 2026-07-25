# 更新日志 (Changelog)

本项目遵循 [语义化版本](https://semver.org/lang/zh-CN/)（SemVer）。

## [1.2.0] - 2026-07-25

### 重构 (Refactor)
- 提取 `Common/AbstractCourierConfig` 与 `Common/AbstractCourierAuth` 两个抽象基类，消除 6 个快递商 `Config`/`Auth` 中大量重复的取参、构造、URL 拼接与令牌管理逻辑。
- `Common/HttpClient` 统一收口所有 HTTP 请求（JSON 编码、Bearer 鉴权、超时、异常标准化），移除 `EMS/Auth` 中重复且无 SSL 校验的裸 cURL 实现。

### 修复 (Fixed)
- 修复菜鸟（Cainiao）`Client` 调用不存在的 `getDefaultCourierCode()` / `getDefaultTemplateCode()` 导致的运行时致命错误（已在 `Cainiao/Config` 补齐对应方法）。
- 对齐 `Common/ClientInterface` 与具体 `Client` 的方法签名：`cancelOrder(string $orderId, string $reason = '')` 与 `queryTracking(string $trackingNumber, string $language = 'zh-CN')` 的可选参数。

### 安全 (Security)
- `HttpClient` 默认强制 `CURLOPT_SSL_VERIFYPEER = true`、`CURLOPT_SSL_VERIFYHOST = 2`，并支持自定义 CA 证书（`verify_peer` / `ca_file`），修复原 `EMS/Auth` 裸 cURL 未校验 SSL 证书的中间人风险。
- 升级 `phpunit/phpunit` 至 `^12.5`（>= 12.5.8），修复安全公告 CVE-2026-24765。

### 文档 (Docs)
- 修正 README 中与实际标量方法签名不符的调用示例（`queryOrder` / `cancelOrder` / `queryTracking` / `intercept` / `modify` / `printLabel` 等）。
- 修正 README 过时信息：移除未使用的 `GuzzleHttp` 依赖声明（项目实际使用自研 `HttpClient`），许可证由 `Apache 2.0` 更正为 `MIT`（与 `composer.json` 一致）。
- 统一 PHP 最低版本为 `8.3+`（匹配 PHPUnit 12.5 工具链要求）。

### 清理 (Chore)
- 将测试用例的模板存储目录从 `examples/templates`、`examples/exports` 隔离到独立临时目录，避免测试产物污染示例目录与仓库。
- 删除 `examples/` 下 14 个测试残留 / 未引用的 JSON 文件，仅保留 `ems_advanced_001.json`、`comprehensive_ems_001.json` 两个示例种子模板，并为 `examples/exports` 增加 `.gitkeep` 占位。

## [1.1.0] - 历史版本
- 初始多快递商集成与面单布局管理能力。
