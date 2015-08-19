<?php
	//начальные переменные
	$srv_path = realpath( __DIR__ . "/../");
	$mods = array( 'vhosts', 'sslhosts');
	$dir_exclusion  = array('.','..','mpak.cms');//исключения
	$all_sites = array(); //массив со всеми собранными сайтами	
	$srv_dir = scandir($srv_path); //сканируем дирректорию сервера
	$path_engine = array();//путь к движку
	
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
		$need_cms = preg_match('#^www\.#iUu',$site['name']);
		$site['name_ascii'] = idn_to_ascii($site['name']);
		$site['path_real'] = realpath($site['path']);
		
		
		
		$config="
		server {
				listen ".($site['mod']=='vhosts' ? '80' : '443').";

				root ".($need_cms ? $path_engine[$site['mod']] : $site['path_real'] )."/;	
				index index.php index.html index.htm;

				server_name {$site['name_ascii']} ".( $need_cms ? preg_replace('#^www\.#iUu','',$site['name_ascii']) : "www.{$site['name_ascii']}" ).";

				location / {
						try_files \$uri \$uri/ /index.html;
				}

				error_page 404 /404.html;

				error_page 500 502 503 504 /50x.html;
				location = /50x.html {
					  root /usr/share/nginx/www;
				}

				# pass the PHP scripts to FastCGI server listening on the php-fpm socket
				location ~ \.php$ {
						try_files \$uri =404;
						fastcgi_pass unix:/var/run/php5-fpm.sock;
						fastcgi_index index.php;
						fastcgi_param SCRIPT_FILENAME \$document_root\$fastcgi_script_name;
						include fastcgi_params;
						
				}
				
				fastcgi_param PHP_ADMIN_VALUE \"open_basedir={$site['path_real']}:".( $need_cms ? "{$path_engine[$site['mod']]}:" : "" )."/tmp\";
				fastcgi_param PHP_ADMIN_VALUE \"safe_mode_include_dir=".($need_cms ? $path_engine[$site['mod']] : $site['path_real'] )."\";
				fastcgi_param PHP_VALUE \"include_path=".($need_cms ? $path_engine[$site['mod']] : $site['path_real'] )."\";
				fastcgi_param PHP_ADMIN_VALUE \"safe_mode_exec_dir=".($need_cms ? $path_engine[$site['mod']] : $site['path_real'] )."\";
				fastcgi_param PHP_ADMIN_VALUE \"doc_root=".($need_cms ? $path_engine[$site['mod']] : $site['path_real'] )."\";
				fastcgi_param PHP_ADMIN_VALUE \"user_dir=".($need_cms ? $path_engine[$site['mod']] : $site['path_real'] )."\";
				fastcgi_param PHP_ADMIN_VALUE \"short_open_tag=1\";
		#		fastcgi_param PHP_ADMIN_VALUE \"allow_url_fopen=0\";
				fastcgi_param PHP_ADMIN_VALUE \"memory_limit=200\";
				fastcgi_param PHP_ADMIN_VALUE \"post_max_size=20M\";
		#		fastcgi_param PHP_VALUE \"phar.readonly=Off\";

				fastcgi_param PHP_ADMIN_VALUE \"allow_url_include=On\";
				
				fastcgi_param PHP_ADMIN_VALUE \"disable_functions=system\";
				fastcgi_param PHP_ADMIN_VALUE \"disable_functions=exec,system,passthru,shell_exec,popen,pclose\";
		#		fastcgi_param PHP_VALUE \"auto_prepend_file=/srv/www/sslhosts/s86.ru/ban/ban.php\";

		}";



	
		/*if($site['mod']=='sslhosts'){
			$config .= "
			SSLEngine on
			SSLProtocol all -SSLv2 
			SSLCipherSuite ALL:!ADH:!EXPORT:!SSLv2:RC4+RSA:+HIGH:+MEDIUM\n\n";
			$sslFiles = scandir("$srv_path/ssl/");
			if(!in_array("SSLCertificateFile.{$site['name']}.crt",$sslFiles)){
				$config .= "
				SSLCertificateFile $srv_path/ssl/default.crt
				SSLCertificateKeyFile $srv_path/ssl/default.key";
			}
			
			
			foreach($sslFiles as $sslfile){
				if(
					!in_array($sslfile,array('.','..')) 
						and 
					preg_match("#^SSLCertificate\w+\.{$site['name']}#ui",$sslfile)
				){
					$config .= "		".preg_replace("#^(SSLCertificate\w+)\.{$site['name']}.+$#ui","$1",$sslfile) ." $srv_path/ssl/$sslfile\n";
				}
			}	
		}*/
	

		file_put_contents("$srv_path/{$site['mod']}.conf/{$site['name']}.conf",$config);
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
		if(file_exists("$srv_path/$mod.conf")){
			exec("rm -R -f $srv_path/$mod.conf/*");
		}
	}
	
	foreach($all_sites as $site){
		config($site);
	}
	
	exec("/etc/init.d/nginx restart");	
	
	echo "End.\n";
	
	
	
	
	
	
	
	


























?>
