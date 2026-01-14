<?php

session_start();

/*=================You can uncomment this vvvvvvvvvvvvvvvvvvvvvvvvvvv
$user=null;
$retval=null;
exec('whoami', $user, $retval);

if ( @$_GET['user'] == $user[0] && @$_SESSION['user'] != $user[0]) {
 $_SERVER['PHP_AUTH_USER'] = $_GET['user'];
 $_SESSION['user'] = $_GET['user'];
 //$_GET['user'] = "";
}

if (@$_SERVER['PHP_AUTH_USER'] != $user[0] && @$_SESSION['user'] != $user[0]) {
    header('WWW-Authenticate: Basic realm="My Realm"');
    header('HTTP/1.0 401 Unauthorized');
    echo '<h3>Sorry, you cannot continue!</h3><h3>Type "whoami" in the Termux console to see "username".</h3>';
    exit;
}

if (@$_SERVER['PHP_AUTH_USER'] == $user[0]) {
 $_SESSION['user'] = $user[0];
}
=================You can uncomment this ^^^^^^^^^^^^^^^^^^^^^^^^^^^*/
//Warning: This is for home use (personal, family, friends...) on your own networks and the above basic authorization will not change this.
//Do not upload (install) this on public servers, nor use it on public, untrusted networks.

$self = basename($_SERVER['PHP_SELF']);
$badsym = "\\\\/:%&*?\"<>|";//also in upload.php
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? $_GET['page'] : 1;
$error="";
$formatdate = 'd.m.y H:i';
$decimals = 2;
$exclude = array('css', 'upload.php', 'download.php', 'favicon.ico');
$imgext = array('jpg', 'jpeg', 'webp', 'tiff', 'tif', 'wave', 'wav', 'dng');//for EXIF
$resultsonpage = 10;
$pdir = dirname($_SERVER['PHP_SELF']);
$total = 0;
$total_size = 0;

if (isset($_GET['reset'])) {$_SESSION = array();session_destroy();echo "<meta http-equiv='refresh' content='0;url=".$self."'>";}

$dir = null;
$_GET['dir'] = trim((string)@$_GET['dir'], '/ ');
$_GET['dir'] = str_replace(array('/..', '../'), '', (string)@$_GET['dir']);
if (!empty($_GET['dir']) && $_GET['dir'] != '..' && is_dir($_GET['dir'])) {
	$ignoreddir = false;
	foreach (explode('/', $_GET['dir']) as $foldername) {
		if (!empty($exclude) && is_array($exclude) && in_array($foldername, $exclude)) {
			$ignoreddir = true;
			break;
		}
	}
	if (!$ignoreddir) $dir = $_GET['dir'];
}
if ($_GET['dir']==='' || $_GET['dir']===null) unset($_GET['dir']);

$_SESSION["ucount"] = 0;
$_SESSION["uerrors"] = 0;
$_SESSION["self"] = $self;

?>
<!DOCTYPE HTML>
<html lang="en-US">
<head>
<?php

//move file
if (isset($_POST['moveconfirm']) && $_POST['moveconfirm'] == "Cancel") {
 $moveback = $_SESSION['moveback'];
 $_SESSION['movefile'] = false;
 $_POST = array();
 $_SESSION['moveback'] = false;
 $_SESSION['isdir'] = false;
 $_SESSION['movename'] = false;
// echo "<meta http-equiv='refresh' content='0;url=".$self."?dir=".(urlencode($dir) ? urlencode($dir) : '')."&page=1'>";
 echo "<meta http-equiv='refresh' content='0;url=".$moveback."'>";
}

