<?php
//**********************************************************
// File name: CStruct.class.php
// Class name: StructBase
// Create date: 2011/02/18
// Update date: 2011/02/18
// Author: parkerzhu
// Description: C结构体类型基类
// Example:
//**********************************************************

namespace syb\oss;
// 结构体基类
// 所有模拟C的结构体都需要继承此类

class StructBase
{
    public function __toString()
    {
        return $this->pack();
    }

    public function __set($name, $value)
    {
        if (isset($this->$name)) {
            if ($this->$name instanceof CType) {
                if ($value instanceof CType)
                    $this->$name = $value;
                else
                    $this->$name->SetValue($value);
            } else {
                $this->$name = $value;
            }
        }
    }

    public function __get($name)
    {
        if (isset($this->$name)) {
            if ($this->$name instanceof CType || $this->$name instanceof StructBase) {
                return $this->$name;
            } else {
                return null;
            }
        }
        return null;
    }

    // 获取整个结构体的字节长度
    public function GetByteLength()
    {
        $fields = get_object_vars($this);
        $len = 0;
        foreach ($fields as $key => $value) {
            if (isset($value)) {
                if ($value instanceof CType || $value instanceof StructBase || $value instanceof TypeArray) {
                    $len += $value->GetByteLength();
                } else {
                    if (is_int($value)) {
                        $len += BYTE_OF_INT;
                    } else if (is_string($value)) {
                        $len += strlen($value);
                    }
                }
            }
        }
        return $len;
    }

    public function unpack(&$data)
    {
        $fields = get_object_vars($this);
        $offset = 0;
        foreach ($fields as $key => $value) {
            if ($value instanceof CType) {
                $len = $value->GetByteLength();
                $value->unpack(substr($data, $offset, $len));
                $offset += $len;
            } else if ($value instanceof StructBase || $value instanceof TypeArray) {
                $value->unpack(substr($data, $offset));
                $len = $value->GetByteLength();
                $offset += $len;
            } else {
                if (is_int($value)) {
                    $this->$key = unpack("I", substr($data, $offset, BYTE_OF_INT));
                    $offset += BYTE_OF_INT;
                }
                // 不支持，因为没法判断长度
                //if(is_string($value)) {
                //    $this->$key = unpack("I", substr($data, $offset, $len));
                //}
            }
        }
        return $offset;
    }

    public function &pack()
    {
        $fields = get_object_vars($this);
        $output = "";
        foreach ($fields as $key => $value) {
            if (isset($value)) {
                if ($value instanceof CType || $value instanceof StructBase) {
                    $output .= $value;
                } else {
                    if (is_int($value)) {
                        $output .= new OUInt(0, $value);
                    }
                    if (is_string($value)) {
                        $output .= new OString(strlen($value), $value);
                    }
                }
            }
        }
        return $output;
    }
}


class TypeArray implements Iterator, Countable, ArrayAccess
{
    private $_type;
    private $_values = array();

    public function __construct($type, $len = 0, $value = null)
    {
        if (!class_exists($type)) throw new \Exception("error create type array：invalid type!");
        $this->_type = $type;

        if ($len) {
            if (!isset($value)) {
                for ($i = 0; $i < $len; ++$i) {
                    $this->_values[] = $this->create_element();
                }
            } else {
                for ($i = 0; $i < $len; ++$i) {
                    $this->_values[] = unserialize(serialize($value));
                }
            }
        }
    }

    public function __toString()
    {
        return $this->pack();
    }

    public function GetByteLength()
    {
        $len = 0;
        foreach ($this->_values as $value) {
            $len += $value->GetByteLength();
        }
        return $len;
    }

    public function &GetValue()
    {
        return $this->_values;
    }

    public function &pack()
    {
        $data = "";
        foreach ($this->_values as $value) {
            $data .= $value->pack();
        }
        return $data;
    }

    public function unpack(&$data)
    {
        $offset = 0;
        foreach ($this->_values as &$value) {
            $value->unpack(substr($data, $offset));
            $offset += $value->GetByteLength();
        }

        return $this->GetByteLength();
    }

    public function create_element()
    {
        $elem = new $this->_type;
        return $elem;
    }

    public function offsetExists($offset)
    {
        return isset($this->_values[$offset]);
    }

    public function offsetSet($offset, $value)
    {
        if (is_null($offset)) {
            $this->_values[] = $value;
        } else {
            $this->_values[$offset] = $value;
        }
    }

    public function offsetGet($offset)
    {
        return isset($this->_values[$offset]) ? $this->_values[$offset] : null;
    }

    public function offsetUnset($offset)
    {
        unset($this->_values[$offset]);
    }

    public function rewind()
    {
        reset($this->_values);
    }

    public function current()
    {
        return current($this->_values);
    }

    public function key()
    {
        return key($this->_values);
    }

    public function next()
    {
        return next($this->_values);
    }

    public function valid()
    {
        return ($this->current() !== false);
    }

    public function count()
    {
        return count($this->_values);
    }
}

class LVInfo extends StructBase
{
    protected $length;
    protected $value;

    public function __construct($lengthType)
    {
        $this->length = new $lengthType();
    }

    public function GetValue()
    {
        return $this->value->GetValue();
    }

    public function unpack(&$data)
    {
        $offset = 0;
        $this->length->unpack($data);
        $offset += $this->length->GetByteLength();

        $this->value = new OString($this->length->GetValue());
        $this->value->unpack(substr($data, $offset, $this->length->GetValue()));
        return ($this->length->GetByteLength() + $this->value->GetByteLength());
    }

    public function &pack()
    {
        return $this->value->pack();
    }
}

class LVArray extends StructBase implements Iterator, Countable, ArrayAccess
{
    private $valueType;
    protected $length;
    protected $values;

    public function __construct($lengthType, $valueType)
    {
        $this->length = new $lengthType();
        $this->valueType = $valueType;
    }

    public function GetValue()
    {
        return $this->values;
    }

    public function unpack(&$data)
    {
        $offset = 0;
        $this->length->unpack($data);
        $offset += $this->length->GetByteLength();

        $this->values = new TypeArray($this->valueType, $this->length->GetValue());
        $this->values->unpack(substr($data, $offset));
        return ($this->length->GetByteLength() + $this->values->GetByteLength());
    }

    public function &pack()
    {
        return $this->values->pack();
    }

    public function offsetExists($offset)
    {
        return isset($this->values[$offset]);
    }

    public function offsetSet($offset, $value)
    {
        if (is_null($offset)) {
            $this->values[] = $value;
        } else {
            $this->values[$offset] = $value;
        }
    }

    public function offsetGet($offset)
    {
        return isset($this->values[$offset]) ? $this->values[$offset] : null;
    }

    public function offsetUnset($offset)
    {
        unset($this->values[$offset]);
    }

    public function rewind()
    {
        reset($this->values->GetValue());
    }

    public function current()
    {
        return current($this->values->GetValue());
    }

    public function key()
    {
        return key($this->values->GetValue());
    }

    public function next()
    {
        return next($this->values->GetValue());
    }

    public function valid()
    {
        return ($this->current() !== false);
    }

    public function count()
    {
        return count($this->values->GetValue());
    }
}
