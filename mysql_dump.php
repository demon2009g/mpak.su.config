#!/usr/bin/php
<?php
	set_time_limit(9999999999999999);
	ini_set('max_execution_time',9999999999999999);
	$settings['folder'] = '/tmp/mysql_dump';
	$settings['folder_bd'] = "{$settings['folder']}/db";
	$settings['zipfile'] = "{$settings['folder']}.zip";
	$settings['premisions'] = "mysql_premisions.ini";
	$settings['exclusion_bd'] = array('information_schema','performance_schema','mysql');
	
	if(count($argv)==3 and $argv[1]=="-p"){
		$pass = $argv[2];
		
		
		$link = mysql_connect('localhost', 'root', $pass);
		
		$users = array();
		mysql_select_db ("mysql",$link);
		$res = mysql_query("SELECT user, host FROM user",$link);	
		while ($row = mysql_fetch_assoc($res))
			$users[] = $row;		
		
		//$users = shell_exec("mysql -u root -B -N -p'$pass' -e 'SELECT user, host FROM user' mysql");
		//preg_match_all("#([A-z0-9-_]+)\s+([^\n]+)\n#iu",$users,$users);
		
		$premisions = array();
		foreach($users as $user){
			$buff = stripslashes(shell_exec("mysql -u root -p'$pass' -B -N -e'SHOW GRANTS FOR \"{$user['user']}\"@\"{$user['host']}\"'"));
			if($buff){
				$buff = explode("\n",$buff);
				array_pop($buff);
				$premisions[] = array(
					'user'=>$user['user'],
					'host'=>$user['host'],
					'premisions'=>$buff
				);
			}
		}		
		
		if(!file_exists($settings['folder'])) 
			mkdir($settings['folder']);
		if(!file_exists($settings['folder_bd'])) 
			mkdir($settings['folder_bd']);
		
		file_put_contents("{$settings['folder']}/{$settings['premisions']}" ,json_encode($premisions));
		

		$res = mysql_query("SHOW DATABASES");
		while ($row = mysql_fetch_assoc($res)){
			if(!in_array($row['Database'],$settings['exclusion_bd'])){
				exec("mysqldump {$row['Database']} -u 'root' -p'$pass' > {$settings['folder_bd']}/{$row['Database']}.sql");			
			}
		}	
		
		$zip = new ZipArchive();
		$zip->open($settings['zipfile'], ZipArchive::CREATE | ZipArchive::OVERWRITE);
		$zip->addFile("{$settings['folder']}/{$settings['premisions']}", $settings['premisions']);
		$dirname = basename($settings['folder_bd']);		
		foreach (scandir($settings['folder_bd']) as $file){
			$fullfile = "{$settings['folder_bd']}/{$file}";
			if (is_file($fullfile))				
				$zip->addFile($fullfile,"$dirname/$file");
		}
		$zip->close();
		
		exec("rm -R -f {$settings['folder']}");
		
		echo "Резервное копирование успешно выполненно!\nФайл бекапа: {$settings['zipfile']}\n";		
		
	}else{
		echo "Запустите данный скрипт с прараметрами -p <password>!\n";
	}
?>