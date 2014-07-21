<?php

use Phinx\Migration\AbstractMigration;

class SentinelInitial extends AbstractMigration
{
    /**
     * Change Method.
     *
     * More information on this method is available here:
     * http://docs.phinx.org/en/latest/migrations.html#the-change-method
     *
     * Uncomment this method if you would like to use it.
     *
    public function change()
    {
    }
    */

    /**
     * Migrate Up.
     */
    public function up()
    {
    	// Table faction
		$faction = $this->table('faction', array('id' => true));
		$faction->addColumn('name', 'string', array('limit' => 50))
			->create();
		$this->execute('INSERT INTO faction (name) VALUES ("Resistance")');
		$this->execute('INSERT INTO faction (name) VALUES ("Enlightenment")');

		// Table agent
		$agent = $this->table('agent', array('id' => true));
		$agent->addColumn('faction_id', 'integer')
			->addColumn('name', 'string', array('limit' => 50))
			->addColumn('current_level', 'integer')
			->create();

		// Table portal
		$portal = $this->table('portal', array('id' => true));
		$portal->addColumn('agent_id', 'integer')
			->addColumn('level', 'string', array('limit' => 2))
			->addColumn('health', 'integer')
			->addColumn('name', 'string', array('limit' => 50))
			->addColumn('link', 'string', array('limit' => 50))
			->addColumn('address', 'string', array('limit' => 255))
			->addColumn('ll_s', 'float')
			->addColumn('ll_w', 'float')
			->addColumn('pll_s', 'float')
			->addColumn('pll_w', 'float')
			->create();

		// Table damage
		$damage = $this->table('damage', array('id' => true));
		$damage->addColumn('owner_id', 'integer')
			->addColumn('emeny_agent_id', 'integer')
			->addColumn('ressonator_destroyed', 'integer')
			->addColumn('ressonator_remaining', 'integer')
			->addColumn('portal_level', 'integer')
			->addColumn('portal_owner', 'integer')
			->addColumn('portal_health', 'integer')
			->addColumn('damage_time', 'string', array('limit' => 20))
			->addColumn('logged_by', 'string', array('limit' => 100))
			->create();
    }

    /**
     * Migrate Down.
     */
    public function down()
    {
		//$count = $this->execute('DELETE FROM faction');
    }
}