<?php

namespace Able\Helpers\GlobalKnowledge;

class Fragment {

	protected $key = '/';
	protected $value = '';
	public $ttl = null;
	public $condition = array();

	public function __construct($key, $value)
	{
		$this->key = $key;
		$this->value = $value;
	}

	public static function get($key, array $flags = null)
	{
		$value = GlobalKnowledge::getInstance()->get($key, $flags);
		return new static($key, $value);
	}

	public static function update($key, $value, $ttl = null, $condition = array())
	{
		return GlobalKnowledge::getInstance()->set($key, $value, $ttl, $condition);
	}

	public function save()
	{
		return GlobalKnowledge::getInstance()->set($this->key, $this->value, $this->ttl, $this->condition);
	}

	public function delete()
	{
		return GlobalKnowledge::getInstance()->rm($this->key);
	}

	public function getKey()
	{
		return $this->key;
	}

	public function getValue()
	{
		return $this->value;
	}

	public function setValue($value, $save = true)
	{
		$this->value = $value;
		if ($save) {
			$this->save();
		}
	}

} 