if (isset($_POST['moveconfirm']) && $_POST['moveconfirm'] == "Move" && (is_file($_SESSION['movefile']) || is_dir($_SESSION['movefile']))) {
 if (strpbrk($_POST['newfilename'], $badsym) === FALSE) {
  if ($_SESSION['isdir'] == 'N') {
	$fileinfo = pathinfo($_SESSION['movefile']);
	$newfilename = $_POST['newfilename'];
	$newpathfile = ($dir=='' ? '' : $dir.'/').$newfilename.'.'.$fileinfo['extension'];
  }
  if ($_SESSION['isdir'] == 'Y') {
	$newpathfile = ($dir=='' ? '' : $dir.'/').$_POST['newfilename'];
  }

   if (!file_exists($newpathfile)) {
    rename($_SESSION['movefile'], $newpathfile);
    if ($_SESSION['isdir'] == 'N' && file_exists($fileinfo['dirname'].'/'.$fileinfo['filename'].'--th.png')) {
     rename($fileinfo['dirname'].'/'.$fileinfo['filename'].'--th.png', ($dir=='' ? '' : $dir.'/').$newfilename.'--th.png');
    }
    $_SESSION['movefile'] = false;
    $_POST = array();
    $_SESSION['moveback'] = false;
	$_SESSION['isdir'] = false;
	$_SESSION['movename'] = false;
    echo "<meta http-equiv='refresh' content='0;url=".$self."?dir=".(urlencode($dir) ? urlencode($dir) : '')."&page=1'>";
   }
   else {
	$error= ($_SESSION['isdir'] == 'Y' ? 'The directory "'.$_POST['newfilename'] : 'The file "'.$newfilename.'.'.$fileinfo['extension']).'" already exists here..';
    $_POST = array();
   }
 }
 else {
  $error='Please, enter a valid file name..';
  $_POST = array();
 }
}

if( isset($_POST['fmname']) && isset($_POST['movefile']) ) {
 $_SESSION['movefile'] = ($dir=='' ? '' : $dir.'/').$_POST['fmname'];
 $_SESSION['isdir'] = $_POST['isdir'];
 $_SESSION['moveback'] = $_POST['moveback'];
 $_SESSION['movename'] = $_POST['fmname'];
 $_POST = array();
 echo "<meta http-equiv='refresh' content='0;url=".$self."?dir=".(urlencode($dir) ? urlencode($dir) : '')."&page=1'>";
}
//move file, continued on body beginning

if(isset($_POST['newdirname']) && isset($_POST['createdir']) ){
 if (strpbrk($_POST['newdirname'], $badsym) === FALSE) {
  if (!file_exists(($dir=='' ? '' : $dir.'/').$_POST['newdirname'])) {
   mkdir(($dir=='' ? '' : $dir.'/').rawurldecode(trim($_POST['newdirname'])), 0777, true);
   $_POST = array();
   echo "<meta http-equiv='refresh' content='0;url=".$self."?dir=".(urlencode($dir) ? urlencode($dir) : '')."&sort=time&desc=1&page=1'>";
  }
  else {
   $error='Тhe directory "'.$_POST['newdirname'].'" already exists here..';
   $_POST = array();
  }
 }
 else {
  $error='Please, enter a valid directory name..';
  $_POST = array();
 }
}

if(isset($_POST['delete']) && isset($_POST['fname']) ){
  $file=$_POST['fname'];
  $deldir = ($dir=='' ? '' : $dir.'/');

					$mime = mime_content_type($deldir.$file);
					if(strstr($mime, "video/")) {
						$fileinfo = pathinfo($deldir.$file);
						if (file_exists($deldir.$fileinfo['filename'].'--th.png')) {
							unlink($deldir.$fileinfo['filename'].'--th.png');
						}
					}

  unlink($deldir.$file);
  $_POST = array();
  //echo '<script>alert("Success, Your file '.$deldir.'/'.$file.' has been deleted.");</script>';
  if ($_SESSION['ic']==1 && $page>1)
    echo "<meta http-equiv='refresh' content='0;url=".$self."?dir=".(urlencode($dir) ? urlencode($dir) : '')."&page=".($page-1)."'>";
  else
    echo "<meta http-equiv='refresh' content='0'>";
}

