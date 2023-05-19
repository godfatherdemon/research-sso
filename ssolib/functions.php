<?php	
	// koneksi db
	function db_connect() {
		global $db;
		if ($db['dbms']=='postgre') {
			return pg_connect('');
		} else {
			return mysqli_connect($db['hostname'], $db['username'], $db['password'], $db['database']);
		}
	}
	
	function db_query($q) {
		global $db;
		global $dbconn;
		if ($db['dbms']=='postgre') return pg_query($dbconn, $q);
		else return mysqli_query($dbconn, $q);
	}
	
	function db_fetch($q) {
		global $db;
		global $dbconn;
		if ($db['dbms']=='postgre') return pg_fetch_assoc($q);
		else return mysqli_fetch_assoc($q);
	}
	
	function db_error() {
		global $db;
		global $dbconn;
		if ($db['dbms']=='postgre') return pg_last_error($dbconn);
		else return mysqli_error($dbconn);
	}
	
	/* fungsi untuk set session berdasarkan framework */
	function set_sessions($data) {
		global $session_mode;
		global $session_table_name;
		if ($session_mode=='ci') {
			$d = db_query("SELECT data FROM $session_table_name WHERE id = '$_SESSION[id]'");
			if ($s = db_fetch($d)) {
				$sess = $s['data'];
				foreach ($data as $name => $value) {
					$sess .= $name . '|' . serialize($value);
				}
				$q = "UPDATE $session_table_name SET data = '$sess' WHERE id = '$_SESSION[id]'";
				db_query($q);
			}
		} else if ($session_mode=='lara') {
			$d = db_query("SELECT payload FROM $session_table_name WHERE id = '$_SESSION[id]'");
			if ($s = db_fetch($d)) {
				$sess = unserialize(base64_decode($s['payload']));
				foreach ($data as $name => $value) {
					$sess[$name] = $value;
				}
				$newsess = base64_encode(serialize($sess));
				$q = "UPDATE $session_table_name SET payload = '$newsess' WHERE id = '$_SESSION[id]'";
				db_query($q);
			}
		} else {
			foreach ($data as $name => $value) {
				$_SESSION[$name] = $value;
			}
		}
	}
	
	/* fungsi untuk ambil role atau data lain dari DB aplikasi untuk disimpan
	   di session berdasarkan inputan username */
	function set_sessions_from_db($username) {
		global $session_query;
		global $session_cols;
		$query = str_replace('{username}', $username, $session_query);
		$q = db_query($query);
		if ($q) {
			if ($d = db_fetch($q)) {
				$data = array();
				foreach ($session_cols as $name => $value) {
					if ($value=='') $data[$name] = $d[$name];
					else $data[$name] = $d[$value];
				}
				set_sessions($data);
			} else return false;
		} else {
			echo db_error();
			return false;
		}
	}
	
	function create_session_table() {
		global $db;
		global $session_mode;
		global $session_table_name;
		
		if ($session_mode=='ci') {
			if ($db['dbms']=='postgre') {
				$q = 'CREATE TABLE "'.$session_table_name.'" (
						"id" varchar(128) NOT NULL,
						"ip_address" varchar(45) NOT NULL,
						"timestamp" bigint DEFAULT 0 NOT NULL,
						"data" text DEFAULT \'\' NOT NULL
					)';
				db_query($q);
				$q = 'CREATE INDEX "'.$session_table_name.'_timestamp" ON "'.$session_table_name.'" ("timestamp")';
			} else {
				$q = "CREATE TABLE IF NOT EXISTS `$session_table_name` (
						`id` varchar(128) NOT NULL,
						`ip_address` varchar(45) NOT NULL,
						`timestamp` int(10) unsigned DEFAULT 0 NOT NULL,
						`data` blob NOT NULL,
						KEY `ci_sessions_timestamp` (`timestamp`)
					)";
			}
		} else if ($session_mode=='lara') {
			if ($db['dbms']=='postgre') {
				$q = 'CREATE TABLE "'.$session_table_name.'" (
						"id" varchar(255) NOT NULL,
						"user_id" bigint,
						"ip_address" varchar(45),
						"user_agent" text,
						"payload" text NOT NULL,
						"last_activity" int NOT NULL
					)';
				db_query($q);
				$q = 'CREATE INDEX "'.$session_table_name.'_user_id_index" ON "'.$session_table_name.'" ("user_id")';
				db_query($q);
				$q = 'CREATE INDEX "'.$session_table_name.'_last_activity_index" ON "'.$session_table_name.'" ("last_activity")';
			} else {
				$q = "CREATE TABLE IF NOT EXISTS `$session_table_name` (
						`id` varchar(255) PRIMARY KEY,
						`user_id` bigint(20) UNSIGNED DEFAULT NULL,
						`ip_address` varchar(45) DEFAULT NULL,
						`user_agent` text DEFAULT NULL,
						`payload` text NOT NULL,
						`last_activity` int(11) NOT NULL,
						KEY `{$session_table_name}_user_id_index` (`user_id`),
						KEY `{$session_table_name}_last_activity_index` (`last_activity`)
					)";
			}
		}
		db_query($q);
	}
?>