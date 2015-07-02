<?

include "/srv/www/mpak.cms/include/mpfunc.php";
include $_SERVER['argv']['1']. "/include/config.php";

echo "\nCREATE USER '{$conf['db']['login']}'@'localhost' IDENTIFIED BY '{$conf['db']['pass']}';\n";

echo "GRANT ALL PRIVILEGES ON `{$conf['db']['name']}`.`*` TO `{$conf['db']['login']}`@`localhost` IDENTIFIED BY '{$conf['db']['pass']}';\n";

echo "CREATE DATABASE IF NOT EXISTS `{$conf['db']['name']}`;\n";

echo "GRANT ALL PRIVILEGES ON `{$conf['db']['name']}` . * TO '{$conf['db']['login']}'@'localhost';\n";

echo "flush privileges;\n\n";

?>
