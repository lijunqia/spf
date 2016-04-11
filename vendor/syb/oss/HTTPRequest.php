<?php
namespace syb\oss;
/**
 * 封装http请求类
 * @author parkerzhu
 *
 */
class HTTPRequest
{
    private $errorno;
    private $errormsg;
    private $response;
    private $header;

    private function _request($option, $options)
    {
        $curl = \curl_init();
        if (isset($curl)) {
            if (!empty($options)) {
                foreach ($options as $key => $value) {
                    $option[$key] = $value;
                }
            }

            \curl_setopt_array($curl, $option);

            $this->response = \curl_exec($curl);
            $this->errorno = \curl_errno($curl);
            $this->errormsg = \curl_error($curl);
            $this->header = \curl_getinfo($curl);
            \curl_close($curl);
        }
        return $curl;
    }

    /**
     * 发http POST请求
     * @param $url 请求的链接
     * @param $post_data post数据
     * @param $options 可选的curl参数
     */
    public function Post($url, $post_data = "", $options = array())
    {
        $option = array(
            CURLOPT_POST => 1,
            CURLOPT_URL => $url,
            CURLOPT_POSTFIELDS => $post_data,
            CURLOPT_HEADER => 0,
            CURLOPT_RETURNTRANSFER => 1
        );
        return $this->_request($option, $options);
    }

    /**
     * 发送http GET请求
     * @param $url 请求的链接
     * @param $options 可选的curl参数
     */
    public function Get($url, $options = array())
    {
        $option = array(
            CURLOPT_POST => 0,
            CURLOPT_URL => $url,
            CURLOPT_HEADER => 0,
            CURLOPT_RETURNTRANSFER => 1
        );
        return $this->_request($option, $options);
    }

    public function GetErrorNo()
    {
        return $this->errorno;
    }

    public function GetErrorMessage()
    {
        return $this->errormsg;
    }

    public function GetResponseHeader()
    {
        return $this->header;
    }

    public function GetResponse()
    {
        return $this->response;
    }
}
