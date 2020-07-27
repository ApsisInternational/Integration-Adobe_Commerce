<?php

namespace Apsis\One\Model\Service;

use Exception;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\Exception\ValidatorException;
use Magento\Framework\Filesystem\Directory\WriteFactory;
use Magento\Framework\Filesystem\Directory\Write;

class File
{
    /**
     * @var DirectoryList
     */
    private $directoryList;

    /**
     * @var Write
     */
    private $write;

    /**
     * File constructor.
     *
     * @param DirectoryList $directoryList
     * @param WriteFactory $write
     *
     * @throws FileSystemException
     */
    public function __construct(
        DirectoryList $directoryList,
        WriteFactory $write
    ) {
        $this->directoryList = $directoryList;
        $this->write = $write->create($this->getOutputFolder());
    }

    /**
     * @return string
     */
    private function getOutputFolder()
    {
        return $this->directoryList->getPath('var') . DIRECTORY_SEPARATOR . 'apsis';
    }

    /**
     * @param string $filename
     *
     * @return string
     *
     * @throws FileSystemException
     */
    public function getFilePath(string $filename)
    {
        return $this->getOutputFolder() . DIRECTORY_SEPARATOR . $filename;
    }

    /**
     * @param string $filePath
     *
     * @return bool
     *
     * @throws FileSystemException
     */
    public function deleteFile(string $filePath)
    {
        return $this->write->delete($filePath);
    }

    /**
     * @param string $file
     * @param array $data
     *
     * @throws FileSystemException
     */
    public function outputCSV($file, $data)
    {
        $resource = $this->write->openFile($file, 'a');
        $resource->lock();
        $resource->writeCsv($data);
        $resource->unlock();
        $resource->close();
    }

    /**
     * @param string $filename
     *
     * @return string
     */
    public function getLogFileContent(string $filename = 'apsis_one')
    {
        switch ($filename) {
            case "apsis_one":
                $filename = 'apsis_one.log';
                break;
            case "system":
                $filename = 'system.log';
                break;
            case "exception":
                $filename = 'exception.log';
                break;
            case "debug":
                $filename = 'debug.log';
                break;
            default:
                return "Log file is not valid. Log file name is " . $filename;
        }
        try {
            $pathLogfile = $this->directoryList->getPath('log') . DIRECTORY_SEPARATOR . $filename;
            if (! $this->write->getDriver()->isExists($pathLogfile)) {
                return "Log file does not exist at this moment. File path is " . $pathLogfile;
            }

            $lengthBefore = 500000;
            $contents = '';
            $handle = $this->write->getDriver()->fileOpen($pathLogfile, 'r');
            fseek($handle, -$lengthBefore, SEEK_END);
            if (! $handle) {
                return "Log file is not readable or does not exist at this moment. File path is "
                    . $pathLogfile;
            }

            if (filesize($pathLogfile) > 0) {
                $contents = $this->write->getDriver()->fileRead($handle, filesize($pathLogfile));
                if ($contents === false) {
                    return "Log file is not readable or does not exist at this moment. File path is " . $pathLogfile;
                }
                $this->write->getDriver()->fileClose($handle);
            }
            return $contents;
        } catch (Exception $e) {
            return $e->getMessage();
        }
    }
}