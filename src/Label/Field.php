<?php

namespace Kode\ExpressApi\Label;

/**
 * 面单字段类
 */
class Field
{
    /**
     * @var string 字段ID
     */
    protected $id;

    /**
     * @var string 字段标签
     */
    protected $label;

    /**
     * @var float X坐标(mm)
     */
    protected $x;

    /**
     * @var float Y坐标(mm)
     */
    protected $y;

    /**
     * @var float 宽度(mm)
     */
    protected $width;

    /**
     * @var float 高度(mm)
     */
    protected $height;

    /**
     * @var int 字体大小
     */
    protected $fontSize = 12;

    /**
     * @var string 对齐方式
     */
    protected $align = 'left';

    /**
     * @var string 字体名称
     */
    protected $fontFamily = 'Arial';

    /**
     * @var bool 是否加粗
     */
    protected $bold = false;

    /**
     * @var bool 是否斜体
     */
    protected $italic = false;

    /**
     * @var string 字段值
     */
    protected $value;

    /**
     * @var string 字段类型 (text, barcode, qrcode, image)
     */
    protected $type = 'text';

    /**
     * 条形码类型 (code128, code39, ean13, etc.)
     *
     * @var string
     */
    protected $barcodeType = 'code128';

    /**
     * 条形码模块宽度
     *
     * @var float
     */
    protected $barcodeModuleWidth = 0.2;

    /**
     * 条形码高度
     *
     * @var float
     */
    protected $barcodeHeight = 10;

    /**
     * 二维码大小
     *
     * @var int
     */
    protected $qrcodeSize = 120;

    /**
     * 二维码错误修正级别
     *
     * @var string
     */
    protected $qrcodeErrorCorrection = 'M';

    /**
     * 文本颜色
     *
     * @var string
     */
    protected $textColor = '#000000';

    /**
     * 标签字体大小
     *
     * @var int
     */
    protected $labelFontSize = 8;

    /**
     * 标签字体
     *
     * @var string
     */
    protected $labelFontFamily = 'Arial';

    /**
     * 标签颜色
     *
     * @var string
     */
    protected $labelColor = '#666666';

    /**
     * 标签对齐方式
     *
     * @var string
     */
    protected $labelAlign = 'left';

    /**
     * 内边距
     *
     * @var array
     */
    protected $padding = ['top' => 2, 'right' => 2, 'bottom' => 2, 'left' => 2];

    /**
     * 标签位置
     *
     * @var string
     */
    protected $labelPosition = 'top';

    /**
     * 是否显示标签
     *
     * @var bool
     */
    protected $showLabel = true;
    
    /**
     * 设置是否显示标签
     *
     * @param bool $showLabel 是否显示标签
     * @return self
     */
    public function setShowLabel(bool $showLabel): self
    {
        $this->showLabel = $showLabel;
        return $this;
    }
    
    
    
    /**
     * @var int 边框宽度
     */
    protected $borderWidth = 0;
    
    /**
     * @var string 边框颜色
     */
    protected $borderColor = '#000000';
    
    /**
     * @var string 背景颜色
     */
    protected $backgroundColor = 'transparent';
    
    /**
     * @var int 旋转角度
     */
    protected $rotation = 0;

    /**
     * @var string|null 图片路径（图片类型字段）
     */
    protected $imagePath;

    /**
     * 构造函数
     *
     * @param string $id 字段ID
     * @param array $config 字段配置
     */
    public function __construct(string $id, array $config = [])
    {
        $this->id = $id;
        $this->configure($config);
    }

