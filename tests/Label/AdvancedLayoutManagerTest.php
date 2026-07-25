<?php

namespace Kode\ExpressApi\Tests\Label;

use PHPUnit\Framework\TestCase;
use Kode\ExpressApi\Label\AdvancedLayoutManager;
use Kode\ExpressApi\Label\ConcreteTemplate;
use Kode\ExpressApi\Label\Field;
use Kode\ExpressApi\Label\MultilingualField;

class AdvancedLayoutManagerTest extends TestCase
{
    private $templateDir;
    private $exportDir;
    private $layoutManager;
    
    protected function setUp(): void
    {
        // 使用独立临时目录作为模板/导出存储，避免污染 examples/ 与仓库
        $runId = getmypid() ?: time();
        $this->templateDir = sys_get_temp_dir() . '/ems_api_templates_' . $runId;
        $this->exportDir = sys_get_temp_dir() . '/ems_api_exports_' . $runId;

        foreach ([$this->templateDir, $this->exportDir] as $dir) {
            if (is_dir($dir)) {
                self::removeDir($dir);
            }
            mkdir($dir, 0777, true);
        }

        // 初始化布局管理器
        $this->layoutManager = new AdvancedLayoutManager($this->templateDir);
    }

    protected function tearDown(): void
    {
        // 彻底清理本次测试使用的临时目录，避免残留污染仓库
        self::removeDir($this->templateDir);
        self::removeDir($this->exportDir);
    }

