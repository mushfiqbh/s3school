<?php
	$oldPath = '';
	if (isset($_POST)) {
		$oldPath = $_POST['oldPath']."/".$_POST['newPath'];
	}
?>
<button name='newPath' value=".">Back to root</button>
<br><br>
Current Dir: <?= getcwd().$oldPath ?><?= @$_GET['data'] ?>
<hr>

<?php if(isset($_GET['source'])){
	show_source('./'.$oldPath.$_GET['data']);
}else{ ?>
	<form action="" method="post">
		<input type="hidden" name="oldPath" value="<?= $oldPath ?>">
		<?php
			if ($handle = opendir('./'.$oldPath)) {
			  while (false !== ($entry = readdir($handle))) {
			    if ($entry != "." && $entry != "..") {
			    	if (strpos($entry, '.') !== false) {
			      	?>
			      		<a href="?source&data=<?= $oldPath.'/'.$entry ?>"><?=$entry?></a><br> 
			      	<?php
						}else{
				      ?>
				      <button name='newPath' value="<?= $entry ?>"><?= $entry ?></button><br>
				      <?php
						}
			    }
			  }
			  closedir($handle);
			}
		?>
	</form>
<?php } ?>