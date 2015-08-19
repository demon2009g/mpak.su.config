<?

include `pwd`. "/../include/config.php";

if(empty($argv[1])){
	echo $cmd = "mysqldump -u {$conf['db']['login']} -p{$conf['db']['pass']} {$conf['db']['name']} > {$conf['db']['name']}.sql\n";
}else{
	echo $cmd = "mysql -u {$conf['db']['login']} -p{$conf['db']['pass']} {$conf['db']['name']} < {$argv[1]}\n";
}