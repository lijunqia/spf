<?php
//**********************************************************
// File name: Socket.class.php
// Class name: Socket
// Create date: 2011/3/28
// Update date: 2011/3/28
// Author: parkerzhu
// Description: socket类
//**********************************************************
namespace syb\oss\Network;
/**
 * Socket类，将原生socket封装成对象来操作，包含一个原始socket成员
 * @note 由于PHP的垃圾收集机制，请在使用完后及时调用unset，以释放socket句柄
 * @author parkerzhu
 *
 */
class Socket
{
    const INVALID_HANDLE = -1;
    /**
     * @desc 套接口类型 SOCK_STREAM or SOCK_DGRAM or SOCK_RAW
     */
    private $_iType;

    /**
     * @desc 地址族
     */
    private $_iFamily;

    /**
     * @desc 协议
     */
    private $_iProtocol;

    /**
     * @desc 套接字描述符
     */
    private $_iHandle;
    /**
     * @desc 构造函数
     * @param[in] iType 套接字类型  SOCK_STREAM 或 SOCK_DGRAM 或 SOCK_RAM
     * @param[in] iFamily 地址族
     * @param[in] iProtocol 同socket_create中的协议类型
     */
    //protected function __construct($iType, $iFamily = AF_INET, $iProtocol = 0)
    protected function __construct(array &$params = array('iType' => null,
        'iFamily' => AF_INET,
        'iProtocol' => 0))
    {
        $this->_iType = $params['iType'];
        $this->_iFamily = $params['iFamily'];
        $this->_iProtocol = $params['iProtocol'];
        $this->_iHandle = self::INVALID_HANDLE;
    }

    /**
     * @desc 析构函数，关闭套接字
     */
    protected function __destruct()
    {
        $this->Close();
    }

    /**
     * @desc 根据地址和端口打开一个套接字
     * @param[in] sAddr: 点分十进制表示的字符串地址或者一个已打开的socket
     * @param[in] iPort: 整型端口
     * @param[in] bReuseAddr: 是否重用地址
     * @return 0: 成功 -2: 已经打开 -1: 打开失败
     */
    protected function Open($sAddr = "0.0.0.0", $iPort = 0, $bReuseAddr = true)
    {
        $bIsResource = \is_resource($sAddr);

        if ($this->IsOpen()) {
            return -2;
        }

        // 如果是socket
        if ($bIsResource) {
            $this->_iHandle = $sAddr;
        } else {
            $sock = \socket_create($this->_iFamily, $this->_iType, $this->_iProtocol);
            if ($sock === false) {
                return -1;
            }
            $this->_iHandle = $sock;
        }

        if (!$this->IsOpen()) {
            return -1;
        }

        // 如果不是socket
        if (!$bIsResource) {
            if ($bReuseAddr) {
                if ($this->SetSockOption(SOL_SOCKET, SO_REUSEADDR, 1) === false) {
                    $this->Close();
                    return -1;
                }
            }

            if ($this->Bind($sAddr, $iPort) == -1) {
                $this->Close();
                return -1;
            }
        }
        return 0;
    }

    /**
     * @desc 设置套接字为阻塞或非阻塞
     * @param[in] $bBlock 是否阻塞方式
     * @return 0: 成功 -1: 失败
     */
    protected function SetBlock($bBlock)
    {
        if ($bBlock === true) {
            if (socket_set_block($this->_iHandle) === false) {
                return -1;
            }
        } else {
            if (socket_set_nonblock($this->_iHandle) === false) {
                return -1;
            }
        }
        return 0;
    }

    /**
     * @desc 获取原始套接字
     * @return 原始套接字
     */
    protected function GetHandle()
    {
        return $this->_iHandle;
    }

    /**
     * @desc 绑定本地地址
     * @param[in] sAddr: 要绑定的地址
     * @param[in] iPort: 要绑定的端口
     * @return 0: 成功 -1: 失败
     */
    private function Bind($sAddr, $iPort = 0)
    {
        if (socket_bind($this->_iHandle, $sAddr, $iPort) === false) {
            return -1;
        }
        return 0;
    }
    /**
     * @desc 关闭套接字
     * @return void
     */
    public function Close()
    {
        if ($this->IsOpen()) {
            \socket_close($this->_iHandle);
        }
        $this->_iHandle = self::INVALID_HANDLE;
    }

    /**
     * @desc 获取当前套接字本地inet地址
     * @param[out] sAddr: 点分格式表示的本地地址
     * @param[out] iPort: 本地端口
     * @return 0: 成功 -1: 失败
     */
    public function GetLocalAddr(&$sAddr, &$iPort)
    {
        if (\socket_getsockname($this->_iHandle, $sAddr, $iPort) === false) {
            return -1;
        }
        return 0;
    }

    /**
     * @desc 获取当前套接字本地inet地址
     * @param[out] sAddr: 点分格式表示的本地地址
     * @param[out] iPort: 本地端口
     * @return 0: 成功 -1: 失败
     */
    public function GetRemoteAddr(&$sAddr, &$iPort)
    {
        if (\socket_getpeername($this->_iHandle, $sAddr, $iPort) === false) {
            return -1;
        }
        return 0;
    }

    /**
     * @desc 套接字是否已经打开
     * @return true: 已经打开 false: 未打开
     */
    public function IsOpen()
    {
        return ($this->_iHandle !== self::INVALID_HANDLE);
    }

    /**
     * @desc 设置套接字的属性
     * @param[in] iLevel 属性级别
     * @param[in] iOption 属性名
     * @param[in] optval 属性值
     * @return 0: 正确  -1: 失败
     */
    public function SetSockOption($iLevel, $iOption, $optval)
    {
        if (\socket_set_option($this->_iHandle, $iLevel, $iOption, $optval) === false) {
            return -1;
        }
        return 0;
    }

    /**
     * @desc 获取当前套接字的属性
     * @param[in] iLevel 属性级别
     * @param[in] iOption 属性名
     * @param[out] optval 属性值
     * @return 0: 正确  -1: 失败
     */
    public function GetSockOption($iLevel, $iOption, &$optval)
    {
        $optval = \socket_get_option($this->_iHandle, $iLevel, $iOption);
        if ($optval === false) {
            return -1;
        }
        return 0;
    }

}