    /**
     * 递归删除目录
     */
    private static function removeDir(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }
        $items = array_diff(scandir($dir), ['.', '..']);
        foreach ($items as $item) {
            $path = $dir . DIRECTORY_SEPARATOR . $item;
            if (is_dir($path)) {
                self::removeDir($path);
            } else {
                unlink($path);
            }
        }
        rmdir($dir);
    }
    
    public function testConstructor()
    {
        // 测试正常初始化
        $manager = new AdvancedLayoutManager($this->templateDir);
        $this->assertInstanceOf(AdvancedLayoutManager::class, $manager);
        
        // 测试目录不存在时自动创建
        $newDir = $this->templateDir . '/new_test_dir';
        if (is_dir($newDir)) {
            rmdir($newDir);
        }
        
        $manager2 = new AdvancedLayoutManager($newDir);
        $this->assertTrue(is_dir($newDir));
        
        // 清理测试目录
        if (is_dir($newDir)) {
            rmdir($newDir);
        }
    }
    
    public function testCreateTemplateWithArrayConfig()
    {
        $config = [
            'id' => 'test_template_001',
            'name' => '测试模板',
            'courier' => 'ems',
            'size' => ['width' => 100, 'height' => 150],
            'unit' => 'mm',
            'paper_type' => 'thermal',
            'fields' => [
                'sender' => [
                    'label' => '发件人',
                    'x' => 5,
                    'y' => 5,
                    'width' => 40,
                    'height' => 10,
                    'font_size' => 8
                ]
            ]
        ];
        
        $template = $this->layoutManager->createTemplate($config);
        
        $this->assertInstanceOf(ConcreteTemplate::class, $template);
        $this->assertEquals('test_template_001', $template->getId());
        $this->assertEquals('测试模板', $template->getName());
        $this->assertEquals('ems', $template->getCourier());
        $this->assertEquals(['width' => 100, 'height' => 150], $template->getSize());
        $this->assertCount(1, $template->getFields());
    }
    
    public function testCreateTemplateWithStringSize()
    {
        $config = [
            'id' => 'test_template_002',
            'name' => '测试模板2',
            'courier' => 'sf',
            'size' => 'standard_100x150',
            'unit' => 'mm',
            'paper_type' => 'normal',
            'fields' => []
        ];
        
        $template = $this->layoutManager->createTemplate($config);
        
        $this->assertInstanceOf(ConcreteTemplate::class, $template);
        $this->assertEquals(['width' => 100, 'height' => 150], $template->getSize());
    }
    
    public function testSaveAndGetTemplate()
    {
        $config = [
            'id' => 'test_save_template',
            'name' => '保存测试模板',
            'courier' => 'ems',
            'size' => ['width' => 100, 'height' => 150],
            'unit' => 'mm',
            'paper_type' => 'thermal',
            'fields' => [
                'sender' => [
                    'label' => '发件人',
                    'x' => 5,
                    'y' => 5,
                    'width' => 40,
                    'height' => 10,
                    'font_size' => 8
                ]
            ]
        ];
        
        $template = $this->layoutManager->createTemplate($config);
        $result = $this->layoutManager->saveTemplate($template);
        
        $this->assertTrue($result);
        
        // 测试获取模板
        $retrievedTemplate = $this->layoutManager->getTemplate('test_save_template');
        $this->assertInstanceOf(ConcreteTemplate::class, $retrievedTemplate);
        $this->assertEquals('保存测试模板', $retrievedTemplate->getName());
        $this->assertCount(1, $retrievedTemplate->getFields());
    }
    
    public function testListTemplates()
    {
        // 先创建几个测试模板
        $config1 = [
            'id' => 'list_test_001',
            'name' => '列表测试模板1',
            'courier' => 'ems',
            'size' => ['width' => 100, 'height' => 150],
            'unit' => 'mm',
            'paper_type' => 'thermal',
            'fields' => []
        ];
        
        $config2 = [
            'id' => 'list_test_002',
            'name' => '列表测试模板2',
            'courier' => 'sf',
            'size' => ['width' => 100, 'height' => 100],
            'unit' => 'mm',
            'paper_type' => 'normal',
            'fields' => []
        ];
        
        $template1 = $this->layoutManager->createTemplate($config1);
        $template2 = $this->layoutManager->createTemplate($config2);
        
        $this->layoutManager->saveTemplate($template1);
        $this->layoutManager->saveTemplate($template2);
        
        // 测试列出所有模板
        $templates = $this->layoutManager->listTemplates();
        $this->assertIsArray($templates);
        $this->assertGreaterThanOrEqual(2, count($templates));
        
        // 检查是否包含我们创建的模板
        $foundTemplates = 0;
        foreach ($templates as $template) {
            if ($template->getId() === 'list_test_001' || $template->getId() === 'list_test_002') {
                $foundTemplates++;
            }
        }
        $this->assertEquals(2, $foundTemplates);
    }
    
    public function testListTemplatesByCourier()
    {
        // 创建EMS模板
        $emsConfig = [
            'id' => 'courier_test_ems',
            'name' => 'EMS测试模板',
            'courier' => 'ems',
            'size' => ['width' => 100, 'height' => 150],
            'unit' => 'mm',
            'paper_type' => 'thermal',
            'fields' => []
        ];
        
        // 创建顺丰模板
        $sfConfig = [
            'id' => 'courier_test_sf',
            'name' => '顺丰测试模板',
            'courier' => 'sf',
            'size' => ['width' => 100, 'height' => 100],
            'unit' => 'mm',
            'paper_type' => 'normal',
            'fields' => []
        ];
        
        $emsTemplate = $this->layoutManager->createTemplate($emsConfig);
        $sfTemplate = $this->layoutManager->createTemplate($sfConfig);
        
        $this->layoutManager->saveTemplate($emsTemplate);
        $this->layoutManager->saveTemplate($sfTemplate);
        
        // 测试按快递公司列出模板
        $emsTemplates = $this->layoutManager->listTemplatesByCourier('ems');
        $sfTemplates = $this->layoutManager->listTemplatesByCourier('sf');
        
        $this->assertIsArray($emsTemplates);
        $this->assertIsArray($sfTemplates);
        
        // 检查EMS模板
        $this->assertGreaterThanOrEqual(1, count($emsTemplates));
        $foundEms = false;
        foreach ($emsTemplates as $template) {
            if ($template->getId() === 'courier_test_ems') {
                $foundEms = true;
                break;
            }
        }
        $this->assertTrue($foundEms);
        
        // 检查顺丰模板
        $this->assertGreaterThanOrEqual(1, count($sfTemplates));
        $foundSf = false;
        foreach ($sfTemplates as $template) {
            if ($template->getId() === 'courier_test_sf') {
                $foundSf = true;
                break;
            }
        }
        $this->assertTrue($foundSf);
    }
    
    public function testCopyTemplate()
    {
        // 创建原始模板
        $originalConfig = [
            'id' => 'copy_original',
            'name' => '原始模板',
            'courier' => 'ems',
            'size' => ['width' => 100, 'height' => 150],
            'unit' => 'mm',
            'paper_type' => 'thermal',
            'fields' => [
                'sender' => [
                    'label' => '发件人',
                    'x' => 5,
                    'y' => 5,
                    'width' => 40,
                    'height' => 10,
                    'font_size' => 8
                ]
            ]
        ];
        
        $originalTemplate = $this->layoutManager->createTemplate($originalConfig);
        $this->layoutManager->saveTemplate($originalTemplate);
        
        // 复制模板
        $copiedTemplate = $this->layoutManager->copyTemplate(
            'copy_original',
            'copy_new',
            '复制的模板'
        );
        
        $this->assertInstanceOf(ConcreteTemplate::class, $copiedTemplate);
        $this->assertEquals('copy_new', $copiedTemplate->getId());
        $this->assertEquals('复制的模板', $copiedTemplate->getName());
        $this->assertEquals('ems', $copiedTemplate->getCourier());
        $this->assertCount(1, $copiedTemplate->getFields());
        
        // 验证原始模板未被修改
        $unchangedTemplate = $this->layoutManager->getTemplate('copy_original');
        $this->assertEquals('原始模板', $unchangedTemplate->getName());
    }
    
    public function testValidateTemplate()
    {
        // 创建有效的模板
        $validConfig = [
            'id' => 'valid_template',
            'name' => '有效模板',
            'courier' => 'ems',
            'size' => ['width' => 100, 'height' => 150],
            'unit' => 'mm',
            'paper_type' => 'thermal',
            'fields' => [
                'sender' => [
                    'label' => '发件人',
                    'x' => 5,
                    'y' => 5,
                    'width' => 40,
                    'height' => 10,
                    'font_size' => 8
                ],
                'recipient' => [
                    'label' => '收件人',
                    'x' => 5,
                    'y' => 20,
                    'width' => 40,
                    'height' => 10,
                    'font_size' => 8
                ],
                'order_no' => [
                    'label' => '订单号',
                    'x' => 5,
                    'y' => 35,
                    'width' => 40,
                    'height' => 8,
                    'font_size' => 8
                ]
            ]
        ];
        
        $validTemplate = $this->layoutManager->createTemplate($validConfig);
        $errors = $this->layoutManager->validateTemplate($validTemplate);
        
        $this->assertIsArray($errors);
        $this->assertEmpty($errors);
        
        // 创建无效的模板（字段位置超出边界）
        $invalidConfig = [
            'id' => 'invalid_template',
            'name' => '无效模板',
            'courier' => 'ems',
            'size' => ['width' => 100, 'height' => 150],
            'unit' => 'mm',
            'paper_type' => 'thermal',
            'fields' => [
                'sender' => [
                    'label' => '发件人',
                    'x' => 5,
                    'y' => 5,
                    'width' => 40,
                    'height' => 10,
                    'font_size' => 8
                ],
                'recipient' => [
                    'label' => '收件人',
                    'x' => 5,
                    'y' => 20,
                    'width' => 40,
                    'height' => 10,
                    'font_size' => 8
                ],
                'order_no' => [
                    'label' => '订单号',
                    'x' => 5,
                    'y' => 35,
                    'width' => 40,
                    'height' => 8,
                    'font_size' => 8
                ],
                // 这个字段超出了模板边界
                'invalid_field' => [
                    'label' => '无效字段',
                    'x' => 90,
                    'y' => 145,
                    'width' => 20,
                    'height' => 20,
                    'font_size' => 8
                ]
            ]
        ];
        
        $invalidTemplate = $this->layoutManager->createTemplate($invalidConfig);
        $errors = $this->layoutManager->validateTemplate($invalidTemplate);
        
        $this->assertIsArray($errors);
        $this->assertNotEmpty($errors);
    }
    
    public function testExportAndImportTemplate()
    {
        // 先删除可能存在的模板
        $this->layoutManager->deleteTemplate('export_test');
        
        // 创建模板用于导出
        $exportConfig = [
            'id' => 'export_test',
            'name' => '导出测试模板',
            'courier' => 'ems',
            'size' => ['width' => 100, 'height' => 150],
            'unit' => 'mm',
            'paper_type' => 'thermal',
            'fields' => [
                'sender' => [
                    'label' => '发件人',
                    'x' => 5,
                    'y' => 5,
                    'width' => 40,
                    'height' => 10,
                    'font_size' => 8
                ],
                'recipient' => [
                    'label' => '收件人',
                    'x' => 5,
                    'y' => 20,
                    'width' => 40,
                    'height' => 10,
                    'font_size' => 8
                ]
            ]
        ];
        
        $exportTemplate = $this->layoutManager->createTemplate($exportConfig);
        $this->layoutManager->saveTemplate($exportTemplate);
        
        // 导出模板
        $exportedData = $this->layoutManager->exportTemplate('export_test');
        $this->assertIsString($exportedData);
        $this->assertNotEmpty($exportedData);
        
        // 先删除导出的模板，避免重复
        $this->layoutManager->deleteTemplate('export_test');
        
        // 导入模板（直接传入JSON数据，而不是文件路径）
        $importedTemplate = $this->layoutManager->importTemplate($exportedData);
        
        $this->assertInstanceOf(ConcreteTemplate::class, $importedTemplate);
        $this->assertEquals('export_test', $importedTemplate->getId());
        $this->assertEquals('导出测试模板', $importedTemplate->getName());
        $this->assertCount(2, $importedTemplate->getFields());
    }
    
    public function testGetSupportedCouriers()
    {
        $couriers = $this->layoutManager->getSupportedCouriers();
        
        $this->assertIsArray($couriers);
        $this->assertContains('ems', $couriers);
        $this->assertContains('sf', $couriers);
    }
    
    public function testGetSupportedSizes()
    {
        $sizes = $this->layoutManager->getSupportedSizes();
        
        $this->assertIsArray($sizes);
        $this->assertArrayHasKey('standard_100x150', $sizes);
        $this->assertArrayHasKey('standard_100x100', $sizes);
        $this->assertArrayHasKey('standard_80x80', $sizes);
        
        $this->assertEquals(['width' => 100, 'height' => 150], $sizes['standard_100x150']);
        $this->assertEquals(['width' => 100, 'height' => 100], $sizes['standard_100x100']);
        $this->assertEquals(['width' => 80, 'height' => 80], $sizes['standard_80x80']);
    }
    
    public function testTemplateWithMultilingualField()
    {
        $config = [
            'id' => 'multilingual_test',
            'name' => '多语言字段测试',
            'courier' => 'ems',
            'size' => ['width' => 100, 'height' => 150],
            'unit' => 'mm',
            'paper_type' => 'thermal',
            'fields' => [
                'product_name' => [
                    'labels' => [  // 使用labels而不是label来创建多语言字段
                        'en' => 'Product Name',
                        'zh' => '产品名称'
                    ],
                    'x' => 5,
                    'y' => 5,
                    'width' => 40,
                    'height' => 10,
                    'font_size' => 8
                ]
            ]
        ];
        
        $template = $this->layoutManager->createTemplate($config);
        
        $this->assertInstanceOf(ConcreteTemplate::class, $template);
        $this->assertEquals('multilingual_test', $template->getId());
        
        $fields = $template->getFields();
        $this->assertCount(1, $fields);
        
        $field = reset($fields);
        $this->assertInstanceOf(\Kode\ExpressApi\Label\MultilingualField::class, $field);
    }
}