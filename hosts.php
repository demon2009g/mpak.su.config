<?php
	//начальные переменные
	$srv_path = realpath( __DIR__ . "/../");
	$mods = array( 'vhosts', 'sslhosts');
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
		$need_cms = preg_match('#^www\.#iUu',$site['name']);
		$site['name_ascii'] = idn_to_ascii($site['name']);
		$site['path_real'] = realpath($site['path']);
		
		$config = "<VirtualHost *:".($site['mod']=='vhosts' ? '80' : '443').">
	ServerAdmin cms@mpak.su
	ServerName {$site['name_ascii']}
	ServerAlias ".( $need_cms ? preg_replace('#^www\.#iUu','',$site['name_ascii']) : "www.{$site['name_ascii']}" )."
	DocumentRoot ".($need_cms ? $path_engine[$site['mod']] : $site['path_real'] )."/
#	ErrorLog {$site['path_real']}/ErrorLog.log
#	CustomLog {$site['path_real']}/CustomLog.log common

	<Directory ".($need_cms ? $path_engine[$site['mod']] : $site['path_real'] ).">
		Options Indexes FollowSymLinks MultiViews
		AllowOverride All
		{$apache_config}
	</Directory>\n\n";
	
	if($site['mod']=='sslhosts'){
	$config .= "
	SSLEngine on
	SSLProtocol all -SSLv2 
	SSLCipherSuite ALL:!ADH:!EXPORT:!SSLv2:RC4+RSA:+HIGH:+MEDIUM 

	SSLCertificateFile ".(file_exists($fle_crt="$srv_path/ssl/{$site['name']}.crt")?$fle_crt:"$srv_path/ssl/default.crt")."
	SSLCertificateKeyFile ".(file_exists($fle_crt="$srv_path/ssl/{$site['name']}.key")?$fle_crt:"$srv_path/ssl/default.key")."
	";
	}
	
	$config .= "
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
					if(!in_array($item,$dir_exclusion) AND is_dir("$srv_path/$mod/$item")){
						if(preg_match('#\.$#iUu',$item)){
							//груповая папка 2 уровня
							foreach(scandir("$srv_path/$mod/$item") as $sub_item){
								if(!in_array($sub_item,$dir_exclusion) AND is_dir("$srv_path/$mod/$item/$sub_item")){
									if(preg_match('#\.$#iUu',$sub_item)){
										//груповая папка 3 уровня
										foreach(scandir("$srv_path/$mod/$item/$sub_item") as $sub_sub_item){
											if(!in_array($sub_sub_item,$dir_exclusion) AND is_dir("$srv_path/$mod/$item/$sub_item/$sub_sub_item")){
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
	
	exec("/etc/init.d/apache2 restart");	
	
	echo "End.\n";
	
	
	
	
	
	
	
	


























?>