if(isset($_POST['ddelete']) && isset($_POST['dname']) ){
  $dname=$_POST['dname'];
  $deldir = ($dir=='' ? '' : $dir.'/');
  rmdir($deldir.$dname);
  $_POST = array();
  //echo '<script>alert("Success, Your file '.$deldir.'/'.$file.' has been deleted.");</script>';
   if ($_SESSION['ic']==1 && $page>1)
    echo "<meta http-equiv='refresh' content='0;url=".$self."?dir=".(urlencode($dir) ? urlencode($dir) : '')."&page=".($page-1)."'>";
  else
    echo "<meta http-equiv='refresh' content='0'>";
}


	function ext($filename) {
	 return strtolower(substr( strrchr( $filename,'.' ),1 ));
	}

	function dir_is_empty($dirname) {
	 if (!is_dir($dirname)) return false;
	 foreach (scandir($dirname) as $file) {
      if (!in_array($file, array('.','..'))) return false;
	 }
	 return true;
	}

	function listdir($path) {
		global $self, $total, $total_size, $exclude, $imgext;
		$List = array();
		if (($dh = @opendir($path)) === false) return $List;
		if (substr($path, -1) != '/') $path .= '/';
		while (($file = readdir($dh)) !== false) {
			$isdir = is_dir($path . $file);
			if ($file == $self) continue;
			if ($file == '.' || $file == '..') continue;
			if (!empty($exclude) && is_array($exclude) && in_array($file, $exclude) || str_ends_with($file, '--th.png') ) continue;
			if ((!is_dir($path . $file) || !is_writable($path . $file)) && isset($_SESSION['movefile']) && $_SESSION['movefile'] !== false) continue;
			if (isset($_SESSION['isdir']) && $_SESSION['isdir'] == 'Y' && './'.$_SESSION['movefile'] == $path . $file ) continue;
		    if (in_array( ext($path . $file), $imgext)) {
			 $exif = exif_read_data($path . $file);
			 if (isset($exif['DateTime']) || isset($exif['DateTimeOriginal']))
				 $ctime = isset($exif['DateTimeOriginal']) ? strtotime($exif['DateTimeOriginal']) : strtotime($exif['DateTime']);
			 else $ctime = 0;
			}
			else $ctime = 0;
			$List[] = array('name' => $file, 'isdir' => $isdir, 'size' => $isdir ? 0 : filesize($path . $file), 'time' => filemtime($path . $file),
			'ctime' => $ctime > 0 ? $ctime : 0);
			$total++;
			$total_size += $isdir ? 0 : filesize($path . $file);
		}
		return $List;
	}

	$List = listdir('.' . (empty($dir) ? '' : '/' . $dir));
	$total_pages = $total;

	function sortbyname($a, $b) {
		return ($a['isdir'] == $b['isdir'] ? strtolower($a['name']) > strtolower($b['name']) : $a['isdir'] < $b['isdir']) ? 1 : -1;
	}

	function sortbysize($a, $b) {
		return ($a['isdir'] == $b['isdir'] ? $a['size'] > $b['size'] : $a['isdir'] < $b['isdir']) ? 1 : -1;
	}

	function sortbytime($a, $b) {
		return ($a['time'] > $b['time']) ? 1 : -1;
	}

	function sortbyctime($a, $b) {
		return ($a['ctime'] > $b['ctime']) ? 1 : -1;
	}

	switch (@$_GET['sort']) {
		case 'size': $sort = 'size'; usort($List, 'sortbysize'); break;
		case 'time': $sort = 'time'; usort($List, 'sortbytime'); break;
		case 'ctime': $sort = 'ctime'; usort($List, 'sortbyctime'); break;
		default    : $sort = 'name'; usort($List, 'sortbyname'); break;
	}

	$sortdesc = (@$_GET['desc'] == '1');
	if ($sortdesc) $List = array_reverse($List);

	if ($pdir != '/' && empty($dir)) array_unshift($List, array(
		'name' => '..',
		'isparent' => true,
		'isdir' => true,
		'size' => 0,
		'time' => 0,
		'ctime' => 0
	));

	if (!empty($dir)) array_unshift($List, array(
		'name' => '..',
		'isparent' => false,
		'isdir' => true,
		'size' => 0,
		'time' => 0,
		'ctime' => 0
	));

	function Linker($nquery) {
		global $self;
		$get = $_GET;
		foreach ($nquery as $k => $v) if ($v===null || trim($v)==='') unset($get[$k]); else $get[$k] = $v;
		foreach ($get as $k => $v) if ($v===null || trim($v)==='' || $k=='page' && $v=='1' || $k=='sort' && $v=='name') unset($get[$k]); else $get[$k] = urlencode($k) . '=' . urlencode($v);
		return empty($get) ? $self : $self . '?' . implode('&', $get);
	}

	function Navigation($p, $d) {
		$crumbs = htmlentities($p);
		$crumbs = sprintf('<a href="%s">%s</a>', htmlentities(Linker(array('dir' => '', 'page'=>1))), $crumbs);
		if (!empty($d)) {
			if ($p != '/') $crumbs .= '/';
			$parts = explode('/', trim($d, '/'));
			foreach ($parts as $i => $part) {
				$crumbs .= sprintf('<a href="%s">%s</a>', htmlentities(Linker(array('dir' => implode('/', array_slice($parts, 0, $i + 1)), 'page'=>1))), htmlentities($part));
				if (count($parts) > ($i + 1)) $crumbs .= ' / ';
			}
		}
		return $crumbs;
	}

	function formatbytes($bytes, $precision = 2) {
     $units = [' B', ' KB', ' MB', ' GB', ' TB'];
     $bytes = max($bytes, 0);
     $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
     $pow = min($pow, count($units) - 1);
     $bytes /= pow(1024, $pow);
     return round($bytes, $precision) . $units[$pow];
	}

