<?php
declare(strict_types = 1);
/**
 * Created by PhpStorm.
 * User: XYQ
 * Date: 2020-03-24
 * Time: 10:57
 */

namespace xyqWeb\log\drivers;


class File extends LogStrategy
{
    /**
     * @var string 日志主路径
     */
    protected $path;

    /**
     * File constructor.
     * @param array $config
     * @throws LogException
     */
    public function __construct(array $config)
    {
        $realPath = $this->getFinalPath($config);
        $errorCode = $this->createDir($realPath);
        if (1 == $errorCode) {
            throw new LogException("目录没有创建权限");
        } elseif (2 == $errorCode) {
            throw new LogException("目录创建失败，请检查!");
        }
        $this->path = $realPath . '/';
    }

    /**
     * 创建文件目录
     *
     * @author xyq
     * @param string $path
     * @return int
     */
    private function createDir(string $path) : int
    {
        if (is_dir($path)) {
            return 0;
        }
        for ($i = 0; $i < 3; $i++) {
            try {
                set_error_handler(function (int $number, string $message) {
                    throw new \Exception($message, $number);
                });
                mkdir($path, 0777, true);
            } catch (\Exception $e) {
                $message = $e->getMessage();
                if (strpos($message, 'Permission denied')) {
                    return 1;
                } elseif (strpos($message, 'File exists')) {
                    return 0;
                }
            }
        }
        if (is_dir($path)) {
            return 0;
        } else {
            return 2;
        }
    }

    /**
     * 写入文本日志
     *
     * @author xyq
     * @param string $logName
     * @param $logContent
     * @return bool
     * @throws LogException
     */
    public function write(string $logName, $logContent) : bool
    {
        $logContent = is_array($logContent) ? json_encode($logContent, JSON_UNESCAPED_UNICODE) : $logContent;
        $newNameArray = $this->resetLogName($logName);
        if (!empty($newNameArray['path'])) {
            $errorCode = $this->createDir($this->path . $newNameArray['path']);
            if (1 == $errorCode) {
                throw new LogException("目录没有创建权限");
            } elseif (2 == $errorCode) {
                throw new LogException("目录创建失败，请检查!");
            }
            $filePath = $this->path . $newNameArray['path'] . '/' . $newNameArray['logName'];
        } else {
            $filePath = $this->path . $newNameArray['logName'];
        }
        $status = error_log($logContent, 3, $filePath);
        if (true == $status) {
            return true;
        } else {
            return false;
        }
    }
}