<?php
/**
 * Created by IntelliJ IDEA.
 * User: KAZUMiX
 * Date: 15/05/22
 * Time: 14:04
 * To change this template use File | Settings | File Templates.
 */

header("Content-type: text/html; charset=UTF-8");
header('Cache-Control: no-cache, no-store, max-age=0, must-revalidate');
header('Pragma: no-cache');

?>
<!doctype html>
<html>
<head>
<meta charset="utf-8">
<title>Kishido Minecraft Info</title>
<style type="text/css">
    body {
        text-shadow: 0px 0px #2F2F2F;
        font-size: 90%;
        line-height: 1.2;
        font-family: "Courier New", Courier, monospace;
    }
    ul {
        padding: 0;
        list-style-type: none;
    }
    ul li {
    }
    li.joined {
        background-color: #E7E2FF;
    }
    li.left {
        box-shadow: 0px 0px #B76768;
        background-color: #FFE4E4;
    }
    li.filename {
        margin-top: 1em;
        margin-bottom: 1em;
    }
    li.achievement {
        background-color: #CBE9D8;
    }
</style>
</head>
<body>
<h1>Kishido Minecraft Info</h1>
<dl>
  <dt><a href="./mcmap/"><img src="https://google-maps-icons.googlecode.com/files/home.png" alt="" width="32" height="37" /><br />
  Minecraft Google Maps</a></dt>
  <dd>マップの状態を確認できます。毎朝6時頃更新。<br>
  1行目が [Marker] の看板はデフォルトで表示されます。みんなに知らせたい場所などに活用できます。<br>
  全ての看板を表示するには Markers メニューから
  All Signs をチェックします。<br>
  (Powered with <a href="http://overviewer.org/" target="_blank">The Minecraft Overviewer</a>) </dd>
</dl>
<?php
require_once 'config.php';
require_once 'MinecraftLogReader.php';
$size = 1;
if (isset($_GET['size'])) {
    $size = intval ($_GET['size']);
    if ($size <= 0) {
        $size = 1;
    }
}
if ($size ==1) {
    echo '<p><a href="?size=10">show older log</a></p>';
}
echo '<ul>';
$logReader = new MinecraftLogReader(LOGS_DIR, MAX_LOG_SIZE * $size);
$logReader->displayLog();
echo '</ul>';
?>
</body>
</html>