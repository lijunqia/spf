<?php
namespace syb\oss;
const BYTE_OF_INT = PHP_INT_SIZE;//64位系统下为8,32位系统下为4
const BYTE_OF_MACHINE = PHP_INT_SIZE * 8;

const BYTE_OF_DOUBLE = BYTE_OF_INT * 2;

// 定义C类型，长度及对应的pack类型
const ARRAY_OTYPES = [
    array('name' => 'Char', 'type' => 'c', 'bytes' => 1),
    array('name' => 'UChar', 'type' => 'C', 'bytes' => 1),
    array('name' => 'String', 'type' => 'a', 'bytes' => 1),
    array('name' => 'STString', 'type' => 'A', 'bytes' => 1),
    array('name' => 'Short', 'type' => 's', 'bytes' => 2),
    array('name' => 'UShort', 'type' => 'S', 'bytes' => 2),
    array('name' => 'BEUShort', 'type' => 'n', 'bytes' => 2),
    array('name' => 'LEUShort', 'type' => 'v', 'bytes' => 2),
    array('name' => 'Int', 'type' => 'i', 'bytes' => BYTE_OF_INT),
    array('name' => 'UInt', 'type' => 'I', 'bytes' => BYTE_OF_INT),
    array('name' => 'Long', 'type' => 'l', 'bytes' => 4),
    array('name' => 'ULong', 'type' => 'L', 'bytes' => 4),
    array('name' => 'BEULong', 'type' => 'N', 'bytes' => 4),
    array('name' => 'LEULong', 'type' => 'V', 'bytes' => 4),
    array('name' => 'Float', 'type' => 'f', 'bytes' => BYTE_OF_INT),
    array('name' => 'Double', 'type' => 'd', 'bytes' => BYTE_OF_DOUBLE)
];

const STRING_OTYPES = 'cCaAsSnviIlLNVfd';

// 类定义模板
define('OCLASS_DEFINE_TEMPLATE', 'class O#name# extends CType {
  public function __construct($length = 1, $value = "") {
      parent::__construct("#type#", $value, $length);
  }
  public function UnitSize() {
      return #bytes#;
  }
}');

// C类型基类
class CType
{
    private $type;
    private $value;
    private $length;

    public function __construct($type, $value = 0, $length = 1)
    {
        $this->Set($value, $type, $length);
    }

    public function __toString()
    {
        return $this->pack();
    }

    public function GetType()
    {
        return $this->type;
    }

    // 获取原始值结果
    public function GetValue()
    {
        return $this->value;
    }

    // 获取元素个数
    public function GetLength()
    {
        return $this->length;
    }

    public function SetType($type)
    {
        if (strpos(STRING_OTYPES, $type) === false) $type = 'a';
        $this->type = $type;
    }

    public function SetValue($value)
    {
        if (!isset($value)) $value = 0;
        if (!is_array($value) && $this->type != 'a' && $this->length > 1) {
            $newValue = array();
            for ($i = 0; $i < $this->length; $i++) {
                $newValue[] = $value;
            }
            $value = $newValue;
        }
        $this->value = $value;
    }

    public function SetLength($length)
    {
        if ($length < 0) $length = 1;
        $this->length = $length;
    }

    public function Set($value, $type, $length)
    {
        $this->SetType($type);
        $this->SetLength($length);
        $this->SetValue($value);
    }

    // 获取此类型的总字节长度
    public function GetByteLength()
    {
        return $this->GetLength() * $this->UnitSize();
    }

    // 获取此类型的单位长度
    public function UnitSize()
    {
        return 1;
    }

    // 根据数据类型打包此数据，用于发包
    public function &pack()
    {
        $data = '';
        if ($this->length == 1 || ($this->type == 'a' && $this->length == 0)) {
            $data = @pack($this->type . '*', $this->value);
        } else {
            if ($this->type == 'a') {
                $data = @pack('a' . $this->length, $this->value);
            } else {
                eval('$data = @pack("' . $this->type . $this->length . '", ' . implode(',', $this->value) . ');');
            }
        }
        return $data;
    }

    // 根据数据类型从某数据解包，并可使用GetValue获取结果
    public function unpack(&$data)
    {
        if ($this->length == 0) return;
        if ($this->length == 1 || ($this->type == 'a' && $this->length == 0)) {
            $value = @unpack($this->type . '*', $data);
            if (!empty($value)) $this->value = $value[1];
        } else {
            if ($this->type == 'a') {
                $value = @unpack('a' . $this->length, $data);
                if ($value !== false) $this->value = $value[1];
            } else {
                $value = @unpack($this->type . $this->length, $data);
                if ($value !== false) {
                    $this->value = array();
                    foreach ($value as $val) {
                        $this->value[] = $val;
                    }
                }
            }
        }
    }
}

// 通过eval从模版创建类
function OCreateType($type)
{
    $defination = str_replace('#name#', $type['name'], OCLASS_DEFINE_TEMPLATE);
    $defination = str_replace('#type#', $type['type'], $defination);
    $defination = str_replace('#bytes#', $type['bytes'], $defination);
    eval($defination);
}

function OCreateTypes(array $otypes)
{
    foreach ($otypes as $type) {
        OCreateType($type);
    }
}

OCreateTypes(ARRAY_OTYPES);