?>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?php echo $_SERVER['SERVER_NAME'].($dir ? '/' : '').$dir;?></title>
<link type="text/css" rel="stylesheet" href="/css/style.css" media="all" />
</head>

<body>
<?php if($error != "") echo '<p id="error" style="background:white;color:red;padding:5px">'.$error.'</p>';?>
<div id="container">

 <div class="header">
  <?php //move file
   if (isset($_SESSION['movefile']) && $_SESSION['movefile']) : ?>
    <br>
    <form method="post" onSubmit="window.location.reload()" style="float:right">
     <?php
     $fileinfo = pathinfo($_SESSION['movefile']);
	 $newfiledirname = ($_SESSION['isdir']=='N' ? $fileinfo['filename'] : $_SESSION['movename']);
	 echo 'Select (or create and select) a folder to move<br> <input id="newfilename" type="text" name="newfilename" value="'.$newfiledirname.'" required>'.($_SESSION['isdir']=='N'? '.'.$fileinfo['extension'] : '');?>
     <input type="submit" class="btn adddir" name="moveconfirm" value="Move">
     <input type="submit" class="btn adddir" name="moveconfirm" value="Cancel" onClick="document.getElementById('newfilename').value ='<?php echo $newfiledirname;?>'">
     <br>Currently selected destination folder - <?php echo ($dir=='' ? 'Home' : $dir);?>
    </form>
  <?php endif;//move file ?>

 <?php
  $dir=='' ? $cdir = '.' : $cdir = $dir;
  if ( !is_writable($cdir) /*|| $dir ==''*/ ) {
   echo str_replace('Directory "."','The directory ','<p class="ronly">Directory "'.$cdir.'" is read only!</p>');
  }
   else {echo '<p class="ronly">&nbsp;</p>';}
 ?>
 <h2 class="slogan<?php echo (isset($_SESSION['movefile']) && $_SESSION['movefile'] ? ' hide' : '');?>"><?php echo ($dir=='' ? 'From anywhere to everywhere at' : '&nbsp;')?></h2>
 <h1><?php echo str_replace('>/</a>','>Home</a>'.($dir=='' ? '' : ' / '), Navigation($pdir, $dir));?></h1>
 <?php if (isset($_SESSION['movefile']) && $_SESSION['movefile']) echo '<h2>&nbsp;</h2>';
       else echo '<h2>'.($total===0 ? 'No ' : $total).' item'.($total==1 ? '' : 's').' in this directory'.($total_size>0 ? ', '.formatbytes($total_size, $decimals) : '.').'</h2>';
 ?>
 <?php if ( is_writable($cdir) /*|| $dir ==''*/ ) : ?>
	<form method="post" onSubmit="return confirm('A subdirectory '+document.getElementById('newdirname').value+' will be created?');">
    	<input type="text" name="newdirname" placeholder="Create a new folder here" required maxlength="30" id="newdirname">
        <input type="submit" name="createdir" value="Create" class="btn adddir">
	</form>
  <!-- uploader -->
 <div class="uploader<?php echo (isset($_SESSION['movefile']) && $_SESSION['movefile'] ? ' hide' : '');?>">
	<form name="upload_form" method="POST" id="upload_form" enctype="multipart/form-data">
		<input type="file" name="file[]" id="file" class="file" multiple required>
		<label for="file" class="file" id="file_label">
			<span class="block white">Choose file(s) to upload here</span>
		</label>
		<input type="hidden" name="dir" id="dir">
		<input type="hidden" name="fu" id="fu">
		<input type="hidden" name="badfile" id="badfile">
		<button type="button" value="Upload" class="submit" id="submit">Upload</button>
		<div id="pass"></div>
	</form>
 </div>
  <!-- uploader -->
 <?php endif; ?>
 </div>

 <div class="content">
 <?php //var_dump($_GET);?>
	<ul id="sort">
		<li>
			<a href="<?php echo Linker(array('sort' => 'size', 'desc' => (!$sortdesc && $sort == 'size') ? '1' : ''/*, 'page'=>1*/)) ?>" class="size <?php if ($sort == 'size') echo $sortdesc ? 'desc' : 'asc' ?>"><span>Size</span></a>
			<a href="<?php echo Linker(array('sort' => 'time', 'desc' => (!$sortdesc && $sort == 'time') ? '1' : ''/*, 'page'=>1*/)) ?>" class="date <?php if ($sort == 'time') echo $sortdesc ? 'desc' : 'asc' ?>"><span>Uploaded</span></a>
			<a href="<?php echo Linker(array('sort' =>  'name' , 'desc' => (!$sortdesc && $sort == 'name') ? '1' : ''/*, 'page'=>1*/)) ?>" class="sname <?php if ($sort == 'name') echo $sortdesc ? 'desc' : 'asc' ?>"><span>Name</span></a>
			<a href="<?php echo Linker(array('sort' => 'ctime', 'desc' => (!$sortdesc && $sort == 'ctime') ? '1' : ''/*, 'page'=>1*/)) ?>" class="cdate <?php if ($sort == 'ctime') echo $sortdesc ? 'desc' : 'asc' ?>"><span>Created</span></a>
		</li>
	</ul>
	<ul id="main">
	<?php
    	$i=0;
		$ic=0;
		$o = ($page*$resultsonpage-$resultsonpage) ;
		if ($dir != '') $o += 1;//..
	?>
	<?php foreach ($List as $row):

    if ($i++ < $o && $row['name'] != '..' ) continue;
    if ($i > $o + $resultsonpage) break;
	if ($row['name'] != '..') $ic += 1;
					$thumb=false;$vthumb=false;$exif=array();
					if ( in_array( ext($row['name']), $imgext) && !$row['isdir']) {
						$exif = exif_read_data(($dir=='' ? '' : $dir.'/').$row['name']);
						$thumb = exif_thumbnail(($dir=='' ? '' : $dir.'/').$row['name'], $width, $height, $type);
					 if ($thumb!==false && (isset($exif['THUMBNAIL']['Orientation']) || isset($exif['Orientation']))) {
						 if (isset($exif['Orientation'])) $thumbo=$exif['Orientation'];
						 if (isset($exif['THUMBNAIL']['Orientation'])) $thumbo=$exif['THUMBNAIL']['Orientation'];
						 switch ($thumbo) {
							case 1:
								$imgro=false;
								break;
							case 2:
								$imgro='transform: rotateY(180deg)';
								break;
							case 3:
								$imgro='transform: rotate(180deg)';
								break;
							case 4:
								$imgro='transform: rotate(180deg) rotateY(180deg)';
								break;
							case 5:
								$imgro='transform: rotate(270deg) rotateY(180deg)';
								break;
							case 6:
								$imgro='transform: rotate(90deg)';
								break;
							case 7:
								$imgro='transform: rotate(90deg) rotateY(180deg)';
								break;
							case 8:
								$imgro='transform: rotate(270deg)';
								break;
							default:
								$imgro=false;
						}
					 }
					}

					$file_path = ($dir == '' ? '' : $dir.'/').$row['name'];
					$mime = mime_content_type($file_path);
					if(strstr($mime, "video/")) {
						$fileinfo = pathinfo($file_path);
						if (file_exists(($dir == '' ? '' : $dir.'/').$fileinfo['filename'].'--th.png')) {
							$vthumb=$dir.'/'.rawurlencode($fileinfo['filename']).'--th.png';
						}
					}
			?>

			<li class="row">

				<span class="size"><?php echo $row['isdir'] ? '-' : formatbytes($row['size'], $decimals) ?></span>

				<span class="date"><?php echo (@$row['isparent'] || empty($row['time'])) ? '-' : date($formatdate, $row['time']) ?></span>

