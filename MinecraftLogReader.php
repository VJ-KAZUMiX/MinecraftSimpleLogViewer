<?php
/**
 * Created by IntelliJ IDEA.
 * User: KAZUMiX
 * Date: 15/05/24
 * Time: 13:35
 * To change this template use File | Settings | File Templates.
 */

require_once 'config.php';

class MinecraftLogReader
{
    /**
     * @var int
     */
    private $maxLogBytes;

    /**
     * @var array
     */
    private $logPathnames = array();

    /**
     * @var int
     */
    private $logsTotalBytes = 0;

    /**
     * @var int
     */
    private $seekOffset = 0;

    private $cssClassDic = array (
        'joined the game' => 'joined',
        'left the game' => 'left',
        ' has just earned the achievement ' => 'achievement'
    );

    /**
     * @param string $logDir
     * @param int $maxLogBytes
     */
    function __construct($logDir, $maxLogBytes)
    {
        $this->maxLogBytes = $maxLogBytes;
        $logPathnames = $this->getLogPathnames($logDir);
        foreach ($logPathnames as $pathname) {
            if ($this->addTargetLogFile($pathname)) {
                continue;
            } else {
                break;
            }
        }
    }

    /**
     * @param string $logDir
     * @return array
     */
    private function getLogPathnames($logDir)
    {
        $resultLogs = array();
        $dir = new DirectoryIterator($logDir);
        $latestLog = '';
        foreach ($dir as $fileinfo) {
            if ($fileinfo->isFile()) {
                if ($fileinfo->getExtension() === 'gz') {
                    array_unshift($resultLogs, $fileinfo->getPathname());
                } else if ($fileinfo->getFilename() === 'latest.log') {
                    $latestLog = $fileinfo->getPathname();
                }
            }
        }
        if (!empty($latestLog)) {
            array_unshift($resultLogs, $latestLog);
        }
        return $resultLogs;
    }

    /**
     * @param string $path
     * @throws Exception
     * @return bool
     */
    private function addTargetLogFile($path)
    {
        if (!file_exists($path)) {
            throw new Exception("file not found: {$path}");
            return false;
        }
        array_unshift($this->logPathnames, $path);
        $ext = substr(strrchr($path, '.'), 1);
        $logBytes = 0;
        if ($ext === 'gz') {
            // According to gzip specification, uncompressed size is stored in last four bytes of the file, little endian.
            $fp = fopen($path, "rb");
            fseek($fp, -4, SEEK_END);
            $buf = fread($fp, 4);
            $unpacked = unpack("V", $buf);
            $logBytes = end($unpacked);
            fclose($fp);
        } else {
            $logBytes = filesize($path);
        }
        $this->logsTotalBytes += $logBytes;

        if ($this->logsTotalBytes >= $this->maxLogBytes) {
            $this->seekOffset = $this->logsTotalBytes - $this->maxLogBytes;
            return false;
        } else {
            return true;
        }
    }

    public function displayLog ()
    {
        foreach ($this->logPathnames as $pathname) {
            $this->echoLog($pathname);
        }
    }

    /**
     * @param string $path
     */
    private function echoLog($path)
    {
        $filename = basename($path);
        echo "<li class='filename'>{$filename}</li>";

        $fp = gzopen($path, 'rb');

        if ($this->seekOffset > 0) {
            gzseek($fp, $this->seekOffset);
            fgets($fp); // dump a line
            $this->seekOffset = 0;
        }

        $chatNameLeft = ': &lt;';
        $chatNameLeftLength = mb_strlen($chatNameLeft);
        $chatNameRight = '&gt;';
        $chatNameRightLength = mb_strlen($chatNameRight);

        $targetInfoTag = '[Server thread/INFO]';
        $targetInfoTagLength = strlen($targetInfoTag);

        while (!gzeof($fp)) {
            $line = fgets($fp);
            $targetTagPos = mb_strpos($line, $targetInfoTag);
            if ($targetTagPos === false) {
                continue;
            }
            // cancel some info
            if (mb_strpos($line, 'logged in with entity id') !== false || mb_strpos($line, 'lost connection: TextComponent') !== false) {
                continue;
            }

            // split
            $date = mb_substr($line, 0 , $targetTagPos);
            $body = mb_substr($line, mb_strlen($date) + $targetInfoTagLength);
            $body = mb_convert_encoding($body, 'UTF-8', 'sjis-win');

            $className = '';
            foreach ($this->cssClassDic as $key => $value) {
                if (mb_strpos($body, $key) !== false) {
                    $className = $value;
                    break;
                }
            }

            $body = htmlentities($body, ENT_QUOTES, mb_internal_encoding());

            // emphasis chat
            if (mb_strpos($body, $chatNameLeft) === 0) {
                $chatNameEndPos = mb_strpos($body, $chatNameRight);
                if ($chatNameEndPos !== false) {
                    $playerName = mb_substr($body, $chatNameLeftLength, $chatNameEndPos - $chatNameLeftLength);
                    $chatText = mb_substr($body, $chatNameEndPos + $chatNameRightLength);
                    $body = "{$chatNameLeft}<strong>{$playerName}</strong>{$chatNameRight}{$chatText}";
                }
            }

            if ($className) {
                $line = "<li class=\"{$className}\">{$date}{$body}</li>";
            } else {
                $line = "<li>{$date}{$body}</li>";
            }

            echo $line;
        }

        gzclose($fp);
    }
}
