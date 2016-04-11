<?php
//**********************************************************
// File name: HtmlTplFill.class.php
// Class name: HtmlTpl
// Create date: 2009/04/14
// Update date: 2009/04/14
// Author: garyzou
// Description: 模板填充类
//**
namespace syb\oss;
use syb\oss\Exception;
/**
 * 缺省标签前缀
 */
const DEFAULT_BEGIN_TAG = '<\%#';
/**
 * 缺省标签后缀
 */
const DEFAULT_END_TAG = '#\%>';

class HtmlTplFill
{
	private $FileName;
	private $BeginTag;
	private $EndTag;
	private $TplFillContent;
	private $OutContent;

	/*
	* \brief 初始化模板
	* \param[in] $filename 页面模版的路径
	* \throw Exception
	*/
	function __construct($filename, $sBeginTag = DEFAULT_BEGIN_TAG, $sEndTag = DEFAULT_END_TAG)
	{
		$this->BeginTag = $sBeginTag;
		$this->EndTag = $sEndTag;
		if(!file_exists($filename))
		{
			throw new Exception("Template File [$filename] Not Exists.\n");
		}
		$this->FileName = $filename;
		//把文件内容全部读入到
		$this->TplFillContent = file_get_contents($this->FileName);
	}

	/*!
	* \brief 填充指定的页面段
	* \param[in] sTagName 页面段标签。如果sTagName==""，则表示填充全部页面
	* \param[in] mValueMap 替换页面片中标记的值的map
	* \return 填充好的页面段。如果出错则返回string("")
	* \throw Exception
	* \note 出错时是否抛异常还是返回string("")是由库编译宏 OSS_NO_EXCEPTIONS 决定的
	*/
	public function Fill($sTagName, $ValueMap=NULL)
	{
		$Section = $this->GetSection($sTagName);
		if($ValueMap!=NULL && \is_array($ValueMap))
		{
			$this->FillSection($Section, $ValueMap);
		}
		return $Section;
	}

	/*!
	* \brief 填充指定的页面段
	* \param[in] sTagName 页面段标签。如果sTagName==""，则表示填充全部页面
	* \param[in] mValueMap 替换页面片中标记的值的map
	* \return 填充好的页面段。如果出错则返回string("")
	* \throw Exception
	* \note 出错时是否抛异常还是返回string("")是由库编译宏 OSS_NO_EXCEPTIONS 决定的
	*/
	public function GetOutContent()
	{
		return $this->OutContent;
	}

	 /*!
	* \brief 获取指定的页面模板段
	* \param[in] sTagName 页面段标签
	* \return 指定页面模板段。如果出错则返回string("")
	* \throw Exception
	* \note 出错时是否抛异常还是返回string("")是由库编译宏 OSS_NO_EXCEPTIONS 决定的
	*/
	private function GetSection($sTagName)
	{
		if($sTagName=="")
		{
			return $this->TplFillContent;
		}
		$FullTarget = ($this->BeginTag).$sTagName.($this->EndTag);
		$StartPos = \strpos($this->TplFillContent, $FullTarget, 0);
		if($StartPos === false)
		{
			throw new Exception("Begin Fill Target $FullTarget Cannot Find In Template Fill File.\n");
		}
		$StartPos = $StartPos+\strlen($FullTarget);
		$EndPos = \strpos($this->TplFillContent, $FullTarget, $StartPos);
		if($EndPos === false)
		{
			throw new Exception("End Fill Target $FullTarget Cannot Find In Template Fill File.\n");
		}
		return \substr($this->TplFillContent,$StartPos,($EndPos-$StartPos));
	}

	/*!
	* \brief 填充指定的页面段
	* \param[in] sSection 已经提取出来的页面模板
	* \param[in] mValueMap 替换页面片中标记的值的map
	* \return 填充好的页面段
	*/
	private function FillSection(&$Section, $ValueMap)
	{
		$keys = \array_keys($ValueMap);
		foreach($keys as $keyid => $keyname )
		{
			$Target = ($this->BeginTag).$keyname.($this->EndTag);
			$Section = \str_replace($Target, $ValueMap[$keyname], $Section);
		}
	}
}