<?php //print_r($exif);?>
				<?php
					if ($row['isdir'] && !@$row['isparent']) {
						if ($row['name'] == '..') {
							$rowurl = Linker(array('dir' => substr($dir, 0, strrpos($dir, '/')), 'page'=>1));
						} else {
							$rowurl = Linker(array('dir' => (empty($dir) ? '' : (string)$dir . '/') . $row['name'], 'page'=>1));
						}
					} else {
							$rowurl = (empty($dir) ? '' : str_replace(['%2F', '%2f'], '/', rawurlencode((string)$dir)) . '/') . rawurlencode($row['name']);
					}
				?>
				<a href="<?php echo htmlentities($rowurl) ?>" class="name<?php echo ($row['isdir'] ? ' directory' : ' file');?>">
					<div class="icon <?php echo ($row['isdir'] ? 'directory' : ext($row['name']));?>">
					<?php
					   if ($row['name'] == '..') {
                         echo '<img width="50" height="38" src="/css/back.png" class="updir">';
					   }
					   if ($thumb!==false) {
                         echo '<img width="'.$width.'" height="'.$height.'" src="data:'.image_type_to_mime_type($type).';base64,'.base64_encode($thumb).'" style="width:50px;height:50px;object-fit: cover;'.(isset($imgro) ? $imgro : '').'">';
                       }
                       if ($vthumb!==false) {
                         echo '<img width="50" height="50" src="/css/videoframe.png"><img src="'.$vthumb.'" class="vthumb">';
                       }
                    ?>
					</div>

					<div class="itemname"><?php echo htmlentities($row['name']);?>
					</div>
				</a>
				<?php if ($row['isdir']) :?>
					<span class="ddir">
				     <?php if ($row['isdir'] && is_writable($cdir) && is_writable($cdir.'/'.$row['name']) && @$_SESSION['movefile'] == false && $row['name']!='..') :?>
						<div class="dmform">
						 <form method="post" onSubmit="window.location.reload();">
							<input type="hidden" name="fmname" value="<?php echo $row['name'];?>">
							<input type="hidden" name="isdir" value="Y">
							<input type="hidden" name="moveback" value="<?php echo Linker($_GET);?>">
							<input type="submit" name="movefile" value="➡️ MOVE / RENAME" class="btn btn-danger move">
						 </form>
						</div>
				     <?php endif; ?>
					 <?php if ($row['isdir'] && is_writable($cdir) && dir_is_empty($cdir.'/'.$row['name']) && @$_SESSION['movefile'] == false) :?>
						<div class="dmform">
						 <form method="post">
							<input type="hidden" name="dname" value="<?php echo $row['name'];?>">
							<input type="submit" name="ddelete" value="❌ DELETE EMPTY FOLDER" class="btn btn-danger delbutton"  onClick="return confirm('Do you really want to delete directory <?php echo $row['name'];?>?');">
						</form>
					   </div>
					 <?php endif; ?>
					</span>
				<?php endif; ?>
				<?php if (!$row['isdir']) :?>
					<span class="exif">
						<?php echo ($row['ctime'] > 0 ? date($formatdate, $row['ctime']).'&nbsp;&nbsp;&nbsp;'.$exif['COMPUTED']['Width'].' x '.$exif['COMPUTED']['Height'].' px&nbsp;&nbsp;&nbsp;'.(isset($exif['Model']) ? $exif['Model'] : @$exif['Software']) : '');?>
						<br>
						<div class="download"><a style="text-decoration:none!important" href="download.php?file=<?php echo urlencode($dir.($dir ? '/' : '').$row['name']);?>"><span class="download">⬇️ DOWNLOAD</span></a></div>

						<?php if (is_writable($cdir)) :?>
							<div class="dmform"><form method="post" onSubmit="window.location.reload();">
							<input type="hidden" name="fmname" value="<?php echo $row['name'];?>">
							<input type="hidden" name="isdir" value="N">
							<input type="hidden" name="moveback" value="<?php echo Linker($_GET);?>">
							<input type="submit" name="movefile" value="➡️ MOVE / RENAME" class="btn btn-danger move">
							</form></div>

							<div class="dmform"><form method="post">
							<input type="hidden" name="fname" value="<?php echo $row['name'];?>">
							<input type="submit" name="delete" value="❌ DELETE" class="btn btn-danger delbutton" onClick="return confirm('Do you really want to delete the file <?php echo $row['name'];?>?');">
							</form></div>
						<?php endif; ?>
					</span>
				<?php endif; ?>
			</li>

		<?php endforeach;$_SESSION['ic']=$ic;$exif=array();unset($thumb);unset($vthumb);$List=array();$row=array();?>

	</ul>
	<?php if ($total_pages > $resultsonpage ): ?>
		<ul class="pagination">
			<?php if ($page > 1): ?>
				<li class="prev"><a href="<?php echo Linker(array('page'=>$page-1));?>">Prev</a></li>
			<?php endif; ?>
			<?php if ($page > 3): ?>
				<li class="start"><a href="<?php echo Linker(array('page'=>1));?>">1</a></li>
				<li class="dots">...</li>
			<?php endif; ?>
			<?php if ($page-2 > 0): ?><li class="page"><a href="<?php echo Linker(array('page'=>$page-2));?>"><?php echo $page-2 ?></a></li><?php endif; ?>
			<?php if ($page-1 > 0): ?><li class="page"><a href="<?php echo Linker(array('page'=>$page-1));?>"><?php echo $page-1 ?></a></li><?php endif; ?>
				<li class="currentpage"><a href="<?php echo Linker(array('page'=>$page));?>"><?php echo $page ?></a></li>
			<?php if ($page+1 < ceil($total_pages / $resultsonpage)+1): ?><li class="page"><a href="<?php echo Linker(array('page'=>$page+1));?>"><?php echo $page+1 ?></a></li><?php endif; ?>
			<?php if ($page+2 < ceil($total_pages / $resultsonpage)+1): ?><li class="page"><a href="<?php echo Linker(array('page'=>$page+2));?>"><?php echo $page+2 ?></a></li><?php endif; ?>
			<?php if ($page < ceil($total_pages / $resultsonpage)-2): ?>
				<li class="dots">...</li>
				<li class="end"><a href="<?php echo Linker(array('page'=>ceil($total_pages / $resultsonpage)));?>"><?php echo ceil($total_pages / $resultsonpage) ?></a></li>
			<?php endif; ?>
			<?php if ($page < ceil($total_pages / $resultsonpage)): ?>
				<li class="next"><a href="<?php echo Linker(array('page'=>$page+1));?>">Next</a></li>
			<?php endif; ?>
		</ul>
	<?php endif; ?>

  <div class="footer">
   <span><?php echo formatbytes(disk_free_space(__DIR__),$decimals).' Freedom';?><span>
   <span style="float:right">&copy; Boycho Dobrev</span>
  </div>

 </div>

