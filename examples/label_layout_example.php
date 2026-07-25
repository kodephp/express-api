<?php
/**
 * 面单布局管理器示例
 * 此示例展示如何使用LabelVisualizer类创建、编辑和管理快递面单布局
 */

require_once __DIR__ . '/../vendor/autoload.php';

use Kode\ExpressApi\Label\Template;
use Kode\ExpressApi\Label\AdvancedLayoutManager;
use Kode\ExpressApi\Label\Visualizer\LabelVisualizer;

/**
 * 创建面单可视化管理器示例
 * 
 * 此示例演示了：
 * 1. 创建默认面单模板
 * 2. 添加和配置面单元素（文本、条形码、二维码）
 * 3. 生成HTML预览
 * 4. 获取完整的可视化编辑页面
 */

// 创建示例数据
$sampleData = [
    'tracking_number' => 'SF1234567890123',
    'order_no' => 'ORD20240501001',
    'sender_name' => '张三',
    'sender_phone' => '13800138000',
    'sender_province' => '广东省',
    'sender_city' => '深圳市',
    'sender_district' => '南山区',
    'sender_address' => '科技园南区8栋',
    'recipient_name' => '李四',
    'recipient_phone' => '13900139000',
    'recipient_province' => '北京市',
    'recipient_city' => '北京市',
    'recipient_district' => '朝阳区',
    'recipient_address' => '建国路88号',
    'product_name' => '电子产品',
    'quantity' => 1,
    'weight' => 0.5,
];

// 创建默认面单模板配置
$templateConfig = [
    'size' => 'ems_default',
    'dimensions' => [
        'width' => 100,
        'height' => 140,
    ],
    'fields' => [
        // 运单号
        [
            'id' => 'tracking_number',
            'label' => '运单号',
            'type' => 'text',
            'x' => 10,
            'y' => 10,
            'width' => 80,
            'height' => 15,
            'fontSize' => 12,
            'fontFamily' => 'Arial',
            'fontWeight' => 'bold',
            'align' => 'center',
            'showLabel' => true,
            'labelPosition' => 'top',
            'labelFontSize' => 10,
            'labelColor' => '#666666',
            'borderWidth' => 0,
        ],
        // 运单号条形码
        [
            'id' => 'tracking_barcode',
            'label' => '',
            'type' => 'barcode',
            'x' => 5,
            'y' => 30,
            'width' => 90,
            'height' => 30,
            'barcodeType' => 'code128',
            'showLabel' => false,
            'borderWidth' => 0,
        ],
        // 收件人信息
        [
            'id' => 'recipient_info',
            'label' => '收件人',
            'type' => 'text',
            'x' => 5,
            'y' => 65,
            'width' => 90,
            'height' => 25,
            'fontSize' => 10,
            'fontFamily' => 'Arial',
            'align' => 'left',
            'showLabel' => true,
            'labelPosition' => 'top',
            'labelFontSize' => 9,
            'labelColor' => '#666666',
            'borderWidth' => 1,
            'borderColor' => '#dddddd',
        ],
        // 发件人信息
        [
            'id' => 'sender_info',
            'label' => '发件人',
            'type' => 'text',
            'x' => 5,
            'y' => 95,
            'width' => 90,
            'height' => 20,
            'fontSize' => 9,
            'fontFamily' => 'Arial',
            'align' => 'left',
            'showLabel' => true,
            'labelPosition' => 'top',
            'labelFontSize' => 9,
            'labelColor' => '#666666',
            'borderWidth' => 1,
            'borderColor' => '#dddddd',
        ],
        // 二维码
        [
            'id' => 'qr_code',
            'label' => '查询码',
            'type' => 'qrcode',
            'x' => 75,
            'y' => 65,
            'width' => 20,
            'height' => 20,
            'showLabel' => true,
            'labelPosition' => 'bottom',
            'labelFontSize' => 8,
            'borderWidth' => 0,
        ],
    ],
];

// 创建LabelVisualizer实例
$template = new Template($templateConfig);
$layoutManager = new AdvancedLayoutManager(sys_get_temp_dir() . '/ems_label_templates_' . uniqid());
$visualizer = new LabelVisualizer($template, $layoutManager);

// 方法1: 生成简单的HTML预览
$htmlPreview = $visualizer->generateHtmlPreview($sampleData);

echo "HTML预览生成示例:
";
echo "==========================================
";
echo $htmlPreview;
echo "\n==========================================\n\n";

// 方法2: 获取完整的可视化编辑页面
// 注意: 此方法生成的完整HTML页面应通过浏览器访问，而不是直接输出到控制台
// 以下代码展示了如何将完整HTML页面保存到文件
$fullHtmlPage = $visualizer->generateFullHtmlPage($sampleData);

// 保存完整HTML页面到文件
$htmlFilePath = __DIR__ . '/label_visualizer_demo.html';
file_put_contents($htmlFilePath, $fullHtmlPage);

echo "完整可视化编辑页面已保存至: $htmlFilePath\n";
echo "您可以通过浏览器打开此文件查看完整的面单可视化编辑功能\n\n";

// 方法3: 导出模板配置为JSON
$templateJson = json_encode($templateConfig, JSON_PRETTY_PRINT);
echo "模板配置JSON:\n";
echo "==========================================\n";
echo $templateJson;
echo "\n==========================================\n\n";

// 使用说明
echo "使用说明:\n";
echo "==========================================\n";
echo "1. 使用命令启动PHP内置服务器查看可视化编辑器:\n";
echo "   php -S localhost:8000 -t src/Label/Visualizer/\n\n";
echo "2. 在浏览器中访问:\n";
echo "   http://localhost:8000/\n\n";
echo "3. 在编辑器中您可以:\n";
echo "   - 拖拽调整元素位置\n";
echo "   - 调整元素大小\n";
echo "   - 修改元素属性（字体、颜色、边框等）\n";
echo "   - 添加文本、条形码、二维码元素\n";
echo "   - 选择面单规格\n";
echo "   - 预览和导出配置\n";
echo "==========================================\n";