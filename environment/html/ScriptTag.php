<?php

class ScriptTag extends Tag
{
		function ScriptTag($class= null)
		{
			Tag::Tag("script", true, $class);
			$this->isScript= true;
		}
		function type($value)
		{
			$this->insertAttribute("type", $value);
		}
		function getType()
		{
			return $this->aNames["type"];
		}
		function src($value)
		{
			$this->insertAttribute("src", $value);
		}
		function language($value)
		{
			$this->insertAttribute("language", $value);
		}
		function add($tag)
		{
			if(isset($this->aNames["src"]))
			{
				echo "\n<br><b>Error</b> ScriptTag::add()<b>:</b> when for an ScriptTag an src attribute is defined";
				echo "\n<br>                                      nothing should be inside this tag !!!";
				exit;
			}
			Tag::add($tag);
		}
		function bevorSubTags()
		{
			global	$tag_spaces,
					$HTML_CLASS_DEBUG_CONTENT;
			
			if($HTML_CLASS_DEBUG_CONTENT)
			{
				$this->spaces($tag_spaces);
				echo "<!--";
			}
		}
		function behindSubTags()
		{
			global	$tag_spaces,
					$HTML_CLASS_DEBUG_CONTENT;
			
			if($HTML_CLASS_DEBUG_CONTENT)
			{
				$this->spaces($tag_spaces);
				echo "//-->";
			}
		}
}

?>