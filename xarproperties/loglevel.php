<?php
/**
 * Logconfig initialization functions
 *
 * @package modules
 * @copyright (C) 2002-2007 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Logconfig Module
 * @link http://xaraya.com/index.php/release/6969.html
 * @author Logconfig module development team
 */
/**
 * handle logging levels property
 * This should be in the 'Log Config' module. Alternative sources for dd properties
 * doesnt seem to be working yet?
 * @author nuncanada <nuncanada@xaraya.com>
 * @package dynamicdata
 */
sys::import('modules.dynamicdata.class.properties.base');

class LogLevelProperty extends DataProperty
{
    public $id   = 107;
    public $name = 'loglevel';
    public $desc = 'Logging Level';
    public $reqmodules = array('logconfig');

    public $options = array ('Emergency', 'Alert', 'Critical', 'Error', 'Warning', 'Notice', 'Info', 'Debug');
    public $value = array();

    function __construct(ObjectDescriptor $descriptor)
    {
        parent::__construct($descriptor);
        $this->template  = 'loglevel';
        $this->tplmodule = 'logconfig';
        $this->filepath   = 'modules/logconfig/xarproperties';
    }

    function validateValue($value = null)
    {
        if (!isset($value)) {
            $value = $this->value;
        }

        foreach ($this->options as $option) {
            if (($value[$option] != 'ON') && ($value[$option] != 'OFF'))
            {
                $this->invalid = xarML('selection');
                $this->value = null;
                return false;
            }
        }

        //HACK: Hack to allow to store the array:
        $this->value = serialize($value);

        return true;
    }

    public function showInput(Array $data = array())
    {
        extract($data);
        $data = array();

        if (!isset($value)) {
            $value = $this->value;
        }

        if (empty($name)) {
            $name = 'dd_'.$this->id;
        }

        //HACK: Hack to allow to store the array:
        if (!empty($value) && !is_array($value)) {$value = unserialize($value);}

        $data['value']   = $value;
        $data['name']    = $name;
        $data['tabindex'] =!empty($tabindex) ? ' tabindex="'.$tabindex.'" ' : '';
        $data['invalid']  =!empty($this->invalid) ? ' <span class="xar-error">'.xarML('Invalid #(1)', $this->invalid) .'</span>' : '';
        $data['options'] = $this->options;

        $template="";
        return xarTplProperty('logconfig', 'loglevel', 'showinput', $data);
    }

    public function showOutput(Array $data = array())
    {
        extract($data);
        if (!isset($value)) {
            $value = $this->value;
        }
        //$out = '';
        $data=array();

        //HACK: Hack to allow to store the array:
        if (!empty($value) && !is_array($value)) {$value = unserialize($value);}

        $data['value']   = $value;
        $data['options'] = $this->options;

        $template="";
        return xarTplProperty('logconfig', 'loglevel', 'showoutput', $data);
        // return $out;
    }
}

?>