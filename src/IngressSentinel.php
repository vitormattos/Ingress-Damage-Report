<?php
namespace IngressSentinel;

use ProgressBar\Manager;
use Symfony\Component\Yaml\Yaml;
class IngressSentinel {
    /**
     * Email body
     * @var \DOMDocument
     */
    private $body;
    /**
     * All unread mail
     * @var Stream
     */
    private $reports;
    /**
     * Database connection
     * @var \PDO
     */
    private $db;
    /**
     *
     * @var imap
     */
    private $imap;
    /**
     * Inform if the application is running in CLI mode
     * @var boolean
     */
    private $cli = false;


    public function __construct($config) {
        libxml_use_internal_errors(true);
        $this->body = new \DOMDocument();
        if( empty($_SERVER['REMOTE_ADDR']) and !isset($_SERVER['HTTP_USER_AGENT']) and count($_SERVER['argv']) > 0) {
            $this->cli = true;
        }
        $this->db = new \PDO($config['db']['dsn'], @$config['db']['user'], @$config['db']['pass']);

        try {
            $this->imap = new Imap($config['email']);
        } catch(Exception $e) {
            throw new \Exception('Connection failure');
        }
    }

    public function process($filter) {
        $this->reports  = $this->imap->search($filter);
        // if mailbox is empty, display "no attacks", else, process mail attacks
        if(count($this->reports) < 1) {
            echo "no attacks\n";
        } else {
            $progressBar = null;
            if($this->cli) {
                $progressBar = new Manager(0, count($this->reports));
            }
            foreach ($this->reports as $i => $msg_number) {
                if($progressBar) {
                    $progressBar->update($i);
                }
                $this->save($msg_number);
            }
        }
    }

    private function save($msg_number) {
        $this->headers = $this->imap->getHeaders($msg_number);
        $data = $this->parseBody($msg_number);
        $data['email'] = $this->headers->toaddress;
        $this->agent = $this->saveAgent($data);
        $this->owner = $this->saveOwner($data);
        $this->portal = $this->savePortal($data);
        $this->emeny = $this->saveEnemyAgent($data);
        $this->damage = $this->saveDamage($data);
    }

    private function saveAgent($data) {
        $agent = $this->db->query('SELECT * FROM agent WHERE name = "'.$data['agent_name'].'" LIMIT 1;');
        if(!$agent = $agent->fetch(\PDO::FETCH_ASSOC)) {
            $agent['faction_id'] = $data['faction'] == 'Resistance'?1:2;
            $agent['agent_name'] = $data['agent_name'];
            $agent['email'] = $data['email'];
            $agent['current_level'] = $data['current_level'];
            $this->db->query(
                'INSERT INTO agent(name, current_level, faction_id) '.
                'VALUES ("'.$data['agent_name'].'", "'.$data['current_level'].'", '.$agent['faction_id'].')'
            );
            $agent['id'] = $this->db->lastInsertId();
        } elseif($data['current_level'] && $agent['current_level'] != $data['current_level']) {
            $this->db->query('UPDATE agent SET current_level = '.$data['current_level']. ' WHERE id = '.$agent['id']);
            $agent['current_level'] = $data['current_level'];
        }
        return $agent;
    }

    private function saveOwner($data) {
        if(!$data['portal_faction']) return false;
        $data['faction'] = $data['portal_faction'];
        $data['agent_name'] = $data['portal_owner'];
        $data['email'] = null;
        $data['current_level'] = null;
        return $this->saveAgent($data);
    }

    private function saveEnemyAgent($data) {
        $data['faction'] = $data['enemy_agent_faction'];
        $data['agent_name'] = $data['enemy_agent'];
        $data['email'] = null;
        $data['current_level'] = null;
        return $this->saveAgent($data);
    }