</div>

<?php if ( is_writable($cdir) /*|| $dir ==''*/ ) : ?>
<!-- uploader -->
<script>// Files Counter
	//var badsym = /[`!@#$%^&*()+=\[\]{};':"\\|,<>\/?~]/;
	const badsym = /[<?php echo $badsym;?>]/;
	var element = function (id) {
		return document.getElementById(id);
	}

	const input = element("file");

	input.addEventListener('change', function() {
	const label = element("file_label");
	element("pass").innerHTML = "";

     if (input.files.length == 1) {
		  var file = input.files[0];
		  if (!badsym.test(file.name)) {
		   label.querySelector('span').innerHTML = file.name;
		  }
		  else {
		   label.querySelector('span').innerHTML = "Unacceptable File name!";
		  }
	 }
	 else {

		  var filesn="";
		  var filessize=0;
		  for (var i = 0; i < input.files.length; ++i) {
			file = input.files[i];
			filesn += '<p style="text-align:left">'+(!badsym.test(file.name) ? file.name+' - '+hFileSize(file.size, false, 1) : '--Unacceptable File name!')+'</p>';
			if (!badsym.test(file.name)) filessize += file.size;
		  }
		  label.querySelector('span').innerHTML = (input.files.length + " files are selected, ~" + hFileSize(filessize, false, 1));
		  element("pass").innerHTML = filesn;
	 }
	})

	element("submit").addEventListener("click", function () {
	 const file1 = input.files[0];
	 if (input.files.length == 1 && badsym.test(file1.name)) {
		element("upload_form").reset();
		element("file_label").querySelector('span').innerHTML = "Choose file(s) to upload here";
	 }
	 else {
		element("pass").innerHTML = "";
        if (!input.files.length) {
			document.forms['upload_form'].reportValidity();
		}
		else {
			var fu = 0;
			for (i = 0; i < input.files.length; ++i) {
			 file = input.files[i];
			 if (!badsym.test(file.name)) {
				fu += 1;
			 }
			}

			for (i = 0; i < input.files.length; ++i) {
			   file = input.files[i];
			   if (!badsym.test(file.name)) {
			   	element("pass").insertAdjacentHTML("beforeend",'<div class="progress" id="progress-bar-'+i+'"><div id="bar-'+i+'" class="progress-bar active" style="width:0%">0%</div></div><div id="stats-'+i+'" class="white" style="text-align:left;font-weight:normal"><p id="status-'+i+'"></p><p id="loaded_n_total-'+i+'"></p><p id="file-'+i+'">'+file.name+' : <span id="n_loaded-'+i+'"></span><span> / </span><span id="n_total-'+i+'"></span><span id="n_per-'+i+'"></span></p></div>');
				var formdata = new FormData();
				formdata.append("file", file);
				formdata.append("fu", fu);
				formdata.append("dir", "<?php echo $dir;?>");
				formdata.append("badfiles", input.files.length - fu);
				var xhttp = new XMLHttpRequest();
				xhttp.upload.addEventListener("progress", progressHandler.bind(null, i), false);
				xhttp.addEventListener("load", completeHandler.bind(null, i), false);
				xhttp.addEventListener("error", errorHandler.bind(null, i), false);
				xhttp.addEventListener("abort", abortHandler.bind(null, i), false);
				xhttp.open("POST", "/upload.php");
				xhttp.send(formdata);
			 }

			 else {
				 element("pass").insertAdjacentHTML("beforeend",'<div id="stats-'+i+'" class="white" style="text-align:left;font-weight:normal"><p id="status-'+i+'">Unacceptable File name!</p></div>');
			 }
			}
		}
	 }
	});

	function progressHandler(num,event) {
    	var link = element("status-"+num);
		var elem = element("bar-"+num);
		var percent = (event.loaded / event.total) * 100;
		var width = Math.round(percent);
		var frame = Math.round(percent);
		var id = setInterval(frame, 100);
		elem.style.width = width + '%';
		elem.innerHTML = width * 1 + '%';
		var load = (event.loaded / (1024 * 1024));
		var loaded = Math.round(load);
		var total = (event.total / (1024 * 1024));
		var totalr = Math.round(total);
		element("n_total-"+num).innerHTML = +totalr + " MB";
		element("n_loaded-"+num).innerHTML = +loaded + " MB";
		if(width == 100){
			elem.classList.add("progress-bar-success");
			elem.innerHTML = "Success";
			link.classList.remove("hide");
			link.classList.add("show");
		}
	}

	function completeHandler(num,event) {
		element("progress-bar-"+num).style.display = "none";
	    element("loaded_n_total-"+num).style.display = "none";
	    element("file-"+num).style.display = "none";
		element("status-"+num).innerHTML = event.target.responseText;
		if (num===input.files.length-1) {
			element("pass");
		}
	}

	function errorHandler(num,event) {
		element("progress-bar-"+num).style.display = "none";
	    element("loaded_n_total-"+num).style.display = "none";
	    element("file-"+num).style.display = "none";
		element("status-"+num).innerHTML = "Upload Failed";

	}

	function abortHandler(num,event) {
		element("progress-bar-"+num).style.display = "none";
	    element("loaded_n_total-"+num).style.display = "none";
	    element("file-"+num).style.display = "none";
		element("status-"+num).innerHTML = "Upload Aborted";
	}

	function hFileSize(bytes, si, dp) {
		var thresh = si ? 1000 : 1024;
		if (Math.abs(bytes) < thresh) {
			return bytes + ' B';
		}
		var units = si ? ['kB', 'MB', 'GB', 'TB'] : ['KiB', 'MiB', 'GiB', 'TiB'];
		var u = -1;
		var r = Math.pow(10, dp);
		do {
			bytes /= thresh;
			++u;
		} while (Math.round(Math.abs(bytes) * r) / r >= thresh && u < units.length - 1);
		return bytes.toFixed(dp) + ' ' + units[u];
	}

	if (element("error") != null) {
	 function hideError() {
	 	element("error").style.display = "none";
	 }
	 setTimeout(hideError, 5000);
	}

</script>
<!-- uploader -->
<?php endif; ?>

</body>
</html>
