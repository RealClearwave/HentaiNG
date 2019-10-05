<?php
$dom = $_SERVER['SERVER_NAME'];
$appdir = dirname(__FILE__);
$pic_ext = array("jpg"=>true,"png"=>true,"tiff"=>true,"gif"=>true);

function is_local($url){
	if(stristr($url,'localhost') || stristr($url,'127.') || stristr($url,'192.') ){
		return true;	
	}else{
		return false;	
	}	
}

function scan_path($pfd){
	$file = scandir($pfd);
	while (count($file) == 3 && !isset($pic_ext[pathinfo($file[2],PATHINFO_EXTENSION)])){
		$pfd = $pfd . '/' . $file[2];
		$file = scandir($pfd);
	}
	return $pfd;
}

function dump_content(){
	$fo = fopen("content.json","r");
	$dcc = fgets($fo);
	fclose($fo);
	return json_decode($dcc);
}

function gall_info($gid,$attr){
	$dcc = (array)dump_content();
	$gatr = (array)json_decode($dcc[$gid]);
	return $gatr[$attr];
}

function gall_filt($fil,$val,$strict){
	$ret = array();
	for ($i=0;$i< count(dump_content()); $i+= 1){
		$vvv = gall_info($i,$fil);
		if (($strict && $vvv == $val) || (!$strict && strpos(' ' . $vvv,$val)))
			array_push($ret,$i);
	}
	return $ret;
}
echo '<!DOCTYPE html>
<html>
<head>
	<meta charset="utf-8">
	<title>HentaiNG</title>
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<link rel="stylesheet" href="https://cdn.staticfile.org/twitter-bootstrap/4.3.1/css/bootstrap.min.css">
	<script src="https://cdn.staticfile.org/jquery/3.2.1/jquery.min.js"></script>
	<script src="https://cdn.staticfile.org/popper.js/1.15.0/umd/popper.min.js"></script>
	<script src="https://cdn.staticfile.org/twitter-bootstrap/4.3.1/js/bootstrap.min.js"></script>
</head>

<body>

<nav class="navbar navbar-expand-md bg-light navbar-light">
  <a class="navbar-brand" href="/">HentaiNG</a>
  <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#collapsibleNavbar">
    <span class="navbar-toggler-icon"></span>
  </button>
  <div class="collapse navbar-collapse" id="collapsibleNavbar">
    <ul class="navbar-nav">
      <li class="nav-item">
        <a class="nav-link" href="/">Home</a>
      </li>
      <li class="nav-item">
        <a class="nav-link" href="?act=explore">Explore</a>
      </li>
      <li class="nav-item">
        <form class="form-inline" action="" method="get">
			<input type="text" name="swd" class="form-control" placeholder="Search here"/>
			<button type="submit" class="btn btn-success">Search</button>
		</form>
      </li>    
    </ul>
  </div>
</nav><br />';

