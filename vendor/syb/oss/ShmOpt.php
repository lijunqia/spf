<?php
//**********************************************************
// File name: ShmOpt.class.php
// Class name: ShmOpt
// Create date: 2011/11/03
// Update date: 2011/11/03
// Author: parkerzhu
// Description: 共享内存操作封装
//**********************************************************
namespace syb\oss;
/**
 * ShmOpt类，封装共享内存基本操作
 * @author parkerzhu
 *
 */
class ShmOpt
{
    const DEFAULT_SHM_SIZE = 1024;
    const DEFAULT_SHM_MODE = 0666;
    const DEFAULT_PROJ_ID = 1;

    private $_proj_id;
    private $_shm_size;
    private $_path_name;
    private $_shm_id;
    private $_shm_key;

    /**
     * 构造函数，初始化路径名，项目名及共享内存大小
     *
     * @param $path_name string 路径
     * @param int $proj_id int 项目名
     * @param int $shm_size int 共享内存大小
     */
    public function __construct( $path_name, $proj_id = self::DEFAULT_PROJ_ID, $shm_size = self::DEFAULT_SHM_SIZE )
    {
        $this->_path_name = $path_name;
        $this->_proj_id = $proj_id;
        $this->_shm_size = $shm_size;
        $this->init();
    }

    /**
     * 初始化工作，创建共享内存key及id
     */
    public function init()
    {
        if(!function_exists('shmop_open'))throw new \Exception('shmop extension is not loaded.');
        $this->_shm_key = $this->get_shm_key();
        if($this->_shm_key === -1) return;
        $this->_shm_id = $this->shm_create($this->_shm_key, $this->_shm_size);
        if($this->_shm_id === FALSE) return;
    }

    /**
     * 生成共享内存key
     * @return string|int 成功返回共享内存key，失败返回 -1
     */
    public function get_shm_key()
    {
        return \ftok($this->_path_name, \pack("c", $this->_proj_id));
    }

    public function get_shm_id()
    {
        return $this->_shm_id;
    }

    public function get_shm_status()
    {
        return \intval($this->_shm_key);
    }

    /**
     * 创建一块共享内存，如果存在，则以读写方式打开，如不存在，则创建一块
     * @param $shm_key string 共享内存key
     * @param $shm_size int 共享内存大小
     * @return bool|int 成功返回共享内存id，失败返回FALSE
     */
    public function shm_create($shm_key, $shm_size)
    {
        $shmid = $this->shm_open($shm_key);
        if(!$shmid)
        {
            $shmid = \shmop_open($shm_key, "n", self::DEFAULT_SHM_MODE, $shm_size);
//          printf( "failed to create shm %s(%d)\n ", posix_strerror(posix_errno()), posix_errno());
            if(!$shmid) return false;
        }
        return $shmid;
    }

    /**
     * 读写方式打开一块共享内存
     * @param $shm_key string 共享内存key
     * @return bool|int 成功返回共享内存id，失败返回FALSE
     */
    public function shm_open($shm_key)
    {
        $shmid = \shmop_open($shm_key, "w", 0, 0);
        return $shmid?:false;
    }

    /**
     * 往共享内存写入一块内容
     * @param $shmid int 共享内存id
     * @param $message string 要写入的内容
     * @param $offset int 写入共享内存的偏移
     * @return bool|int 成功返回写入内容的大小，失败返回FALSE
     */
    public function shm_write($shmid, $message, $offset = 0)
    {
        if(!$shmid) return FALSE;
        return shmop_write($shmid, $message, $offset);
    }

    /**
     * 读取一块共享内存
     * @param $shmid int 共享内存id
     * @param $offset int 读取的共享内存的偏移
     * @param $size int 读取大小
     * @return bool|string 成功返回字符串，失败返回FALSE
     */
    public function shm_read($shmid, $offset = 0, $size = self::DEFAULT_SHM_SIZE)
    {
        if(!$size) $size = \shmop_size($shmid);
        return shmop_read($shmid, $offset, $size);
    }

    /**
     * 关闭一块共享内存（使$shmid失效，但不影响内存中的值）
     * @param $shmid int    共享内存id
     * @return bool
     */
    public function shm_close($shmid)
    {
        return ($shmid<0)?false:\shmop_close($shmid);
    }
    /**
     * 删除一块共享内存
     * @param $shmid    int    共享内存id
     * @return bool            成功返回TRUE，失败返回FALSE
     */
    public function shm_destroy($shmid)
    {
        return ($shmid<0)?false:\shmop_delete($shmid);
    }
}