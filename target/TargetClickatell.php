<?php
/**
 * Created by PhpStorm.
 * User: info_000
 * Date: 07.09.2016
 * Time: 12:03
 */

namespace fabria\sms\target;

/**
 * http://www.phpclasses.org/package/1452-PHP-Send-worldwide-SMS-using-the-Clickatell-gateway.html
 * CLICKATELL SMS API
 *
 * This class is meant to send SMS messages via the Clickatell gateway
 * and provides support to authenticate to this service and also query
 * for the current account balance. This class use the fopen or CURL module
 * to communicate with the gateway via HTTP/S.
 *
 * For more information about CLICKATELL service visit http://www.clickatell.com
 *
 * @version   1.3d
 * @package   sms_api
 * @author    Aleksandar Markovic <mikikg@gmail.com>
 * @copyright Copyright © 2004, 2005 Aleksandar Markovic
 * @link      http://sourceforge.net/projects/sms-api/ SMS-API Sourceforge project page
 * @license   http://opensource.org/licenses/gpl-license.php GNU Public License
 *
 */

/**
 * Main SMS-API class
 *
 * Example:
 * <code>
 * <?php
 * require_once ("sms_api.php");
 * $mysms = new sms();
 * echo $mysms->session;
 * echo $mysms->getbalance();
 * $mysms->send ("38160123", "netsector", "TEST MESSAGE");
 * ?>
 * </code>
 *
 * @package sms_api
 */
class TargetClickatell extends Component implements Target
{
	/**
	 * Use SSL (HTTPS) protocol
	 *
	 * @var bool
	 */
	public $use_ssl = false;
	/**
	 * Define SMS balance limit below class will not work
	 *
	 * @var integer
	 */
	public $balace_limit = 0;
	/**
	 * Gateway command sending method (curl,fopen)
	 *
	 * @var mixed
	 */
	public $sending_method = "fopen";
	/**
	 * Optional CURL Proxy
	 *
	 * @var bool
	 */
	public $curl_use_proxy = false;
	/**
	 * Proxy URL and PORT
	 *
	 * @var mixed
	 */
	public $curl_proxy = "http://127.0.0.1:8080";
	/**
	 * Proxy username and password
	 *
	 * @var mixed
	 */
	public $curl_proxyuserpwd = "login:secretpass";
	/**
	 * Callback
	 * 0 - Off
	 * 1 - Returns only intermediate statuses
	 * 2 - Returns only final statuses
	 * 3 - Returns both intermediate and final statuses
	 *
	 * @var integer
	 */
	public $callback = 0;
	/**
	 * Session variable
	 *
	 * @var mixed
	 */
	public $session;
	public $base;
	public $base_s;
	/**
	 * Clickatell API-ID
	 *
	 * @link http://sourceforge.net/forum/forum.php?thread_id=1005106&forum_id=344522 How to get CLICKATELL API ID?
	 * @var integer
	 */
	private $api_id = "YOUR_CLICKATELL_API_NUMBER";
	/**
	 * Clickatell username
	 *
	 * @var mixed
	 */
	private $user = "YOUR_CLICKATELL_USERNAME";
	/**
	 * Clickatell password
	 *
	 * @var mixed
	 */
	private $password = "YOUR_CLICKATELL_PASSWORD";

	/**
	 * Class constructor
	 * Create SMS object and authenticate SMS gateway
	 *
	 * @return object New SMS object.
	 * @access public
	 */
	public function smsinit()
	{
		if ($this->use_ssl)
		{
			$this->base   = "http://api.clickatell.com/http";
			$this->base_s = "https://api.clickatell.com/http";
		}
		else
		{
			$this->base   = "http://api.clickatell.com/http";
			$this->base_s = $this->base;
		}

		$this->_auth();
	}

	/**
	 * Authenticate SMS gateway
	 *
	 * @return mixed  "OK" or script die
	 * @access private
	 */
	private function _auth()
	{
		$comm          = sprintf("%s/auth?api_id=%s&user=%s&password=%s", $this->base_s, $this->api_id, $this->user, $this->password);
		$this->session = $this->_parse_auth($this->_execgw($comm));
	}

	/**
	 * Parse authentication command response text
	 *
	 * @access private
	 */
	private function _parse_auth($result)
	{
		$session = substr($result, 4);
		$code    = substr($result, 0, 2);

		if ($code != "OK")
		{
			die ("SMS - Error in SMS authorization! ($result)");
		}

		return $session;
	}

	/**
	 * Execute gateway commands
	 *
	 * @access private
	 */
	private function _execgw($command)
	{
		if ($this->sending_method == "curl")
		{
			return $this->_curl($command);
		}

		if ($this->sending_method == "fopen")
		{
			return $this->_fopen($command);
		}

		die ("SMS - Unsupported sending method!");
	}

	/**
	 * CURL sending method
	 *
	 * @access private
	 * @return result
	 */
	private function _curl($command)
	{
		$this->_chk_curl();
		$ch = curl_init($command);
		curl_setopt($ch, CURLOPT_HEADER, 0);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);

		if ($this->curl_use_proxy)
		{
			curl_setopt($ch, CURLOPT_PROXY, $this->curl_proxy);
			curl_setopt($ch, CURLOPT_PROXYUSERPWD, $this->curl_proxyuserpwd);
		}

		$result = curl_exec($ch);
		curl_close($ch);

