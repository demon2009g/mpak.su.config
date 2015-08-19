<?

//$param['sslhosts']['terminal.surguttel.ru']['open_basedir'] = ":/srv/www/vhosts/surgut.info/";

//$param['vhosts']['ugratel.ru']['include_path'] = ":/srv/www/vhosts/ugratel.ru/admin";

//$param['vhosts']['mtacompany.ru']['www'] = "src/htdocs/\nAlias /images /srv/www/htdocs/mtacompany.ru/usr/images/";

/*$param['vhosts']['mtacompany.ru']['www'] = "src/htdocs/
	Alias /images /usr/images/
	Alias /data /usr/data/";

$param['vhosts']['tundrasvs.ru']['www'] = "src/htdocs/
	Alias /images /usr/images
	Alias /data /usr/data";

$param['sslhosts']['chat.s86.ru']['www'] = "www";
$param['sslhosts']['chat.s86.ru']['param'] = "
	php_value register_globals 1
	php_value magic_quotes_gpc 0
	php_value display_errors 1
	php_value display_startup_errors 1
	php_value log_errors 1
	php_value session.use_trans_sid 0";

$param['allow_url_fopen'] = array(
	'terminal.surguttel.ru' => '#',
	'surpk.ru' => '#',
	'surgut.info' => '#',
	'tehincom.info' => '#',
	'mp.s86.ru' => '#',
	'nikolas.s86.ru' => '#'
);*/

include "/srv/www/vhosts/mpak.cms/include/idna_convert.class.inc";
$idn = new idna_convert();

$m_type = array('vhosts', 'sslhosts');
foreach($m_type as $k=>$type){
	echo "\n[$type]\n";
	$folder = "/srv/www/$type";
	system("rm $folder.conf/*");
	$dir = opendir($folder);
	while($file_name = readdir($dir)){
		if ($file_name == 'mpak.cms' || $file_name == '.' || $file_name == '..' || !is_dir("$folder/$file_name")) continue;
		if(is_link("$folder/$file_name")){ # Симлинк
//			if($type == "sslhosts"){
//				$real_path = "ssl.cms";
//				$param[$type][$file_name]['open_basedir'] = "../". readlink("$folder/$file_name");
//			}else{
				echo "real_path:". $real_path = "mpak.cms";
				$param[$type][$file_name]['open_basedir'] = "/srv/www/vhosts/". strtr(readlink("$folder/$file_name"), array("../vhosts/"=>""));
				if(is_link($param[$type][$file_name]['open_basedir'])){
					$param[$type][$file_name]['open_basedir'] = "/srv/www/vhosts/". strtr(readlink($param[$type][$file_name]['open_basedir']), array("../vhosts/"=>""));
				}
//			}
//			print_r($real_path); exit;
		}else{ # Директория
			if (substr($file_name, 0, 4) == 'www.'){
				$real_path = "mpak.cms";
				$param[$type][$file_name]['open_basedir'] = "$folder/$file_name";
			}else{
				$real_path = $file_name;
			}
		}
		echo "$file_name [$real_path]\n";
		$content = "<VirtualHost ".($type == 'sslhosts' ? '*:443' : (array_pop(explode(".", $file_name)) == "i2p" ? '127.0.0.1:7658' : '*:80')).">
	ServerAdmin cms@mpak.su
	ServerName ". /*$idn->decode*/($file_name). "
	ServerAlias ". /*$idn->decode*/(substr($file_name, 0, 4) == 'www.' ? substr($file_name, 4) : "www.$file_name"). "
	DocumentRoot $folder/$real_path/{$param[$type][$file_name]['www']}
#	ErrorLog $folder/$file_name/ErrorLog.log
#	CustomLog $folder/$file_name/CustomLog.log common

	<Directory $folder/$real_path>
		Options Indexes FollowSymLinks MultiViews
		AllowOverride All
		Order allow,deny
		Allow from all
	</Directory>\n\n";

if ($type == 'sslhosts'){ $content .= "
#	SSLEngine on
#	SSLCipherSuite ALL:!ADH:!EXPORT56:RC4+RSA:+HIGH:+MEDIUM:+LOW:+SSLv2:+EXP:+eNULL
#	SetEnvIf User-Agent \".*MSIE.*\" nokeepalive ssl-unclean-shutdown
#	<Files ~ \"\.(cgi|shtml|phtml|php3?)$\">
#		SSLOptions +StdEnvVars
#	</Files>

	SSLEngine on
	SSLProtocol all -SSLv2 
	SSLCipherSuite ALL:!ADH:!EXPORT:!SSLv2:RC4+RSA:+HIGH:+MEDIUM 

	SSLCertificateFile /etc/apache2/ssl/". (file_exists("/etc/apache2/ssl/{$file_name}.crt") ? "{$file_name}.crt" : "91.122.47.82.crt"). "
	SSLCertificateKeyFile /etc/apache2/ssl/". (file_exists("/etc/apache2/ssl/{$file_name}.key") ? "{$file_name}.key" : "91.122.47.82.key"). "
#	SSLCertificateChainFile /etc/apache2/ssl/sub.class1.server.ca.pem

#	CustomLog /usr/local/httpd/logs/ssl_request_log \
#	\"%t %h %{SSL_PROTOCOL}x %{SSL_CIPHER}x \"%r\" %b\"\n\n";
}

echo $content .= "	php_admin_value open_basedir {$param[$type][$file_name]['open_basedir']}".($param[$type][$file_name]['open_basedir'] ? ':' : '')."$folder/$real_path:/tmp
	php_admin_value safe_mode_include_dir $folder/$real_path
	php_value include_path $folder/$real_path{$param[$type][$file_name]['include_path']}
	php_admin_value safe_mode_exec_dir $folder/$real_path
	php_admin_value doc_root $folder/$real_path
	php_admin_value user_dir $folder/$real_path
	php_admin_value upload_tmp_dir /tmp
#{$param['allow_url_fopen'][$file_name]}	php_admin_value allow_url_fopen 0
	php_admin_value memory_limit 200M
	php_admin_value post_max_size  20M
#	php_value phar.readonly Off

	php_admin_value allow_url_include On

	php_admin_value disable_functions system
	php_admin_value disable_functions \"exec,system,passthru,shell_exec,popen,pclose\"
#	php_value auto_prepend_file $folder/s86.ru/ban/ban.php
	{$param[$type][$file_name]['param']}
</VirtualHost>";

		$f = fopen("$folder.conf/$file_name.conf", 'w');
		fwrite($f, $content);
		fclose($f);
	}
}

echo `/etc/init.d/apache2 restart`;

?>
