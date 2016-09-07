<?php
/**
 * Created by Fabrizio Albonico.
 * Date: 07.09.2016
 * Time: 12:00
 */

namespace fabria\sms;

use Yii;
use yii\base\Component;
use yii\base\InvalidParamException;
use yii\base\Object;

class Sms extends Component
{
	public $targets = [];
	/**
	 * @inheritdoc
	 */
	public function init()
	{
		parent::init();
		if (empty($this->targets)) {
			throw new InvalidParamException("Please configure targets.");
		}
		foreach ($this->targets as $name => $target) {
			if (!$target instanceof Target) {
				$this->targets[$name] = Yii::createObject($target);
			}
		}
	}
	/**
	 * @param string $mobiles
	 * @param string $message
	 * @return bool
	 */
	public function send($mobiles, $message)
	{
		if (count($this->targets) == 0) {
			Yii::error('No sms targets configured', 'sms.send');
			return false;
		}
		$target = $this->targets[rand(0, count($this->targets) - 1)];
		if (!$ret = $target->send($mobiles, $message)) {
			if ($target instanceof Object) {
				Yii::error($target::className() . ' send ' . $mobiles . ' failed', 'sms.send');
			} else {
				Yii::error('Target send ' . $mobiles . ' failed', 'sms.send');
			}
			return false;
		}
		Yii::info($mobiles . ':' . $message, 'sms.send');
		return true;
	}
}