    /**
     * 配置字段属性
     *
     * @param array $config 字段配置
     * @return self
     */
    public function configure(array $config): self
    {
        // 处理特殊键名
        $keyMap = [
            'barcode_type' => 'barcodeType',
            'image_path' => 'imagePath',
            'font_size' => 'fontSize',
            'font_family' => 'fontFamily',
            'border_width' => 'borderWidth',
            'border_color' => 'borderColor',
            'background_color' => 'backgroundColor',
            'show_label' => 'showLabel'
        ];

        foreach ($config as $key => $value) {
            // 处理键名映射
            if (isset($keyMap[$key])) {
                $key = $keyMap[$key];
            }

            $setter = 'set' . str_replace('_', '', ucwords($key, '_'));
            if (method_exists($this, $setter)) {
                $this->$setter($value);
            }
        }
        return $this;
    }

    /**
     * 获取字段ID
     *
     * @return string
     */
    public function getId(): string
    {
        return $this->id;
    }

    /**
     * 设置字段标签
     *
     * @param string $label 字段标签
     * @return self
     */
    public function setLabel(string $label): self
    {
        $this->label = $label;
        return $this;
    }

    /**
     * 获取字段标签
     *
     * @return string
     */
    public function getLabel(): string
    {
        return $this->label ?? $this->id;
    }

    /**
     * 设置X坐标
     *
     * @param float $x X坐标(mm)
     * @return self
     */
    public function setX(float $x): self
    {
        $this->x = $x;
        return $this;
    }

    /**
     * 设置边框宽度
     *
     * @param int $borderWidth 边框宽度
     * @return self
     */
    public function setBorderWidth(int $borderWidth): self
    {
        $this->borderWidth = $borderWidth;
        return $this;
    }

    /**
     * 获取边框宽度
     *
     * @return int
     */
    public function getBorderWidth(): int
    {
        return $this->borderWidth;
    }

    /**
     * 设置边框颜色
     *
     * @param string $borderColor 边框颜色
     * @return self
     */
    public function setBorderColor(string $borderColor): self
    {
        $this->borderColor = $borderColor;
        return $this;
    }

    /**
     * 获取边框颜色
     *
     * @return string
     */
    public function getBorderColor(): string
    {
        return $this->borderColor;
    }

    /**
     * 设置背景颜色
     *
     * @param string $backgroundColor 背景颜色
     * @return self
     */
    public function setBackgroundColor(string $backgroundColor): self
    {
        $this->backgroundColor = $backgroundColor;
        return $this;
    }

    /**
     * 获取背景颜色
     *
     * @return string
     */
    public function getBackgroundColor(): string
    {
        return $this->backgroundColor;
    }

    /**
     * 设置旋转角度
     *
     * @param int $rotation 旋转角度
     * @return self
     */
    public function setRotation(int $rotation): self
    {
        $this->rotation = $rotation;
        return $this;
    }

    /**
     * 获取旋转角度
     *
     * @return int
     */
    public function getRotation(): int
    {
        return $this->rotation;
    }

    /**
     * 获取是否显示标签
     *
     * @return bool
     */
    public function getShowLabel(): bool
    {
        return $this->showLabel;
    }

    /**
     * 获取X坐标
     *
     * @return float
     */
    public function getX(): float
    {
        return $this->x;
    }

    /**
     * 设置Y坐标
     *
     * @param float $y Y坐标(mm)
     * @return self
     */
    public function setY(float $y): self
    {
        $this->y = $y;
        return $this;
    }

    /**
     * 获取Y坐标
     *
     * @return float
     */
    public function getY(): float
    {
        return $this->y;
    }

    /**
     * 设置宽度
     *
     * @param float $width 宽度(mm)
     * @return self
     */
    public function setWidth(float $width): self
    {
        $this->width = $width;
        return $this;
    }

    /**
     * 获取宽度
     *
     * @return float
     */
    public function getWidth(): float
    {
        return $this->width;
    }

    /**
     * 设置高度
     *
     * @param float $height 高度(mm)
     * @return self
     */
    public function setHeight(float $height): self
    {
        $this->height = $height;
        return $this;
    }

    /**
     * 获取高度
     *
     * @return float
     */
    public function getHeight(): float
    {
        return $this->height;
    }

    /**
     * 设置字体大小
     *
     * @param int $fontSize 字体大小
     * @return self
     */
    public function setFontSize(int $fontSize): self
    {
        $this->fontSize = $fontSize;
        return $this;
    }

