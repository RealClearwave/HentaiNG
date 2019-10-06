<?php
$dom = $_SERVER['SERVER_NAME'];
$appdir = dirname(__FILE__);
$pic_ext = array("jpg"=>true,"png"=>true,"tiff"=>true,"gif"=>true);

$fo = fopen("content.json","r");
$dcc = fgets($fo);
fclose($fo);
$buf_content = json_decode($dcc);

$fo = fopen("tag.json","r");
$dcc = fgets($fo);
fclose($fo);
$buf_tag = json_decode($dcc);

function is_local($url) {
	if(stristr($url,'localhost') || stristr($url,'127.') || stristr($url,'192.') ) {
		return true;
	} else {
		return false;
	}
}

function scan_path($pfd) {
	$file = scandir($pfd);
	while (count($file) == 3 && !isset($pic_ext[pathinfo($file[2],PATHINFO_EXTENSION)])) {
		$pfd = $pfd . '/' . $file[2];
		$file = scandir($pfd);
	}
	return $pfd;
}

function dump_content() {
	global $buf_content;
	return $buf_content;
}

function gall_info($gid,$attr) {
	$dcc = (array)dump_content();
	$gatr = (array)json_decode($dcc[$gid]);
	return $gatr[$attr];
}

function gall_filt($fil,$val,$strict) {
	$ret = array();
	for ($i=0; $i< count(dump_content()); $i+= 1) {
		$vvv = gall_info($i,$fil);
		if (($strict && $vvv == $val) || (!$strict && strpos(' ' . $vvv,$val)))
			array_push($ret,$i);
	}
	return $ret;
}

function dump_tag() {
	global $buf_tag;
	return $buf_tag;
}

function set_tag($ctg) {
	$fo = fopen("tag.json","w");
	fwrite($fo,json_encode($ctg));
	fclose($fo);
}

function gall_tag($gid) {
	return dump_tag()[$gid];
}

function add_tag($gid,$tag) {
	$ctg = dump_tag();
	array_push($ctg[$gid],$tag);
	set_tag($ctg);
}

function rmv_tag($gid,$tag) {
	$ctg = dump_tag();
	$ctg[$gid] = array_diff($ctg[$gid],[$tag]);
	set_tag($ctg);
}

function sch_tag($tag) {
	$arr = array();
	$ctg = dump_tag();
	for ($i=0; $i<count(dump_content()); $i+=1) {
		for ($j=0; $j<count($ctg[$i]); $j++) {
			if ($ctg[$i][$j] == $tag) {
				array_push($arr,$i);
				break;
			}
		}
	}
	return $arr;
}

echo '<!DOCTYPE html><html>
<head><meta charset="utf-8">
<title>HentaiNG</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<link rel="stylesheet" href="asset/css/bootstrap.min.css">
<script src="asset/js/bootstrap.min.js"></script>
</head><body>
<nav class="navbar navbar-expand-md bg-light navbar-light">
<a class="navbar-brand" href="/">HentaiNG</a>
<button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#collapsibleNavbar">
<span class="navbar-toggler-icon"></span></button>
<div class="collapse navbar-collapse" id="collapsibleNavbar">
<ul class="navbar-nav"><li class="nav-item"><a class="nav-link" href="/">Home</a></li>
<li class="nav-item"><a class="nav-link" href="?act=explore">Explore</a></li>
<li class="nav-item"><form class="form-inline" action="" method="get">
<input type="text" name="swd" class="form-control" placeholder="Search here"/>
<button type="submit" class="btn btn-success">Search</button>
</form></li></ul></div></nav><br />';

