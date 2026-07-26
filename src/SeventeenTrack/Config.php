<?php

namespace Kode\ExpressApi\SeventeenTrack;

use Kode\ExpressApi\Common\AbstractCourierConfig;

/**
 * 17TRACK API 配置类（国际运单识别 / 轨迹聚合）
 *
 * 17TRACK 接口路径自带 /trackings/v2.1 前缀，故版本号置空，
 * getBaseUrl() 直接返回 Host，避免拼接出多余的斜杠。
 */
class Config extends AbstractCourierConfig
{
    /**
     * 生产环境 Host
     *
     * @return string
     */
    protected function getProductionHost(): string
    {
        return 'https://api.17track.net';
    }

    /**
     * 沙箱环境 Host（17TRACK 未区分沙箱，复用生产地址）
     *
     * @return string
     */
    protected function getSandboxHost(): string
    {
        return 'https://api.17track.net';
    }

    /**
     * 接口路径已自带前缀，版本号置空
     *
     * @return string
     */
    public function getVersion(): string
    {
        return '';
    }
}
