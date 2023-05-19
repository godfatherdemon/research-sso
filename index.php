<?php
	require_once 'config.php';
	require_once 'ssolib/functions.php';
	session_start();
	if (isset($_GET['id'])) $_SESSION['id'] = $_GET['id'];
	
	if ($sessions_from_db || $session_mode!='native') { //cek koneksi db
		error_reporting(E_ERROR | E_PARSE | E_NOTICE);
		$dbconn = db_connect();
		if (!$dbconn) {
			echo 'DB tidak terkoneksi / salah konfigurasi';
			die;
		} else if ($session_table_name=='') {
			echo 'tabel session belum di-set di config.php';
			die;
		} else {
			if (isset($_GET['create'])) {
				create_session_table();
				header("location: $site_url");
			}
			$test = db_query("SELECT * FROM $session_table_name");
			if (!$test) {
				echo 'Tabel session tidak ada / salah <br /> Pembuatan otomatis akan dilakukan ...';
				echo "	<script>
							setTimeout(function(){ location.href='$params_login[redirect_uri]/?create'; }, 2000);
						</script>";
				die;
			}
		}
	}
	if (isset($_GET['login'])) {
		if (isset($_SESSION['sso_access_token'])) {
			header("location: $home_url");
		} else {
			$sso_url .= 'auth?response_type=code';
			foreach ($params_login as $name => $value) {
				$sso_url .= "&$name=$value";
			}
			header("location: $sso_url");
		}
	} else if (isset($_GET['logout'])) {
		session_destroy();
		header("location: $sso_url"."logout?redirect_uri=$site_url");
	} else if (isset($_GET['code'])) {
		require_once 'ssolib/Grant.php';
		require_once 'ssolib/KeyCloak.php';
		require_once 'ssolib/Token.php';
		require_once 'ssolib/backend-call.php';
	
		$headers = array(
			'Content-Type: application/x-www-form-urlencoded'
		);
		$config = file_get_contents('ssolib/keycloak.json');
		$kc = new \OnionIoT\KeyCloak\KeyCloak($config);
		$params = array(
			'grant_type' => 'authorization_code', 
			'scope' => 'openid profile', 
			'client_id' => $kc->getClientId(),
			'client_secret' => $kc->getClientSecret(),
			'code' => $_GET['code'],
			'redirect_uri' => $params_login['redirect_uri']
		);
		$response = $kc->send_request('POST', 	
			'/protocol/openid-connect/token', $headers, 
			http_build_query($params));
		if ($response['code'] < 200 || $response['code'] > 299) {
			echo "Error request access token. <br>";
			var_dump($response);
		} else {
			$sess = '';
			$body = json_decode($response['body']);
			$kc->grant = new \OnionIoT\KeyCloak\Grant($response['body']);
			$resp = $kc->grant->access_token->payload;
			$_SESSION['sso_access_token'] = $body->access_token;
			set_sessions($session_auth_names);
			if ($sessions_from_db || $session_mode!='native') set_sessions_from_db($resp['preferred_username']);
			set_sessions(array(
				'sso_access_token' => $body->access_token,
				$username_session_name => $resp['preferred_username'],
				$email_session_name => $resp['email'],
				$fullname_session_name => $resp['name']
			));
			if ($sso_role_attr!='') set_sessions(array($role_session_name=>$resp[$sso_role_attr]));
			header("location: $home_url");
		}
	}
?>