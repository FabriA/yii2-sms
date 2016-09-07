<?php
/**
 * Created by PhpStorm.
 * User: info_000
 * Date: 07.09.2016
 * Time: 12:02
 */

namespace fabria\sms\target;

interface Target
{
	/**
	 * @param array $mobiles
	 * @param string $message
	 *
	 * @return bool
	 */
	public function send($mobiles, $message);
}