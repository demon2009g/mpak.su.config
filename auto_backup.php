<?php
set_time_limit(9999999999999999);
ini_set('max_execution_time',9999999999999999); 
	
$settings['bd_login'] = 'root';
$settings['bd_passw'] = 'llo9om22';
$settings['folder'] = realpath(__DIR__ ."/../");
$settings['folder_backup'] = $settings['folder']."/auto_backup";
$settings['folder_bd'] = $settings['folder_backup']."/bd";
$settings['folder_duplicity'] = $settings['folder_backup']."/backup-duplicity";
$settings['number_copies'] = 15;
$settings['exclusion_bd'] = array('information_schema','performance_schema','mysql');

$SSHUSER = 'login';
$SSHPASSWORD = 'pass';
$SSHSERVER = "hostname";
$SSHPORT = '6525';
$DESTFOLDER = '/folder';

function MyScandir($folder){
	$arr = scandir($folder);
	array_shift($arr);
	array_shift($arr);
	return($arr);
}
$PS = __FILE__;
$info = "Пожалуйста, выполните данный скрипт с параметром <FirstStart|Backup>. Пример:\n\tphp -f $PS FirstStart\t-\tПервый запуск скрипта.\n\tphp -f $PS Backup\t\t-\tСтандартный режим работы скрипта.
Для добавления скрипта в крон выполните: crontab -e.\nИ добавьте вконец файла: '0 4 * * * php -f /srv/www/mpak.cms.config/auto_backup.php Backup > /dev/null &'\n";


if(count($argv)==2){	
	
	//exec("rm -R -f /root/.cache/duplicity/*");
	//exec("duplicity --no-encryption --volsize=1024 --include={$settings['folder']}/sslhosts --include={$settings['folder']}/vhosts --exclude-regex='.*\.ts$' --exclude=/** {$settings['folder']} file://{$settings['folder_duplicity']}");
	//exec("duplicity --no-encryption --volsize=300 --include={$settings['folder']}/sslhosts --include={$settings['folder']}/vhosts --include={$settings['folder_bd']} --exclude-regex='.*\.ts$' --exclude=/** {$settings['folder']} sftp://$SSHUSER:$SSHPASSWORD@$SSHSERVER:$SSHPORT/$DESTFOLDER");
	
	$duplicity_command =  "duplicity --no-encryption --volsize=300 --include={$settings['folder']}/sslhosts --include={$settings['folder']}/vhosts --include={$settings['folder_bd']} --exclude-regex='.*\.ts$' --exclude=/** {$settings['folder']} sftp://$SSHUSER:$SSHPASSWORD@$SSHSERVER:$SSHPORT/$DESTFOLDER";
	
	if(trim($argv[1])=="Backup"){

		// Узнаем список БД
		$link = mysql_connect('localhost', $settings['bd_login'], $settings['bd_passw']);
		$res = mysql_query("SHOW DATABASES");

		if(!file_exists($settings['folder_bd']))
			mkdir($settings['folder_bd']);

		while ($row = mysql_fetch_assoc($res)){
			if(!in_array($row['Database'],$settings['exclusion_bd'])){
				$Database = $row['Database'];
				$FolderBD = $settings['folder_bd']."/$Database";
				
				if(!file_exists($FolderBD))
					mkdir($FolderBD);
				
				$in_folder = MyScandir($FolderBD);
				for($i=count($in_folder); $i>$settings['number_copies']-1; $i--){
					unlink($FolderBD."/".array_shift($in_folder));
				}
				
				$DumpFileName = time()."_".date("d.m.Y")."_".$Database.".sql";
				exec("mysqldump {$Database} -u {$settings['bd_login']} -p{$settings['bd_passw']} > {$FolderBD}/{$DumpFileName}");
				
				//создание zip архива
				$zip = new ZipArchive();
				$zip->open("{$FolderBD}/{$DumpFileName}.zip", ZIPARCHIVE::CREATE);
				$zip->addFile("{$FolderBD}/{$DumpFileName}", $DumpFileName);
				$zip->close();

				unlink("{$FolderBD}/{$DumpFileName}");
			
			}
		}
		
		exec("rm -R -f /root/.cache/duplicity/*");
		exec($duplicity_command);
		
	}else if(trim($argv[1])=="FirstStart"){
		
		echo "Пожалуйста, выполните следующую команду вручную и дождитесь ее выполнения:\n$duplicity_command\n";
		
	}else{
		echo($info);
	}
}else{
	echo($info);
}
		

		

?>