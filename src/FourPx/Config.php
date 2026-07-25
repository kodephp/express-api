<?php

namespace Kode\ExpressApi\FourPx;

use Kode\ExpressApi\Common\AbstractCourierConfig;

/**
 * 4PX 递四方配置
 *
 * 真实接入地址（递四方开放平台）：
 *  - 生产：https://open.4px.com/router/api/service
 *  - 测试：https://open-test.4px.com/router/api/service
 *
 * 公共参数（拼在 URL 后）：method / app_key / v / timestamp / format / sign / language / access_token
 * 业务数据以 JSON 放在请求体，签名规则见 FourPx\Auth / Client。
 */
class Config extends AbstractCourierConfig
{
    protected function getProductionHost(): string
    {
        return 'https://open.4px.com/router/api/service';
    }

    protected function getSandboxHost(): string
    {
        return 'https://open-test.4px.com/router/api/service';
    }

    public function getVersion(): string
    {
        // 递四方开放平台接口路径已含 /router/api/service，无需额外版本前缀
        return '';
    }
}
