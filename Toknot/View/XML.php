<?php

/**
 * Toknot (http://toknot.com)
 *
 * @copyright  Copyright (c) 2011 - 2013 Toknot.com
 * @license    http://toknot.com/LICENSE.txt New BSD License
 * @link       https://github.com/chopins/toknot
 */

namespace Toknot\View;

use Toknot\Di\Object;
use \SplFixedArray;
use Toknot\Exception\StandardException;

class XML extends Object {

	private $encode = 'utf-8';

	public static function singleton() {
		parent::__singleton();
	}

	public function setEncoding($encode = 'utf=8') {
		$this->encode = $encode;
	}

	public function parseFile($file, $compliefile) {
		$fileSize = filesize($file);
		$expectLineNumber = $fileSize / 40;
		$fp = fopen($file, 'r');
		$writeFile = fopen($compliefile, 'w');
		$i = 0;
		$xml = "<?xml version=\"1.0\" encoding==\"{$this->encode}\" ?>";
		fwrite($writeFile, $xml, strlen($xml));
		$indentArray = new SplFixedArray($expectLineNumber);
		$readSize = 0;
		if ($fp) {
			while (($line = fgets($fp)) !== false) {
				$lineSize = strlen($line);
				$readSize += $lineSize;
				if(substr(trim($line),0,1) == '{') {
					$startControl = $line;
					continue;
				}
				$indentArray[$i] = array();
				if($startControl !== null) {
					$indentArray[$i]['control'] = $startControl;
					$startControl = null;
				}
				list($tagName, $value) = explode(trim($line), ' ', 2);
				if ($i === 0) {
					
					$xml = "<$tagName>$value";
					fwrite($writeFile, $xml, strlen($xml));
					$indentArray[$i]['indent'] = 0;
					$indentArray[$i]['tag'] = $tagName;
				} else {

					$indentArray[$i]['tag'] = $tagName;
					$indentArray[$i]['indent'] = strlen($line) - strlen(ltrim($line));
					if ($indentArray[$i]['indent'] % $indentArray[1]['indent'] !== 0) {
						throw new StandardException('XML template indent error in line ' . $i + 1);
					}
					if ($indentArray[$i]['indent'] >= $indentArray[$i - 1]['indent']) {
						$j = 0;
						$xml = '';
						while (true) {
							$j++;
							$key = $i - $j;
							if ($key < 0) {
								break;
							}
							if ($indentArray[$key] === 0) {
								continue;
							}
							if ($indentArray[$i]['indent'] >= $indentArray[$key]['indent']) {
								$xml .= "</{$indentArray[$key]['tag']}>";
								$indentArray[$key] = 0;
							} else {
								break;
							}
						}
						$xml .= "<$tagName>$value";
						fwrite($writeFile, $xml, strlen($xml));
					}
				}
				if ($readSize >= $fileSize) {
					break;
				}
				$i++;
			}
			$j = 0;
			$size = $indentArray->getSize();
			$xml  = '';
			while (true) {
				$j++;
				$key = $size - $j;
				if ($key < 0) {
					break;
				}
				if ($indentArray[$key] === 0) {
					continue;
				}
				$xml .= "</{$indentArray[$key]['tag']}>";
			}
		}
	}

	/**
	  TAG	value
		TAG1 value
			{foreach }
			TAG2 value
	 *		{/foreach}
			TAG3 value
		



	  <TAG>value<TAG1>value<TAG2>value</TAG2>
	 */
}
