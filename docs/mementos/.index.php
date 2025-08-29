<!doctype html>
<html>

<head>
	<meta charset="UTF-8">
	<title>Mémentos des versions</title>
	<link rel="stylesheet" href=".style.css">
	<script src=".sorttable.js"></script>
</head>

<body>

<div id="container">

	<h1>Mémentos des versions IPREX</h1>

	<table class="sortable">
		<thead>
		<tr>
			<th>Fichier</th>
			<th>Version</th>
			<th>Taille <small>(octets)</small></th>
			<th>Dernière modification</th>
		</tr>
		</thead>
		<tbody>
		<?php

		$myDirectory=opendir(".");


		while($entryName=readdir($myDirectory)) {
			$dirArray[]=$entryName;
		}


		function findexts ($filename) {
			$filename=strtolower($filename);
			$exts = explode("[/\\.]", $filename) ;
			$n=count($exts)-1;
			$exts=$exts[$n];
			return $exts;
		}


		closedir($myDirectory);


		$indexCount=count($dirArray);


		sort($dirArray);


		for($index=0; $index < $indexCount; $index++) {

			// Allows ./?hidden to show hidden files
			if($_SERVER['QUERY_STRING']=="hidden")
			{$hide="";
				$ahref="./";
				$atext="Hide";}
			else
			{$hide=".";
				$ahref="./?hidden";
				$atext="Show";}
			if(substr("$dirArray[$index]", 0, 1) != $hide) {


				$name=$dirArray[$index];
				$namehref=$dirArray[$index];


				$extn=findexts($dirArray[$index]);


				$size=number_format(filesize($dirArray[$index]));


				$modtime=date("M j Y g:i A", filemtime($dirArray[$index]));
				$timekey=date("YmdHis", filemtime($dirArray[$index]));


				if($name=="."){$name=". (Current Directory)"; $extn="&lt;System Dir&gt;";}
				if($name==".."){$name=".. (Parent Directory)"; $extn="&lt;System Dir&gt;";}

				$version = str_replace(['.pdf', 'IPREX ', 'v'], '', $name);
				$version = str_replace('-', '.', $version);


				print("
          <tr>
            <td><a href='./$namehref'>$name</a></td>
            <td><a href='./$namehref'>$version</a></td>
            <td><a href='./$namehref'>$size</a></td>
            <td sorttable_customkey='$timekey'><a href='./$namehref'>$modtime</a></td>
          </tr>");
			}
		}
		?>
		</tbody>
	</table>

</div>

</body>

</html>