<?php

function realurl($url, $link){
	if(substr($link, 0, 4) == 'http'){
		$fn = $link;
	}elseif(substr($link, 0, 2) == '//'){
		$fn = 'http:'. $link;
	}elseif(substr($link, 0, 1) == '/'){
		preg_match("/^(http:\/\/)?([^\/]+)/i", $url, $matches);
		$fn = $matches[0]. $link;
	}else{
			$fn = dirname($url. 'url'). '/'. $link;
	}
	$fn. "\n";
	return $fn;
}

if((!empty($_GET['path']) && (strpos($doc = $_GET['path'], 'http://') === 0)) || ($doc = $_SERVER['argv']['1'])){
	if(empty($_SERVER['argv']['1'])){
		$bn = ($_SERVER['argv']['1'] ? './' : '/tmp/'). basename($doc);
		ini_set('display_errors', 0);
	}else{
		$bn = basename($doc);
	}// echo $bn;

	if(!file_exists($bn)) mkdir($bn);
	$html = file_get_contents($doc);

	preg_match_all("!<link[^>]+href=\"?'?([^ \"'>]+)\"?'?[^>]*>?!is",$html,$ok1);
	if($_SERVER['argv']['0'])
		print_r($ok1);
	preg_match_all("!@import\"?'?([^ \"'>]+)\"?'?!is",$html,$ok2);
	if($_SERVER['argv']['0'])
		print_r($ok2);
	$ok[0] = array_merge($ok1[0], $ok2[0]);
	$ok[1] = array_merge($ok1[1], $ok2[1]);

	if ($ok[1]){
		if(!file_exists("$bn/css")) mkdir("$bn/css");
		foreach($ok[1] as $key=>$num){

			if(!array_search(array_pop(explode('.', array_shift(explode("?", $num)))), array(1=>'css'))) continue;
			$fn = realurl($doc, $num);
			$style = file_get_contents($fn);

			preg_match_all("!url\(\"?'?([^ \"'\)]+)\"?'?\)!is",$style,$ok);
			if ($ok[1]){
				if(!file_exists("$bn/img")) mkdir("$bn/img");
				$bg = array();
				foreach($ok[1] as $n=>$z){
					if(!empty($_SERVER['argv']['1'])) echo realurl($fn, $z). " => $bn/img/". basename($z). "\n";
					if(copy(realurl($fn, $z), "$bn/img/". basename(array_shift(explode("?", $z))))){
						$tgz[] = "$bn/img/". basename($z);
						$bg[ $ok[0][$n] ] = str_replace($z, "../img/". basename(array_shift(explode("?", $z))), $ok[0][$n]);
					}
				}
				$style = strtr($style, (array)$bg);
			}
			$tgz[] = "$bn/css/". basename($fn);
			file_put_contents("$bn/css/". basename(array_shift(explode("?", $fn))), $style);
			$css[$num] = "css/". basename(array_shift(explode("?", $fn)));
		}
		$html = strtr($html, (array)$css);
	}

	preg_match_all("!<img[^>]+src=\"?'?([^ \"'>]+)\"?'?[^>]+>!is",$html,$ok);
	if($ok[1]){
		if (!file_exists("$bn/img")) mkdir("$bn/img");
		$img = array();
		foreach($ok[1] as $key=>$src){
//			if(array_search(array_pop(explode('.', $src)), array(1=>'jpg', 'gif', 'png'))){
				$realurl = realurl($doc, $src);
				if(!empty($_SERVER['argv']['1'])) echo "$realurl => $bn/img/". basename($src). "\n";
				copy($realurl, "$bn/img/". basename($src));
				$tgz[] = "$bn/img/". basename($src);
				$img[$src] = 'img/'. basename($src);
//			}
		}
		$html = strtr($html, $img);
	}

	preg_match_all("!url\(\"?'?([^ \"'\)]+)\"?'?\)!is",$html,$ok);
	if($ok[1]){
		if (!file_exists("$bn/img")) mkdir("$bn/img");
		$img = array();
		foreach($ok[1] as $n=>$z){
			if(!empty($_SERVER['argv']['1'])) echo realurl($doc, $z). " => $bn/img/". basename($z). "\n";
			if(copy(realurl($doc, $z), "$bn/img/". basename(array_shift(explode("?", $z))))){
				$tgz[] = "$bn/img/". basename($z);
				$img[$z] = 'img/'. basename(array_shift(explode("?", $z)));
			}
		}
		$html = strtr($html, $img);
	}

	preg_match_all("!<script[^>]+src=\"?'?([^ \"'>]+)\"?'?[^>]*></script>!is",$html,$ok);
	if($ok[1]){
		if(!file_exists("$bn/js")) mkdir("$bn/js");
		foreach($ok[1] as $n=>$z){
			if(!empty($_SERVER['argv']['1'])) echo realurl($doc, $z). " => $bn/js/". basename(array_shift(explode("?", $z))). "\n";
			if(copy(realurl($doc, $z), "$bn/js/". basename(array_shift(explode("?", $z))))){
				$tgz[] = "$bn/js/". basename(array_shift(explode("?", $z)));
				$js[$z] = 'js/'. basename(array_shift(explode("?", $z)));
			}
		}
		$html = strtr($html, $js);
	}

//	preg_match_all("!<a[^>]+href=\"?'?([^ \"'>]+)\"?'?[^>]*>!is",$html,$ok);
//	foreach($ok[1] as $n=>$z){
//		$a[$z] = '#';
//	} $html = strtr($html, $a);

	$tgz[] = "$bn/block.html";
	file_put_contents("$bn/block.html", "<h3 title='<!-- [block:modpath] -->:<!-- [block:fn] -->:<!-- [block:id] -->'><!-- [block:title] --></h3>\n<div><!-- [block:content] --></div>\n");
	$tgz[] = "$bn/index.html";
	file_put_contents("$bn/index.html", $html);

	if(empty($_SERVER['argv']['1'])){
//		include mpopendir("modules/{$arg['modpath']}/Tar.php");

//		$tar = new Archive_Tar("$bn.tar", false);
//		$tar->create($tgz); // or die("Could not create archive!");

//		header("Content-Length: ".filesize("$bn.tar"));
//		header("Content-Disposition: attachment; filename=\"".basename("$bn.tar")."\"");
//		echo file_get_contents("$bn.tar");

		$zip = new ZipArchive();
		if ($zip->open($filename = "$bn.zip", ZIPARCHIVE::CREATE)!==TRUE) {
			exit("Невозможно открыть <$bn.zip>\n");
		}else{
			$tree = function($dir) use(&$tree, &$zip){
				$d = opendir($dir);
				while($file = readdir($d)){
					if($file{0} == ".") continue;
					if(is_dir($dir. "/". $file)){
						$tree($dir. "/". $file);
					}else{
						$zip->addFile($dir. "/". $file, substr($dir. "/". $file, strlen("/tmp/")));
					}
				}
			}; $tree($bn); $zip->close();

			header('Content-type: application/zip');
			header('Content-Disposition: attachment; filename="'. $bn. '.zip"');
			readfile($filename);
		}
	}
}

?>