		return $result;
	}

	/**
	 * Check for CURL PHP module
	 *
	 * @access private
	 */
	private function _chk_curl()
	{
		if (!extension_loaded('curl'))
		{
			die ("This SMS API class can not work without CURL PHP module! Try using fopen sending method.");
		}
	}

	/**
	 * fopen sending method
	 *
	 * @access private
	 */
	private function _fopen($command)
	{
		$result  = '';
		$handler = @fopen($command, 'r');

		if ($handler)
		{
			while ($line = @fgets($handler, 1024))
			{
				$result .= $line;
			}
			fclose($handler);

			return $result;
		}
		else
		{
			die ("Error while executing fopen sending method!<br>Please check does PHP have OpenSSL support and check does PHP version is greater than 4.3.0.");
		}
	}

	/**
	 * Queries the message status
	 *
	 * @return string status information or erro
	 * @access public
	 */
	public function getmsgstatus($msgid)
	{
		$comm = sprintf("%s/querymsg?session_id=%s&apimsgid=%s", $this->base, $this->session, $msgid);

		return $this->_parse_getmsgstatus($this->_execgw($comm));
	}

	/**
	 * Parse getmsgstatus command response text
	 *
	 * @access private
	 */
	private function _parse_getmsgstatus($result)
	{
		$response = explode(' ', $result);

		if (substr($response[0], 0, 2) != "ID")
		{
			$response[1] = trim($response[1]);
			die ("SMS - Parse SMS status Error ($response[1])");
		}
		else
		{
			unset($response[0]);
			unset($response[2]);

			return array(trim($response[1]), trim($response[3]));
		}

	}

	/**
	 * Send SMS message
	 *
	 * @param to   mixed  The destination address.
	 * @param from mixed  The source/sender address
	 * @param text mixed  The text content of the message
	 *
	 * @return mixed  "OK" or script die
	 * @access public
	 */
	public function send($to = null, $from = null, $text = null, $params = array())
	{

		/* Check SMS credits balance */
		if ($this->getbalance() < $this->balace_limit)
		{
			die ("You have reach the SMS credit limit!");
		};

		/* Check SMS $text length */
		if (strlen($text) > 465)
		{
			die ("Your message is to long! (Current length=" . strlen($text) . ")");
		}

		/* Does message need to be concatenated */
		if (strlen($text) > 160)
		{
			$concat = "&concat=3";
		}
		else
		{
			$concat = "";
		}

		/* Check $to and $from is not empty */
		if (empty ($to))
		{
			die ("You not specify destination address (TO)!");
		}
		if (empty ($from))
		{
			die ("You not specify source address (FROM)!");
		}

		/* Reformat $to number */
		$cleanup_chr = array("+", " ", "(", ")", "\r", "\n", "\r\n", ".", ",", "-");
		$to          = str_replace($cleanup_chr, "", $to);
		$from        = str_replace($cleanup_chr, "", $from);

		if (sizeof($params) > 0)
		{
			$params = '&' . implode('&', $params);
		}
		else
		{
			$params = '';
		}

		//$textEncoded = htmlentities($text);
		$textEncoded = urlencode(utf8_decode($text));

		//exit;

		/* Send SMS now */
		$comm = sprintf("%s/sendmsg?session_id=%s&to=%s&from=%s&text=%s&callback=%s&unicode=1%s%s",
			$this->base,
			$this->session,
			urlencode($to),
			urlencode($from),
			//rawurlencode($text),
			$textEncoded,
			$this->callback,
			$concat,
			$params
		);

		return $this->_parse_send($this->_execgw($comm));
	}

	/**
	 * Query SMS credit balance
	 *
	 * @return integer  number of SMS credits
	 * @access public
	 */
	public function getbalance()
	{
		$comm = sprintf("%s/getbalance?session_id=%s", $this->base, $this->session);

		return $this->_parse_getbalance($this->_execgw($comm));
	}

	/**
	 * Parse getbalance command response text
	 *
	 * @access private
	 */
	private function _parse_getbalance($result)
	{
		$result = substr($result, 8);

		return (float) $result;
	}

	/**
	 * Parse send command response text
	 *
	 * @access private
	 */
	private function _parse_send($result)
	{
		$code = substr($result, 0, 2);

		if ($code != "ID")
		{
			return 'ERR:' . trim($result);
		}
		else
		{
			$id = substr($result, 4, strlen($result));

			return trim($id);
		}
	}

	public function delete($messageId)
	{
		/* Send DELETE command now */
		$comm = sprintf("%s/delmsg?session_id=%s&apimsgid=%s",
			$this->base,
			$this->session,
			$messageId
		);

		return $this->_parse_send($this->_execgw($comm));
	}

	public function statuscodes()
	{
		return array(
			'001' => 'Message unknown',
			'002' => 'Message queued',
			'003' => 'Message delivered to gateway',
			'004' => 'Message received by recipient',
			'005' => 'Error with message',
			'006' => 'User cancelled message delivery',
			'007' => 'Error delivering message to handset',
			'008' => 'Message received by gateway OK',
			'009' => 'Routing error',
			'010' => 'Message expired',
			'011' => 'Message queued for later delivery',
			'012' => 'Out of credit',
			'014' => 'Maximum MT limit exceeded',
		);
	}

	/**
	 * Basic setter
	 *
	 * @param $property
	 * @param $value
	 *
	 * @return $this
	 */
	public function set($property, $value)
	{
		$this->$property = $value;

		return $this;
	}

	/**
	 * Basic getter
	 *
	 * @param $property
	 *
	 * @return mixed
	 */
	public function get($property)
	{
		return $this->$property;
	}
}