    /**
     * 获取字体大小
     *
     * @return int
     */
    public function getFontSize(): int
    {
        return $this->fontSize;
    }

    /**
     * 设置对齐方式
     *
     * @param string $align 对齐方式 (left, center, right)
     * @return self
     */
    public function setAlign(string $align): self
    {
        $this->align = $align;
        return $this;
    }

    /**
     * 获取对齐方式
     *
     * @return string
     */
    public function getAlign(): string
    {
        return $this->align;
    }

    /**
     * 设置字体名称
     *
     * @param string $fontFamily 字体名称
     * @return self
     */
    public function setFontFamily(string $fontFamily): self
    {
        $this->fontFamily = $fontFamily;
        return $this;
    }

    /**
     * 获取字体名称
     *
     * @return string
     */
    public function getFontFamily(): string
    {
        return $this->fontFamily;
    }

    /**
     * 设置是否加粗
     *
     * @param bool $bold 是否加粗
     * @return self
     */
    public function setBold(bool $bold): self
    {
        $this->bold = $bold;
        return $this;
    }

    /**
     * 获取是否加粗
     *
     * @return bool
     */
    public function isBold(): bool
    {
        return $this->bold;
    }

    /**
     * 设置是否斜体
     *
     * @param bool $italic 是否斜体
     * @return self
     */
    public function setItalic(bool $italic): self
    {
        $this->italic = $italic;
        return $this;
    }

    /**
     * 获取是否斜体
     *
     * @return bool
     */
    public function isItalic(): bool
    {
        return $this->italic;
    }

    /**
     * 设置字段值
     *
     * @param string|null $value 字段值
     * @return self
     */
    public function setValue(?string $value): self
    {
        $this->value = $value;
        return $this;
    }

    /**
     * 获取字段值
     *
     * @return string|null
     */
    public function getValue(): ?string
    {
        return $this->value;
    }

    /**
     * 转换为数组
     *
     * @return array
     */
    public function toArray(): array
    {
        $data = [
            'id' => $this->id,
            'label' => $this->getLabel(),
            'x' => $this->x,
            'y' => $this->y,
            'width' => $this->width,
            'height' => $this->height,
            'font_size' => $this->fontSize,
            'align' => $this->align,
            'font_family' => $this->fontFamily,
            'bold' => $this->bold,
            'italic' => $this->italic,
            'value' => $this->value,
            'type' => $this->type,
            'border_width' => $this->borderWidth,
            'border_color' => $this->borderColor,
            'background_color' => $this->backgroundColor,
            'rotation' => $this->rotation,
            'show_label' => $this->showLabel,
            'barcode_type' => $this->barcodeType,
            'barcode_module_width' => $this->barcodeModuleWidth,
            'barcode_height' => $this->barcodeHeight,
            'qrcode_size' => $this->qrcodeSize,
            'qrcode_error_correction' => $this->qrcodeErrorCorrection,
            'text_color' => $this->textColor,
            'label_font_size' => $this->labelFontSize,
            'label_font_family' => $this->labelFontFamily,
            'label_color' => $this->labelColor,
            'label_align' => $this->labelAlign,
            'padding' => $this->padding,
            'label_position' => $this->labelPosition
        ];

        // 只有在需要时才添加特定类型的属性
        if ($this->type === 'barcode' || $this->type === 'qrcode') {
            $data['barcode_type'] = $this->barcodeType;
        }

        if ($this->type === 'image') {
            $data['image_path'] = $this->imagePath;
        }

        return $data;
    }

