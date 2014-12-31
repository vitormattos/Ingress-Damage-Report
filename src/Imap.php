<?php
namespace IngressSentinel;

class Imap {
    private $host;
    private $port;
    private $user;
    private $pass;
    private $ssl;
    private $imap;
    private $headers;

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
        if(!$this->imap) {
            throw new \Exception('Connection to IMAP account failure: '.imap_last_error());
        }
    }

    public function search($criteria) {
        return imap_search($this->imap, $criteria);
    }

    public function fetchHtmlBody($msg_number, $section = 2) {
        return imap_qprint(imap_fetchbody($this->imap, $msg_number, $section));
    }

    public function getHeaders($mailId) {
        $this->headers = imap_rfc822_parse_headers(imap_fetchheader($this->imap, $mailId, FT_UID));
        $this->headers->date = \DateTime::createFromFormat(\DateTime::RFC2822, $this->headers->date);
        $this->headers->date = $this->headers->date->format('Y-m-d H:i:s');
        return $this->headers;
    }

    public function __destruct() {
        imap_close($this->imap);
    }
}