if (!isset($_GET["act"])) {
	if (isset($_GET["swd"])){
		$swd = $_GET["swd"];
	}else $swd = false;

	$cjs = fopen("content.json","r");
    echo '<div class="container">
   <div class="jumbotron">
        <h1>Welcome to HentaiNG</h1>
        <p>Hantai of New Generation!</p>
   </div>
	</div><div class="row clearfix">
				<div class="col-md-12 column">
					<div class="list-group">
						 <a href="#" class="list-group-item active">Galleries</a>';
	if (isset($_GET["swd"])){
		$dsp = gall_filt("name",$swd,false);
		for ($j=0;$j< count($dsp);$j+= 1){
			$i = $dsp[$j];
			echo '<div class="list-group-item"><span class="badge">' . gall_info($i,"count") . '</span>';
			echo '<a href="?' . http_build_query(array("act"=>"show","gid"=>$i,"page"=>1)) . '">' . gall_info($i,"name") . '</a></div>';
		}
	}else{
    	for ($i = 0; $i < count(dump_content()); $i+= 1) {
			echo '<div class="list-group-item"><span class="badge">' . gall_info($i,"count") . '</span>';
			echo '<a href="?' . http_build_query(array("act"=>"show","gid"=>$i,"page"=>1)) . '">' . gall_info($i,"name") . '</a></div>';
		}
	}
    echo '</div></div></div></div></div></div>';
}else{
	if ($_GET["act"] == "explore"){
		echo '<div class="container mt-3">';
		for ($i=1;$i<=10;$i++){
			$gid = rand(0,count(dump_content())-1);
			$file = scandir($appdir . '/' . gall_info($gid,"folder"));
			echo '<div class="media border p-3"><img src="' . gall_info($gid,"cover") . '" alt="Cover" class="mr-3 mt-3 rounded-circle" style="width:60px;">';
			echo '<div class="media-body"><h4>' . gall_info($gid,"name") . '</h4>';
			echo '<a href="?' . http_build_query(array("act"=>"show","gid"=>$gid,"page"=>1)) . '"><strong>Get Started!</strong></a>';
			echo '</div></div>';
		}
		echo '</div>';
	}else if ($_GET["act"] == "build_content"  && is_local($dom)) {
		echo "building content.json      ";
		$file = scandir($appdir . "/picture");
		$arr = array();
		$gid = -1;
		for ($i = 0; $i < count($file); $i+= 1){
			if ($file[$i][0] != '.'){
				$scpv = scan_path('picture/' . $file[$i]);
				echo $scpv;
				$gid = $gid + 1;$cvr = 0;
				$fl = scandir($appdir . '/' . $scpv);
				while (!(isset($pic_ext[pathinfo($fl[$cvr],PATHINFO_EXTENSION)]) && $pic_ext[pathinfo($fl[$cvr],PATHINFO_EXTENSION)] == true)) $cvr = $cvr + 1;
				array_push($arr,json_encode(array("gid"=>$gid,"name"=>$file[$i],"folder"=>$scpv,"count"=>strval(count(scandir($scpv)) - 2),"cover"=>$scpv . '/' . $fl[$cvr] ,"compressed"=>"")));
			}
		}
		$cjs = fopen("content.json","w+");
		fwrite($cjs,json_encode($arr));
		print_r($arr);
		echo "      Done.";
	}else if ($_GET["act"] == "show"){
		echo '<div class="container"><div class="row clearfix"><div class="col-md-9 column">';

		$gid = $_GET["gid"];$page = $_GET["page"];
		$file = scandir($appdir . '/' . gall_info($gid,"folder"));
		$acted = false;
		for ($i = 0;$i < count($file);$i+=1){
			if (isset($pic_ext[pathinfo($file[$i],PATHINFO_EXTENSION)]) && $pic_ext[pathinfo($file[$i],PATHINFO_EXTENSION)] == true) {
				$page -= 1;
				if ($page == 0){
					echo ' <img class="img-fluid" src="' . gall_info($gid,"folder") . '/' . $file[$i] .'" alt="pic">';
					break;
				}
			}
		}
	   echo '</div>';	

	   echo '<div class="col-md-3 column">';
	   echo '<ul class="list-group">';
	   echo '<li class="list-group-item">' . gall_info($gid,"name") .'</li>';
	   $prev_dis="";$next_dis="";
	   if ($_GET["page"] < 2) $prev_dis="disabled";
	   else if ($_GET["page"] >= count($file)-2) $next_dis="disabled";
	   echo '<div class="btn-group">';
	   echo '<a href="?' . http_build_query(array("act"=>"show","gid"=>$gid,"page"=>$_GET["page"]-1)) . '" class="btn btn-success ' . $prev_dis . '" role="button">prev page</a>';
	   echo '<a href="?' . http_build_query(array("act"=>"show","gid"=>$gid,"page"=>$_GET["page"]+1)) . '" class="btn btn-primary ' . $next_dis . '" role="button">next page</a>';
	   echo '</div>';

	   echo '</ul>';
       echo '</div></div></div>';
	}
}
echo '</body></html>';