if (!isset($_GET["act"])) {
	echo '<div class="container"><div class="jumbotron">
	<h1>Welcome to HentaiNG</h1><p>Hantai of New Generation!</p>
	</div></div><div class="row clearfix"><div class="col-md-12 column">
	<div class="list-group"><a href="#" class="list-group-item active">Galleries</a>';
	if (isset($_GET["swd"])) {
		$swd = $_GET["swd"];
		$dsp = gall_filt("name",$swd,false);
		for ($j=0; $j< count($dsp); $j+= 1) {
			$i = $dsp[$j];
			echo '<div class="list-group-item"><span class="badge">' . gall_info($i,"count") . '</span>';
			echo '<a href="?' . http_build_query(array("act"=>"show","gid"=>$i,"page"=>1)) . '">' . gall_info($i,"name") . '</a></div>';
		}
	} else if (isset($_GET["tag"])) {
		$dsp = sch_tag($_GET["tag"]);
		for ($j=0; $j< count($dsp); $j+= 1) {
			$i = $dsp[$j];
			echo '<div class="list-group-item"><span class="badge">' . gall_info($i,"count") . '</span>';
			echo '<a href="?' . http_build_query(array("act"=>"show","gid"=>$i,"page"=>1)) . '">' . gall_info($i,"name") . '</a></div>';
		}
	} else {
		for ($i = 0; $i < count(dump_content()); $i+= 1) {
			echo '<div class="list-group-item"><span class="badge">' . gall_info($i,"count") . '</span>';
			echo '<a href="?' . http_build_query(array("act"=>"show","gid"=>$i,"page"=>1)) . '">' . gall_info($i,"name") . '</a></div>';
		}
	}
	echo '</div></div></div></div></div></div>';
} else {
	if ($_GET["act"] == "explore") {
		echo '<div class="container mt-3">';
		for ($i=1; $i<=10; $i++) {
			$gid = rand(0,count(dump_content())-1);
			$file = scandir($appdir . '/' . gall_info($gid,"folder"));
			echo '<div class="media border p-3"><img src="' . gall_info($gid,"folder") . '/' . gall_info($gid,"page")[0] . '" alt="Cover" class="mr-3 mt-3 rounded-circle" style="width:60px;">';
			echo '<div class="media-body"><h4>' . gall_info($gid,"name") . '</h4>';
			echo '<a href="?' . http_build_query(array("act"=>"show","gid"=>$gid,"page"=>1)) . '"><strong>Get Started!</strong></a>';
			echo '</div></div>';
		}
		echo '</div>';
	} else if ($_GET["act"] == "build_content"  && is_local($dom)) {
		echo "building content.json      ";
		$file = scandir($appdir . "/usr/gallery");
		$arr = array();
		$gid = -1;
		for ($i = 0; $i < count($file); $i+= 1) {
			if ($file[$i][0] != '.') {
				$scpv = scan_path('usr/gallery/' . $file[$i]);
				$gid = $gid + 1;
				$page = array();
				$fl = scandir($appdir . '/' . $scpv);

				for ($j=0; $j<count($fl); $j++) {
					if (isset($pic_ext[pathinfo($fl[$j],PATHINFO_EXTENSION)]) && $pic_ext[pathinfo($fl[$j],PATHINFO_EXTENSION)] == true) {
						array_push($page,$fl[$j]);
					}
				}

				array_push($arr,json_encode(array("gid"=>$gid,"name"=>$file[$i],"folder"=>$scpv,"page"=>$page,"count"=>count($page),"compressed"=>$file[$i] . ".rar")));
			}
		}
		$cjs = fopen("content.json","w+");
		fwrite($cjs,json_encode($arr));
		fclose($cjs);
		print_r($arr);
		echo "      Done.";
	} else if ($_GET["act"] == "show") {
		echo '<div class="container"><div class="row clearfix"><div class="col-md-9 column">';

		$gid = $_GET["gid"];
		$page = $_GET["page"];
		$acted = false;
		echo ' <img class="img-fluid" src="' . gall_info($gid,"folder") . '/' . gall_info($gid,"page")[$page-1] .'" alt="pic">';
		echo '</div>';

		echo '<div class="col-md-3 column">';
		echo '<ul class="list-group">';
		echo '<li class="list-group-item">' .  gall_info($gid,"name") .'</li>';

		echo '<li class="list-group-item">';
		$ctg = gall_tag($gid);
		if (count($ctg) == 0) {
			echo 'No Tag Available.';
		} else {
			for ($i=0; $i<count($ctg); $i+=1) {
				echo '<a href="?tag=' . $ctg[$i] . '"><span class="badge badge-pill badge-info">' . $ctg[$i] . '</span></a> ';
			}
		}
		if (is_local($dom)) echo '<br /><a href="?act=tagadmin&gid=' . $gid . '" class="btn btn-info" role="button">Manage Tags</a>';
		echo '</li>';
		echo '<a href="usr/compressed/' . gall_info($gid,"compressed") . '"><li class="list-group-item">Download ZIP</li></a>';
		$prev_dis="";
		$next_dis="";
		if ($_GET["page"] < 2) $prev_dis="disabled";
		if ($_GET["page"] >= gall_info($gid,"count")) $next_dis="disabled";
		echo '<div class="btn-group">';
		echo '<a href="?' . http_build_query(array("act"=>"show","gid"=>$gid,"page"=>$_GET["page"]-1)) . '" class="btn btn-success ' . $prev_dis . '" role="button">prev page</a>';
		echo '<a href="?' . http_build_query(array("act"=>"show","gid"=>$gid,"page"=>$_GET["page"]+1)) . '" class="btn btn-primary ' . $next_dis . '" role="button">next page</a>';
		echo '</div>';

		echo '</ul>';
		echo '</div></div></div>';
	} else if ($_GET["act"] == "tagadmin" && is_local($dom)) {
		if (!isset($_GET["exe"])){
			$gid = $_GET["gid"];
			echo '<div class="container">';
			echo '<p>Removable Tags(Click to remove):</p>';
			$ctg = gall_tag($gid);
			for ($i=0; $i<count($ctg); $i+=1) {
				echo '<a href="?action=tagadmin&exe=rm&gid=' . $gid . '&tag=' . $ctg[$i] . '"><span class="badge badge-pill badge-info">' . $ctg[$i] . '</span></a> ';
			}
			echo '</div><br />';

			echo '<div class="container">';
			echo '<form class="form-inline" action="" method="get"><label for="email">Add Tag:</label>';
			echo '<input name="act" type="hidden" value="tagadmin">';
			echo '<input name="exe" type="hidden" value="add">';
			echo '<input name="gid" type="hidden" value="' . $gid . '">';
			echo '<input name="tag" type="text" class="form-control" placeholder="Enter your tag here">';
			echo '<button type="submit" class="btn btn-success">Submit</button></form>';
			echo '</div>';
		}else if ($_GET["exe"] == "init") {
			$tbl = array();
			for ($i=0; $i<count(dump_content()); $i+=1) {
				array_push($tbl,array());
			}
			print_r($tbl);
			$tjs = fopen("tag.json","w+");
			fwrite($tjs,json_encode($tbl));
			fclose($tjs);
		} else if ($_GET["exe"] == "add") {
			$gid = $_GET["gid"];
			$tag = $_GET["tag"];
			add_tag($gid,$tag);
			echo "Done.";
		} else if ($_GET["exe"] == "rm") {
			$gid = $_GET["gid"];
			$tag = $_GET["tag"];
			rmv_tag($gid,$tag);
			echo "Done.";
		}else{
			echo '<h1>404 Not Found</h1>';
		}
	}
}
echo '</body></html>';