    private function savePortal($data) {
        $portal = $this->db->query('SELECT * FROM portal WHERE name = "'.$data['portal_name'].'" LIMIT 1;');
        if(!$portal = $portal->fetch(\PDO::FETCH_ASSOC)) {
            $portal['name'] = iconv("UTF-8", "ISO-8859-1", $data['portal_name']);
            $portal['link'] = $data['portal_link'];
            $portal['address'] = iconv("UTF-8", "ISO-8859-1", $data['portal_address']);
            $portal['ll_s'] = $data['portal_link_ll_s'];
            $portal['ll_w'] = $data['portal_link_ll_w'];
            $portal['pll_s'] = $data['portal_link_pll_s'];
            $portal['pll_w'] = $data['portal_link_pll_w'];
            $portal['agent_id'] = isset($this->owner['id']) ? $this->owner['id'] : null;
            $portal['level'] = $data['portal_level'];
            $portal['health'] = $data['portal_health'];
            $sth = $this->db->prepare(
                'INSERT INTO portal ('.implode(',', array_keys($portal)).') '.
                'VALUES ('.trim(str_repeat('?, ', count($portal)), ', ').')'
            );
            $sth->execute(array_values($portal));
            $portal['id'] = $this->db->lastInsertId();
        } else {
            $values['name'] = iconv("UTF-8", "ISO-8859-1", $data['portal_name']);
            $values['link'] = $data['portal_link'];
            $value['address'] = iconv("UTF-8", "ISO-8859-1", $data['portal_address']);
            $values['ll_s'] = $data['portal_link_ll_s'];
            $values['ll_w'] = $data['portal_link_ll_w'];
            $values['pll_s'] = $data['portal_link_pll_s'];
            $values['pll_w'] = $data['portal_link_pll_w'];
            $values['agent_id'] = isset($this->owner['id']) ? $this->owner['id'] : null;
            $values['level'] = $data['portal_level'];
            $values['health'] = $data['portal_health'];
            if(array_diff($values, $portal)) {
                $sth = $this->db->prepare(
                    'UPDATE portal SET '.implode(' = ?, ', array_keys($values)). ' = ? '.
                    'WHERE id = ?'
                );
                $values['id'] = $portal['id'];
                $sth->execute(array_values($values));
            } else {
                $values['id'] = $portal['id'];
            }
            $portal = $values;
        }
        return $portal;
    }

    private function saveDamage($data) {
        $damage['owner_id'] = isset($this->owner['id']) ? $this->owner['id'] : null;
        $damage['emeny_agent_id'] = $this->emeny['id'];
        $damage['ressonator_destroyed'] = $data['ressonator_destroyed'];
        $damage['ressonator_remaining'] = $data['ressonator_remaining'];
        $damage['portal_level'] = $data['portal_level'];
        $damage['portal_owner'] = isset($this->owner['id']) ? $this->owner['id'] : null;
        $damage['portal_health'] = $data['portal_health'];
        $damage['damage_time'] = $this->headers->date;
        $damage['logged_by'] = $this->headers->toaddress;
        $sth = $this->db->prepare(
            'INSERT INTO damage ('.implode(', ', array_keys($damage)).') '.
            'VALUES ('.trim(str_repeat('?, ', count($damage)), ', ').')'
        );
        $sth->execute(array_values($damage));
        $damage['id'] = $this->db->lastInsertId();
        return $damage;
    }

    private function parseBody($msg_number) {
        $this->body->loadHTML('<html>'.$this->imap->fetchHtmlBody($msg_number).'</html>');
        $domxpath = new \DOMXPath($this->body);
        // Agent info
        $nodeList = $domxpath->query('//div/table/tbody/tr[2]/td/table/tbody/tr/td/span');
        $data['agent_name'] = $nodeList->item(1)->nodeValue;
        $data['faction'] = $nodeList->item(3)->nodeValue;
        $data['current_level'] = filter_var($nodeList->item(5)->nodeValue, FILTER_SANITIZE_NUMBER_INT);
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
        $nodeList = $domxpath->query('//div/table/tbody/tr[2]/td/table/tbody/tr[last()]/td/table/td/div');
        $data['enemy_agent'] = $nodeList->item(0)->getElementsByTagName('span')->item(0)->nodeValue;
        $data['enemy_agent_faction'] = $nodeList->item(0)->getElementsByTagName('span')->item(0)->getAttribute('style') == 'color: #3679B9;' ? 1 : 2;
        preg_match(
            '/DAMAGE:([0-9]).*at ([0-9]{1,2}:[0-9]{1,2}).*GMT([0-9]|No)/',
            $nodeList->item(0)->nodeValue,
            $matches
        );
        $data['ressonator_destroyed'] = $matches[1];
        $data['time'] = $matches[2];
        $data['ressonator_remaining'] = $matches[3]=='No'?0:$matches[3];
        // Status
        $nodeList = $domxpath->query('//div/table/tbody/tr[2]/td/table/tbody/tr[last()]/td/table/td[2]/div');
        if($nodeList->item(0)->getElementsByTagName('span')->length) {
            $data['portal_faction'] = $nodeList->item(0)->getElementsByTagName('span')->item(0)->getAttribute('style') == 'color: #3679B9;' ? 1 : 2;
        } else {
            $data['portal_faction'] = null;
        }
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