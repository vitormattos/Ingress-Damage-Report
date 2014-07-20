<?php

require 'imap.php';

$imap = new imap(array(
	'host' => 'imap.gmail.com', //gmail: imap.gmail.com
	'port' => '993',     //gmail: 993
	'user' => 'email@gmail.com',   //example@example.com
	'pass' => 'pass',       //mail pass
	'ssl'  => 'ssl'         //ssl
));

class ingress_damage_report {
	/**
	 * Email body
	 * @var DOMDocument
	 */
	private $body;
	/**
	 * All unread mail
	 * @var Stream
	 */
	private $reports;
	/**
	 * Database connection
	 * @var PDO
	 */
	private $db;


	public function __construct() {
		libxml_use_internal_errors(true);
		$this->body = new DOMDocument();
		$this->db = new PDO('sqlite:db.sqlite');
	}

	public function process() {
		//$search = $imap->search('UNSEEN');
		//$search = $imap->search('ALL');
		$this->reports  = $imap->search('UNSEEN SUBJECT "Ingress Damage Report"');
		// if mailbox is empty, display "no attacks", else, process mail attacks
		if($search < 1) {
			echo "no attacks\n";
		} else {
			$ingress_damage_report = new ingress_damage_report();
			foreach ($search as $msg_number) {
				$data = $ingress_damage_report->parseBody($msg_number);
				$this->save($data);
			}
		}
	}

	public function save($data) {
		$this->db->query('SELECT id FROM agent WHERE name = "'.$data['agent_name'].'"');
	}

	public function parseBody($msg_number) {
		$this->body->loadHTML('<html>'.$imap->fetchHtmlBody($msg_number).'</html>');
		$domxpath = new DOMXPath($this->body);
		// Agent info
		$nodeList = $domxpath->query('//div/table/tbody/tr[2]/td/table/tbody/tr/td/span');
		$data['agent_name'] = $nodeList->item(1)->nodeValue;
		$data['faction'] = $nodeList->item(3)->nodeValue;
		$data['current_level'] = $nodeList->item(5)->nodeValue;
		// Portal info
		$nodeList = $domxpath->query('//div/table/tbody/tr[2]/td/table/tbody/tr[3]/td/div');
		$data['portal_name'] = $nodeList->item(0)->nodeValue;
		$data['portal_address'] = $nodeList->item(1)->nodeValue;
		$data['portal_link'] = $nodeList->item(1)->getElementsByTagName('a')->item(0)->getAttribute('href');
		$portal_link = parse_url($data['portal_link']);
		$portal_link = $portal_link['query'];
		parse_str($portal_link, $portal_link);
		$portal_link['ll'] = explode(',', $portal_link['ll']);
		$data['portal_link_ll_s'] = $portal_link['ll'][0];
		$data['portal_link_ll_w'] = $portal_link['ll'][1];
		$portal_link['pll'] = explode(',', $portal_link['pll']);
		$data['portal_link_pll_s'] = $portal_link['pll'][0];
		$data['portal_link_pll_w'] = $portal_link['pll'][1];
		$data['portal_link_z'] = $portal_link['z'];
		// Damage
		$nodeList = $domxpath->query('//div/table/tbody/tr[2]/td/table/tbody/tr[5]/td/table/td/div');
		$data['enemy_agent'] = $nodeList->item(0)->getElementsByTagName('span')->item(0)->nodeValue;
		preg_match(
			'/DAMAGE:([0-9]).*at ([0-9]{1,2}:[0-9]{1,2}).*GMT([0-9]|No)/',
			$nodeList->item(0)->nodeValue,
			$matches
		);
		$data['ressonator_destroyed'] = $matches[1];
		$data['time'] = $matches[2];
		$data['ressonator_remaining'] = $matches[3]=='No'?0:$matches[3];
		// Status
		$nodeList = $domxpath->query('//div/table/tbody/tr[2]/td/table/tbody/tr[5]/td/table/td[2]/div');
		preg_match(
			'/Level ([0-9])Health: ([0-9]{0,})%Owner: (.*)/',
			$nodeList->item(0)->nodeValue,
			$matches
		);
		$data['portal_level'] = $matches[1];
		$data['portal_health'] = $matches[2];
		$data['portal_owner'] = $matches[3];
		return $data;
	}
}