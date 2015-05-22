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
    <title>Minecraft Simple Log View</title>
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
    </style>
</head>

<body>
<ul>
<?php
$filename = 'latest.log';

$fp = fopen($filename, 'rb');

while (!feof($fp)) {
    $line = fgets($fp);
    if (mb_strpos($line, '[Server thread/INFO]') !== false) {
        if (mb_strpos($line, 'logged in with entity id') !== false) {
            continue;
        }
        $className = '';
        if (mb_strpos($line, 'joined the game') !== false) {
            $className = 'joined';
        } else if (mb_strpos($line, 'left the game') !== false) {
            @$className = 'left';
        }
        echo "<li class=\"{$className}\">{$line}</li>";
    }
}
?>
</ul>
</body>
</html>