    /**
     * 从数组创建字段
     *
     * @param string $id 字段ID
     * @param array $data 字段数据
     * @return self
     */
    public static function fromArray(string $id, array $data): self
    {
        // 创建字段实例
        $field = new self($id);
        
        // 设置基本属性
        $field->setLabel($data['label'] ?? '');
        $field->setX($data['x'] ?? 0);
        $field->setY($data['y'] ?? 0);
        $field->setWidth($data['width'] ?? 100);
        $field->setHeight($data['height'] ?? 30);
        $field->setFontSize($data['font_size'] ?? 12);
        $field->setAlign($data['align'] ?? 'left');
        $field->setFontFamily($data['font_family'] ?? 'Arial');
        $field->setBold($data['bold'] ?? false);
        $field->setItalic($data['italic'] ?? false);
        $field->setValue($data['value'] ?? '');
        $field->setType($data['type'] ?? 'text');
        $field->setBarcodeType($data['barcode_type'] ?? 'code128');
        $field->setImagePath($data['image_path'] ?? '');
        
        // 设置新增的边框和样式属性
        $field->setBorderWidth($data['border_width'] ?? 0);
        $field->setBorderColor($data['border_color'] ?? '#000000');
        $field->setBackgroundColor($data['background_color'] ?? 'transparent');
        $field->setRotation($data['rotation'] ?? 0);
        $field->setShowLabel($data['show_label'] ?? true);
        
        // 设置条形码和二维码属性
        if (isset($data['barcode_module_width'])) {
            $field->setBarcodeModuleWidth($data['barcode_module_width']);
        }
        if (isset($data['barcode_height'])) {
            $field->setBarcodeHeight($data['barcode_height']);
        }
        if (isset($data['qrcode_size'])) {
            $field->setQrcodeSize($data['qrcode_size']);
        }
        if (isset($data['qrcode_error_correction'])) {
            $field->setQrcodeErrorCorrection($data['qrcode_error_correction']);
        }
        
        // 设置文本样式
        if (isset($data['text_color'])) {
            $field->setTextColor($data['text_color']);
        }
        if (isset($data['label_font_size'])) {
            $field->setLabelFontSize($data['label_font_size']);
        }
        if (isset($data['label_font_family'])) {
            $field->setLabelFontFamily($data['label_font_family']);
        }
        if (isset($data['label_color'])) {
            $field->setLabelColor($data['label_color']);
        }
        if (isset($data['label_align'])) {
            $field->setLabelAlign($data['label_align']);
        }
        
        // 设置布局属性
        if (isset($data['padding'])) {
            $field->setPadding($data['padding']);
        }
        if (isset($data['label_position'])) {
            $field->setLabelPosition($data['label_position']);
        }
        
        return $field;
    }

    /**
     * 设置字段类型
     *
     * @param string $type 字段类型
     * @return self
     */
    public function setType(string $type): self
    {
        $this->type = $type;
        return $this;
    }

    /**
     * 获取字段类型
     *
     * @return string
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * 设置条码类型
     *
     * @param string $barcodeType 条码类型
     * @return self
     */
    public function setBarcodeType(string $barcodeType): self
    {
        $this->barcodeType = $barcodeType;
        return $this;
    }

    /**
     * 获取条码类型
     *
     * @return string
     */
    public function getBarcodeType(): string
    {
        return $this->barcodeType;
    }

    /**
     * 设置图片路径
     *
     * @param string $imagePath 图片路径
     * @return self
     */
    public function setImagePath(string $imagePath): self
    {
        $this->imagePath = $imagePath;
        return $this;
    }

    /**
     * 获取图片路径
     *
     * @return string|null
     */
    public function getImagePath(): ?string
    {
        return $this->imagePath;
    }

    /**
     * 获取条形码模块宽度
     *
     * @return float
     */
    public function getBarcodeModuleWidth(): float
    {
        return $this->barcodeModuleWidth;
    }

    /**
     * 设置条形码模块宽度
     *
     * @param float $barcodeModuleWidth
     * @return self
     */
    public function setBarcodeModuleWidth(float $barcodeModuleWidth): self
    {
        $this->barcodeModuleWidth = $barcodeModuleWidth;
        return $this;
    }

