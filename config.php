<?php
	/* SSO keycloak to PHP
	config file */
	
	// URL SSO server keycloak
	$sso_url = 'http://localhost:8080/auth/realms/sso/protocol/openid-connect/';
	
	// parameter keycloak ketika login
	$params_login = array(
		'client_id' => 'sso-app', //nama client di keycloak
		//'state'=>'',
		//'scope'=>'',
		'redirect_uri' => 'http://localhost:8000/sso' //URL dari aplikasi ke folder library
	);
	
	// URL aplikasi
	$site_url = 'http://localhost:8000';
	
	// URL home/beranda/dashboard aplikasi (jika sukses login)
	$home_url = 'http://localhost:8000/home';
	
	// nama session pada aplikasi yang di-set untuk menyimpan data dari keycloak
	$username_session_name = 'username';
	$email_session_name = 'email';
	$fullname_session_name = 'name';
	$role_session_name = 'role';
	
	/* SSO role attribute
	   'xx' -> mengambil role dari keycloak dengan atribut xx
	   '' -> mengambil role dari DB aplikasi ($role_session_name diabaikan) */
	$sso_role_attr = '';
	
	/* session-session yang di-set di aplikasi ketika autentikasi 
	   (data session yang di-set statis / tidak diambil dari db) */
	$session_auth_names = array(
		'loggedin'=>TRUE,
		'status'=>'logged'
	);
	
	// apakah ambil data dari db untuk session
	$sessions_from_db = FALSE;
	$session_table_name = 'sessions';
	
	/* mode session framework: 'native' atau 'ci' atau 'lara'
	   jika 'ci' atau 'lara' dipilih maka $sessions_from_db selalu TRUE */
	$session_mode = 'lara';
	
	//setting DB pada aplikasi
	$db = array(
		// 'dbms' => 'mysqli', //'mysqli' atau 'postgre'
		'dbms' => 'postgre', //'mysqli' atau 'postgre'
		'hostname' => 'localhost',
		'username' => 'root',
		'password' => '',
		'database' => 'test'
	);
	
	/* kolom dari DB yang diambil untuk disimpan menjadi session
	   'nama session yang di-set' => 'nama kolom db'
	   (kosongkan nama kolom db jika nama session dan kolom sama) */
	$session_cols = array(
		'id' => '',
		'verified' => 'email_verified_at'
	);
	
	/* query untuk mengambil data dari DB
	   query ini dieksekusi jika $session_from_db di-set TRUE, untuk ambil role atau data lain dari DB aplikasi untuk disimpan di session berdasarkan 
	   inputan username. jika DBMS nya berbeda, feel free untuk kustomisasi fungsi set_sessions_from_db di functions.php */
	$session_query = "SELECT * FROM users WHERE email = '{username}'";
?>