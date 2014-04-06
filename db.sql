CREATE TABLE faction(
	id INTEGER PRIMARY KEY AUTOINCREMENT,
	name varchar(15)
);

INSERT INTO faction (name) VALUES ('Resistance');

CREATE TABLE agent(
	id INTEGER PRIMARY KEY AUTOINCREMENT,
	faction_id int,
	name varchar(30),
	current_level int
);

CREATE TABLE portal (
	id INTEGER PRIMARY KEY AUTOINCREMENT,
	name varchar(30),
	link varchar(200),
	address varchar(255),
	ll_s real,
	ll_w real,
	pll_s real,
	pll_w real
);

CREATE TABLE damage (
	id INTEGER PRIMARY KEY AUTOINCREMENT,
	owner_id int,
	emeny_agent_id int,
	ressonator_destroyed int,
	ressonator_remaining int,
	portal_level int,
	portal_owner int,
	portal_health int,
	damage_time varchar(5)
);