    /**
     * 获取条形码高度
     *
     * @return float
     */
    public function getBarcodeHeight(): float
    {
        return $this->barcodeHeight;
    }

    /**
     * 设置条形码高度
     *
     * @param float $barcodeHeight
     * @return self
     */
    public function setBarcodeHeight(float $barcodeHeight): self
    {
        $this->barcodeHeight = $barcodeHeight;
        return $this;
    }

    /**
     * 获取二维码大小
     *
     * @return int
     */
    public function getQrcodeSize(): int
    {
        return $this->qrcodeSize;
    }

    /**
     * 设置二维码大小
     *
     * @param int $qrcodeSize
     * @return self
     */
    public function setQrcodeSize(int $qrcodeSize): self
    {
        $this->qrcodeSize = $qrcodeSize;
        return $this;
    }

    /**
     * 获取二维码错误修正级别
     *
     * @return string
     */
    public function getQrcodeErrorCorrection(): string
    {
        return $this->qrcodeErrorCorrection;
    }

    /**
     * 设置二维码错误修正级别
     *
     * @param string $qrcodeErrorCorrection
     * @return self
     */
    public function setQrcodeErrorCorrection(string $qrcodeErrorCorrection): self
    {
        $this->qrcodeErrorCorrection = $qrcodeErrorCorrection;
        return $this;
    }

    /**
     * 获取文本颜色
     *
     * @return string
     */
    public function getTextColor(): string
    {
        return $this->textColor;
    }

    /**
     * 设置文本颜色
     *
     * @param string $textColor
     * @return self
     */
    public function setTextColor(string $textColor): self
    {
        $this->textColor = $textColor;
        return $this;
    }

    /**
     * 获取标签字体大小
     *
     * @return int
     */
    public function getLabelFontSize(): int
    {
        return $this->labelFontSize;
    }

    /**
     * 设置标签字体大小
     *
     * @param int $labelFontSize
     * @return self
     */
    public function setLabelFontSize(int $labelFontSize): self
    {
        $this->labelFontSize = $labelFontSize;
        return $this;
    }

    /**
     * 获取标签字体
     *
     * @return string
     */
    public function getLabelFontFamily(): string
    {
        return $this->labelFontFamily;
    }

    /**
     * 设置标签字体
     *
     * @param string $labelFontFamily
     * @return self
     */
    public function setLabelFontFamily(string $labelFontFamily): self
    {
        $this->labelFontFamily = $labelFontFamily;
        return $this;
    }

    /**
     * 获取标签颜色
     *
     * @return string
     */
    public function getLabelColor(): string
    {
        return $this->labelColor;
    }

    /**
     * 设置标签颜色
     *
     * @param string $labelColor
     * @return self
     */
    public function setLabelColor(string $labelColor): self
    {
        $this->labelColor = $labelColor;
        return $this;
    }

    /**
     * 获取标签对齐方式
     *
     * @return string
     */
    public function getLabelAlign(): string
    {
        return $this->labelAlign;
    }

    /**
     * 设置标签对齐方式
     *
     * @param string $labelAlign
     * @return self
     */
    public function setLabelAlign(string $labelAlign): self
    {
        $this->labelAlign = $labelAlign;
        return $this;
    }

    /**
     * 获取内边距
     *
     * @return array
     */
    public function getPadding(): array
    {
        return $this->padding;
    }

    /**
     * 设置内边距
     *
     * @param array $padding
     * @return self
     */
    public function setPadding(array $padding): self
    {
        $this->padding = array_merge(
            ['top' => 2, 'right' => 2, 'bottom' => 2, 'left' => 2], 
            $padding
        );
        return $this;
    }

    /**
     * 获取标签位置
     *
     * @return string
     */
    public function getLabelPosition(): string
    {
        return $this->labelPosition;
    }

    /**
     * 设置标签位置
     *
     * @param string $labelPosition
     * @return self
     */
    public function setLabelPosition(string $labelPosition): self
    {
        $this->labelPosition = $labelPosition;
        return $this;
    }
}
