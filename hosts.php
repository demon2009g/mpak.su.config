<?php
	//начальные переменные
	$srv_path = realpath( __DIR__ . "/../");
	$mods = array( 'vhosts', 'sslhosts');
	$modsports = array( 'vhosts'=>array(80,8080,'80','http'), 'sslhosts'=>array(443,993,'443 ssl','https'));
	$dir_exclusion  = array('.','..','mpak.cms');//исключения
	$all_sites = array(); //массив со всеми собранными сайтами	
	$srv_dir = scandir($srv_path); //сканируем дирректорию сервера
	$path_engine = array();//путь к движку
	
	//проверяем версию апача
	exec("apache2 -v",$version);	
	if($version = floatval(preg_replace("#^.*Apache/(\d+\.\d+).*$#iu",'$1',$version['0'])) ){
		if($version < 2.4){
			$apache_config = "Order allow,deny\n		Allow from all";
		}else if($version >= 2.4){
			$apache_config = "Require all granted";
		}
	}
	
	foreach($mods as $mod){
		if(file_exists("$srv_path/$mod/mpak.cms")){
			$path_engine[$mod]="$srv_path/$mod/mpak.cms";
		}else if(file_exists("$srv_path/mpak.cms")){
			$path_engine[$mod]="$srv_path/mpak.cms";
		}else{
			$path_engine[$mod]=false;
		}
	}
	
	function config($site){
		global $srv_path;
		global $path_engine;
		global $apache_config;
		global $mods;
		global $modsports;		
		$need_cms = preg_match('#^www\.#iUu',$site['name']);
		$site['name_ascii'] = idn_to_ascii($site['name']);
		$site['path_real'] = realpath($site['path']);
		
		
		$configSSL = "";
		$config="
		server {
			listen {$modsports[$site['mod']][2]}; #IP и порт на котором слушает nginx
			server_name {$site['name_ascii']} ".( $need_cms ? preg_replace('#^www\.#iUu','',$site['name_ascii']) : "www.{$site['name_ascii']}" )."; #указываем имена нашего сайта
			server_name_in_redirect off;
			add_header Access-Control-Allow-Origin *;\n";
			
			if($site['mod']=='sslhosts'){
				if(is_dir("$srv_path/ssl/{$site['name']}")){
					$SslDirHost = "{$srv_path}/ssl/{$site['name']}/nginx";
					if(file_exists($SslDirHost))
						$configSSL = "\n\t\t\t\tkeepalive_timeout   70;\n". file_get_contents("$SslDirHost/ssl.conf")."\n\n";
				}else if($site['name']==$site['name_ascii']){
					//$site['name']==$site['name_ascii'] пока поддерживаются только обычные домены
					//вот когда будет поддержке IDN доменов вот тогда и будем думать как включить
					if(!file_exists("/etc/letsencrypt/live/{$site['name']}")){
						exec("/srv/www/letsencrypt/certbot-auto certonly --webroot -w /var/www/html --email admin@it-impulse.ru -d {$site['name']} > /dev/null");						
					}
					if(file_exists("/etc/letsencrypt/live/{$site['name']}")){
						$configSSL = "
							keepalive_timeout   70;
							ssl_protocols TLSv1 TLSv1.1 TLSv1.2; 
							ssl_ciphers 'HIGH:!aNULL:!MD5:!kEDH';
							ssl_certificate     /etc/letsencrypt/live/{$site['name']}/fullchain.pem;
							ssl_certificate_key /etc/letsencrypt/live/{$site['name']}/privkey.pem;
							#ssl_trusted_certificate /etc/letsencrypt/live/{$site['name']}/chain.pem
							
						";
					}
				}else{
					$SslDirHost = "{$srv_path}/ssl/default/nginx";
					if(file_exists($SslDirHost))
						$configSSL = "\n\t\t\t\tkeepalive_timeout   70;\n". file_get_contents("$SslDirHost/ssl.conf")."\n\n";
				}
			}
			$config .= $configSSL;
			
			$config .= "
			
			location / {
				proxy_pass {$modsports[$site['mod']][3]}://127.0.0.1:{$modsports[$site['mod']][1]}/; #указываем ip и порт на котором теперь будет слушать Apache 
				proxy_redirect off;
				proxy_set_header Host \$host;
				proxy_set_header X-Real-IP \$remote_addr;
				proxy_set_header X-Forwarded-For \$proxy_add_x_forwarded_for;
				proxy_set_header  X-Server-Address  \$server_addr;
				client_max_body_size 200m;
				client_body_buffer_size 128k;
				proxy_connect_timeout 90;
				proxy_send_timeout 90;
				proxy_read_timeout 90;
				proxy_buffer_size 4k;
				proxy_buffers 4 32k;
				proxy_busy_buffers_size 64k;
				proxy_temp_file_write_size 10m;
			}
			
			#phpmyadmin
			location ~* ^/phpmyadmin/(.+\.(jpg|jpeg|gif|css|png|js|ico|html|xml|txt))$ {
			   root /usr/share/;
			}
			
			# Определяем местонахождение и расширения статичных файликов
			#location ~* ^.+\.(jpg|jpeg|gif|png|ico|css|zip|tgz|gz|rar|bz2|doc|xls|exe|pdf|ppt|txt|tar|wav|bmp|rtf|js)$ {
			#	root {$site['path_real']};
			#}
			
			# Определяем местонахождение и расширения статичных файликов
			location ~* ^.+\.(ts)$ {
				root {$site['path_real']};
			}

			# htaccess и htpasswd не отдаем
			location ~ /\.ht {
				deny all;
			}
		}";
		
		if($site['mod']=='sslhosts' AND empty($configSSL)){
			echo "Некорректная настройка Nginx SSL  для сайта {$site['name']}\n";
		}else{
			file_put_contents("{$srv_path}/{$site['mod']}.conf/nginx/{$site['name']}.conf",$config);
		}
		
			$configSSL = "";
			$config = "
			<VirtualHost *:{$modsports[$site['mod']][1]}>
				ServerAdmin cms@mpak.su
				ServerName {$site['name_ascii']}
				ServerAlias ".( $need_cms ? preg_replace('#^www\.#iUu','',$site['name_ascii']) : "www.{$site['name_ascii']}" )."
				DocumentRoot ".($need_cms ? $path_engine[$site['mod']] : $site['path_real'] )."/
			#	ErrorLog /var/log/apache2/{$site['name']}_ErrorLog.log
			#	CustomLog /var/log/apache2/{$site['name']}_CustomLog.log common

				<Directory ".($need_cms ? $path_engine[$site['mod']] : $site['path_real'] ).">
					Options Indexes FollowSymLinks MultiViews
					AllowOverride All
					{$apache_config}
				</Directory>\n\n";
				
	
			if($site['mod']=='sslhosts'){
				if(is_dir("$srv_path/ssl/{$site['name']}")){
					$SslDirHost = "{$srv_path}/ssl/{$site['name']}/apache";
					if(file_exists($SslDirHost))
						$configSSL = "\n". file_get_contents("$SslDirHost/ssl.conf") ."\n\n";
				}else if($site['name']==$site['name_ascii']){
					if(!file_exists("/etc/letsencrypt/live/{$site['name']}")){
						//В nginx мы должны были создать
						//походу произошла ошибка и не создалось
					}
					if(file_exists("/etc/letsencrypt/live/{$site['name']}")){
						$configSSL = "							
							SSLEngine on
							SSLProtocol all -SSLv2 -SSLv3
							SSLCipherSuite ALL:!ADH:!EXPORT:!SSLv2:RC4+RSA:+HIGH:+MEDIUM

							SSLCertificateFile /etc/letsencrypt/live/{$site['name']}/cert.pem
							SSLCertificateKeyFile /etc/letsencrypt/live/{$site['name']}/privkey.pem
							SSLCertificateChainFile /etc/letsencrypt/live/{$site['name']}/chain.pem
						";
					}
				}else{					
					$SslDirHost = "{$srv_path}/ssl/default/apache";
					if(file_exists($SslDirHost))
						$configSSL = "\n". file_get_contents("$SslDirHost/ssl.conf") ."\n\n";
				}			
			}
			$config .= $configSSL;
	
			$config .= "								
				RemoteIPHeader X-Forwarded-For
				RemoteIPTrustedProxy 127.0.0.1
			
				php_admin_value open_basedir {$site['path_real']}:".( $need_cms ? "{$path_engine[$site['mod']]}:" : "" )."/tmp
				php_admin_value safe_mode_include_dir ".($need_cms ? $path_engine[$site['mod']] : $site['path_real'] )."
				php_value include_path ".($need_cms ? $path_engine[$site['mod']] : $site['path_real'] )."
				php_admin_value safe_mode_exec_dir ".($need_cms ? $path_engine[$site['mod']] : $site['path_real'] )."
				php_admin_value doc_root ".($need_cms ? $path_engine[$site['mod']] : $site['path_real'] )."
				php_admin_value user_dir ".($need_cms ? $path_engine[$site['mod']] : $site['path_real'] )."
				php_admin_value short_open_tag 1
				php_admin_value upload_tmp_dir /tmp
			#	php_admin_value allow_url_fopen 0
				php_admin_value memory_limit 200M
				php_admin_value post_max_size  20M
			#	php_value phar.readonly Off

				php_admin_value allow_url_include On

				php_admin_value disable_functions system
				php_admin_value disable_functions \"exec,system,passthru,shell_exec,popen,pclose\"
			#	php_value auto_prepend_file /srv/www/sslhosts/s86.ru/ban/ban.php
				
			</VirtualHost>";
			
		
		
		if($site['mod']=='sslhosts' AND empty($configSSL)){
			echo "Некорректная настройка Apache SSL  для сайта {$site['name']}\n";
		}else{
			file_put_contents("$srv_path/{$site['mod']}.conf/apache/{$site['name']}.conf",$config);	
		}		
		
	}
		
	
	
	//ищем все сайты
	foreach($mods as $mod){
		if(in_array($mod,$srv_dir)){
			//если существует папка vhosts или sslhosts
			if(!in_array("$mod.conf",$srv_dir)){
				//если нет папка для конфигов
				mkdir("$srv_path/$mod.conf");//создаем папку
			}			
			if(file_exists("$srv_path/$mod.conf")){
				//если есть папка для конфигов
				
				//сканируем папку с сайтами
				//груповая папка 1 уровня
				foreach(scandir("$srv_path/$mod") as $item){
					if(!in_array($item,$dir_exclusion) AND !preg_match('#^\.#iu',$item) AND is_dir("$srv_path/$mod/$item")){
						if(preg_match('#\.$#iUu',$item)){
							//груповая папка 2 уровня
							foreach(scandir("$srv_path/$mod/$item") as $sub_item){
								if(!in_array($sub_item,$dir_exclusion) AND !preg_match('#^\.#iu',$sub_item) AND is_dir("$srv_path/$mod/$item/$sub_item")){
									if(preg_match('#\.$#iUu',$sub_item)){
										//груповая папка 3 уровня
										foreach(scandir("$srv_path/$mod/$item/$sub_item") as $sub_sub_item){
											if(!in_array($sub_sub_item,$dir_exclusion) AND !preg_match('#^\.#iu',$sub_sub_item) AND is_dir("$srv_path/$mod/$item/$sub_item/$sub_sub_item")){
												$all_sites[] = array(
													'mod'=>$mod,
													'name'=>$sub_sub_item,
													'path'=>"$srv_path/$mod/$item/$sub_item/$sub_sub_item"
												);
											}
										}
									}else{
										$all_sites[] = array(
												'mod'=>$mod,
												'name'=>$sub_item,
												'path'=>"$srv_path/$mod/$item/$sub_item"
											);
									}
								}
							}
						}else{
							//просто сайт
							$all_sites[] = array(
								'mod'=>$mod,
								'name'=>$item,
								'path'=>"$srv_path/$mod/$item"
							);
						}						
					}
				}
			}else{
				echo "Not exist directory '$mod.conf' and I can't create it!\n";
			}
		}else{
			echo "Not found and Skip '$mod'!\n";
		}
	}
	
	
	foreach($all_sites as $key1 => $site1){
		//делаем проверку на дубликаты в оном и том же режиме (http/https)
		foreach($all_sites as $key2 => $site2){
			if( $key1!=$key2 ){//если это не он сам
				if($site1['mod'] == $site2['mod']){//если это один и тот же режим
					if(preg_replace('#^www\.#iUu','',$site1['name']) == preg_replace('#^www\.#iUu','',$site2['name'])){//если доменны совпадают без учета отошения к движку
						exit("Detected a duplicate domain '".preg_replace('#^www\.#iUu','',$site1['name'])."' in the directories:\n'{$site1['path']}'\n'{$site2['path']}'\n");
					}
				}
			}
		}
		//делаем проверку наличия движка для сайта
		if(preg_match('#^www\.#iUu',$site1['name'])){//если сайт на движке
			if(!$path_engine[$site1['mod']]){//нет движка
				exit("Error: Not find folder 'mpak.cms' for '{$site1['mod']}'!\n");
			}		
		}
	}
	
	//просто удаляем все конфиги
	foreach($mods as $mod){
		if(file_exists("$srv_path/$mod.conf/apache")){
			exec("rm -R -f $srv_path/$mod.conf/apache/*");
		}
		if(file_exists("$srv_path/$mod.conf/nginx")){
			exec("rm -R -f $srv_path/$mod.conf/nginx/*");
		}
	}
	
	foreach($all_sites as $site){
		config($site);
	}
	
	exec("/etc/init.d/nginx reload");	//restart
	exec("/etc/init.d/apache2 reload");	//restart
	
	echo "End.\n";
	
	
	
	
	
	
	
	


























?>
