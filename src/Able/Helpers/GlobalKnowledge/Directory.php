<?php

namespace Able\Helpers\GlobalKnowledge;

class Directory {

	protected $key = '/';
	public $ttl = null;
	public $condition = array();

	public function __construct($key)
	{
		$this->key = $key;
	}

	public function children($type = null)
	{
		$response = GlobalKnowledge::getInstance()->listDir($this->key);
		$children = array();
		if (!empty($response->node->nodes)) {
			foreach ($response->node->nodes as $node) {
				if (!empty($node->dir) && ($type == 'directories' || $type == null)) {
					$children[] = new Directory($node->key);
				} elseif ($type == 'fragments' || $type == null) {
					$children[] = new Fragment($node->key, $node->value);
				}
			}
		}
		return $children;
	}

	public function fragments()
	{
		return $this->children('fragments');
	}

	public function directories()
	{
		return $this->children('directories');
	}

} 
