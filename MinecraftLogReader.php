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
        array_unshift($resultLogs, $latestLog);
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
        echo "<hr /><li>{$filename}</li>";

        $fp = gzopen($path, 'rb');

        if ($this->seekOffset > 0) {
            gzseek($fp, $this->seekOffset);
            fgets($fp); // dump a line
            $this->seekOffset = 0;
        }

        while (!gzeof($fp)) {
            $line = fgets($fp);
            $line = mb_convert_encoding($line, 'UTF-8', 'sjis-win');
            if (mb_strpos($line, '[Server thread/INFO]') === false) {
                continue;
            }
            if (mb_strpos($line, 'logged in with entity id') !== false || mb_strpos($line, 'lost connection: TextComponent') !== false) {
                continue;
            }

            $className = '';
            if (mb_strpos($line, 'joined the game') !== false) {
                $className = 'joined';
            } else if (mb_strpos($line, 'left the game') !== false) {
                @$className = 'left';
            }
            $line = htmlentities($line, ENT_QUOTES, mb_internal_encoding());
            echo "<li class=\"{$className}\">{$line}</li>";
        }

        gzclose($fp);
    }
}
