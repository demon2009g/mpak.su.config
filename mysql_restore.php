#!/usr/bin/php
<?php 
	set_time_limit(9999999999999999);
	ini_set('max_execution_time',9999999999999999);
	$settings['zipfile'] = "mysql_dump.zip";
	$settings['folder'] = '/tmp/mysql_dump';
	$settings['folder_bd'] = "{$settings['folder']}/db";
	$settings['premisions'] = "mysql_premisions.ini";
	
	if(count($argv)==3 and $argv[1]=="-p"){
		$pass = $argv[2];
		if(file_exists("/tmp/{$settings['zipfile']}")){	
		
			if(file_exists($settings['folder']))
				exec("rm -R -f {$settings['folder']}");
			mkdir($settings['folder']);		
		
			$zip = new ZipArchive;
			if ($zip->open("/tmp/{$settings['zipfile']}") === TRUE) {
				$zip->extractTo($settings['folder']);
				$zip->close();
			} else {
				echo 'Ошибка открытия архива!\n';
			}
			
			$link = mysqli_connect('localhost', 'root', $pass);
			foreach (scandir($settings['folder_bd']) as $file){
				$fullfile = "{$settings['folder_bd']}/{$file}";
				if (is_file($fullfile) and preg_match("#\.sql$#iu",$file)){	
					$db_name = 	stripslashes(preg_replace("#\.sql$#iu",'',$file));					
					mysqli_query($link,"CREATE DATABASE `{$db_name}` DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci");
					exec("mysql --user=root --password='{$pass}' {$db_name} < {$fullfile}\n");
				}
			}		
			
			$premisions = file_get_contents("{$settings['folder']}/{$settings['premisions']}");
			$premisions = json_decode($premisions,1);			
			
			foreach($premisions as $user_premisions){
				foreach($user_premisions['premisions'] as $premision){
					mysqli_query($link,$premision);
				}
			}		
			
			exec("rm -R -f {$settings['folder']}");
			echo "Готово!\n";
			
		}else{
			echo "Ошибка! Нет файла: /tmp/{$settings['zipfile']}!\n";
		}
	}else{
		echo "Запустите данный скрипт с прараметрами -p <password>!\n";
	}
?>
