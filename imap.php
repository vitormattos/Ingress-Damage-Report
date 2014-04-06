<?php
class imap {
	private $host;
	private $port;
	private $user;
	private $pass;
	private $ssl;
	private $imap;

	public function __construct($connect) {
		$this->host = $connect['host'];
		$this->port = $connect['port'];
		$this->user = $connect['user'];
		$this->pass = $connect['pass'];
		$this->ssl = $connect['ssl'];
		$this->imap = imap_open(
			"{{$this->host}:{$this->port}/imap".($this->ssl?'/'.$this->ssl:'')."}INBOX",
			$this->user,
			$this->pass
		);
	}

	public function search($criteria) {
		return  imap_search($this->imap, $criteria);
	}

	public function fetchHtmlBody($msg_number, $section = 2) {
		return imap_qprint(imap_fetchbody($this->imap, $msg_number, $section));
	}

	public function __destruct() {
		imap_close($this->imap);
	}
}