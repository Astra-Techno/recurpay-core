<?php
namespace AstraTech\DataForge\Base;

use Illuminate\Support\Facades\Validator;

abstract class Entity extends ClassObject
{
	private $_data = [];

    // Magic method to set dynamic properties
    public function __set($name, $value)
    {
        $this->_data[$name] = $value;
    }

    final function getClassName()
    {
        return str_replace('App\DataForge\\', '', get_class($this));
    }

    // Magic method to get dynamic properties
    public function __get($name)
    {
        return $this->get($name, true) ?? null;
    }

    public function __isset($name)
	{
		return !empty($this->get($name, false));
	}

	final function get($name, $raiseError = false)
	{
		if (property_exists((object)$this->_data, $name))
            return $this->_data[$name];
        else if (isset($this->_data['_method'][$name]))
            return $this->_data['_method'][$name];

        $method = 'get'.$name;
		if (!method_exists($this, $method)) {
			if ($raiseError)
	            $this->raiseError($name." - property not found in class ".$this->className);
	        return null;
        }

        $this->_data['_method'][$name] = $this->$method();

        return $this->_data['_method'][$name];
    }

    abstract function init($args);

	final function bindInitProperties($array)
	{
		// Assigning dynamic properties to the object
		foreach ($array as $key => $value) {
			$this->$key = $value;
		}
	}

	function bind($array)
	{
		$this->bindInitProperties($array);
	}

	final function validate($rules, $data = [])
	{
		if (!$rules) {
			$this->setError('Validate rules empty!');
			return false;
		}

		$data = empty($data) ? $this->_data : $data;
		if (!$data) {
			$this->setError('Empty data to validate!');
			return false;
		}

		// Validate the data
		$validator = Validator::make($data, $rules);
		if ($validator->fails()) {
            $this->setError(implode("\n", $validator->errors()->all()));
			return false;
		}

		return true;
	}

	final function MediaSave($record_id)
	{
		// Verify media section defined in entity.
		if (!method_exists($this, 'getMediaSection')) {
			$this->setError('Media section not defined in '.$this->className);
			return false;
		}

		// Verify media types defined in entity.
		if (!method_exists($this, 'getMediaTypes')) {
			$this->setError('Media types not defined in '.$this->className);
			return false;
		}

		$types = [];
		foreach ($this->MediaTypes as $type) 
		{
			if (empty($this->_data[$type]))
				continue;

			// Load media type.
			$mediaType = DataForge::getSystemMediaType(['section' => $this->MediaSection, 'name' => $type]);
			if (!$mediaType) {
				$this->setError('Media section ('.$this->MediaSection.') & type ('.$type.') not configured in system!');
				return false;
			}

			// Copy media record from temp table.
			if (!$mediaType->copyMedia($this->_data[$type], $record_id)) {
				$this->setError($mediaType->getError());
				return false;
			}

			$types[] = $type;
		}

		return array_unique($types);
	}

    final function unset($args)
    {
        $args = (array) $args;
        foreach ($args as $arg) {
            if (isset($this->_data[$arg]))
                unset($this->_data[$arg]);
            else if (isset($this->_data['_method'][$arg]))
                unset($this->_data['_method'][$arg]);
        }
    }

    final function reset()
    {
        $this->_data['_method'] = [];
    }

	function _getPropertyMethods()
	{
		$methods1 = get_class_methods($this);
		$methods2 = DataForge::classMethods('AstraTech\DataForge\Base\ClassObject');
		$methods3 = DataForge::classMethods('AstraTech\DataForge\Base\Entity');

		$methods1 = array_diff($methods1, $methods2);
		$methods1 = array_diff($methods1, $methods3);

		$methods = array();
		foreach ($methods1 as $method)
		{
			if (stripos($method, 'get') !== 0)
				continue;

			if ($method = preg_replace('/get/', '', $method, 1))
				$methods[] = lcfirst($method);
		}

		return $methods;
	}

    final function getBaseAttribs()
    {
        $base = $this->_data;
 		if (isset($base['_method']))
 			unset($base['_method']);

 		return $base;
    }

 	function toArray($attrbs = [], $withBase = true)
 	{
 		$data = $withBase ? $this->getBaseAttribs() : [];
 		if (!$attrbs)
 			return $data;

 		if ($attrbs == 'all')
 			$attrbs = $this->_getPropertyMethods();
 		else if (!is_array($attrbs))
 			$attrbs = DataForge::split($attrbs);

        foreach ($attrbs as $attrb)
        {
			$tmp = $this->$attrb;
            if (is_object($tmp) && strpos(get_class($tmp), 'App\DataForge\Entity\\') !== false)
                $tmp = $tmp->toArray();
            else if (is_array($tmp) && !empty($tmp[0]))
            {
            	$sub = [];
				foreach ($tmp as $key => $val) {
					if (is_object($val) && strpos(get_class($val), 'App\DataForge\Entity\\') !== false)
					    $val = $val->toArray();

					$sub[$key] = $val;
				}

				$tmp = $sub;
            }

            $data[$attrb] = $tmp;
        }

		return $data;
 	}

    function toGroupArray($group, $attribs, $withBase = false)
 	{
        if (is_array($attribs))
            $attribs = implode(',', $attribs);

 		if (method_exists($this, 'attribGroups')) {
            $groups = $this->attribGroups();
            if (isset($groups[$group]))
                $attribs .= ','.$groups[$group];
        }

        $attribs = DataForge::split($attribs);
		return $this->toArray($attribs, $withBase);
 	}

    final function TableSave($array, $table, $keys = 'id')
    {
        $array = Table::save($array, $table, $keys);
        if (!$array) {
            $this->setError(Table::getError());
			return false;
        }

        return $array;
    }

    final function TableDelete($array, $table, $keys = 'id')
    {
        $out = \Table::delete($array, $table, $keys);
        if (!$out) {
            $this->setError(\Table::getError());
			return false;
        }

        return $out;
    }
}