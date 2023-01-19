<?php

namespace Apsis\One\Model\Service;

use Apsis\One\Logger\Logger;
use Magento\Framework\Module\ModuleListInterface;
use Throwable;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Module\ResourceInterface;

class Log
{
    const MODULE_NAME = 'Apsis_One';

    /**
     * @var ScopeConfigInterface
     */
    protected ScopeConfigInterface $scopeConfig;

    /**
     * @var Logger
     */
    private Logger $logger;

    /**
     * @var ResourceInterface
     */
    private ResourceInterface $moduleResource;

    /**
     * @var ModuleListInterface
     */
    private ModuleListInterface $moduleList;

    /**
     * Log constructor.
     *
     * @param Logger $logger
     * @param ScopeConfigInterface $scopeConfig
     * @param ResourceInterface $moduleResource
     * @param ModuleListInterface $moduleList
     */
    public function __construct(
        Logger $logger,
        ScopeConfigInterface $scopeConfig,
        ResourceInterface $moduleResource,
        ModuleListInterface $moduleList
    ) {
        $this->moduleList = $moduleList;
        $this->logger = $logger;
        $this->scopeConfig = $scopeConfig;
        $this->moduleResource = $moduleResource;
    }

    /**
     * @param string $message
     * @param array $extra
     *
     * @return void
     */
    public function log(string $message, array $extra = []): void
    {
        $this->logger->info($this->addModuleVersionToMessage("Message : $message"), $extra);
    }

    /**
     * @param string $message
     * @param array $response
     * @param array $extra
     *
     * @return void
     */
    public function debug(string $message, array $response = [], array $extra = []): void
    {
        $this->logger->debug($this->getStringForLog(['Message' => $message, 'Information' => $response]), $extra);
    }

    /**
     * @param string $classMethodName
     * @param Throwable $e
     * @param array $extra
     *
     * @return void
     */
    public function logError(string $classMethodName, Throwable $e, array $extra = []): void
    {
        $info = [
            'Method' => $classMethodName,
            'Exception|Error' => $e->getMessage(),
            'Trace' => str_replace(PHP_EOL, PHP_EOL . "        ", PHP_EOL . $e->getTraceAsString())
        ];
        $this->error($this->getStringForLog($info), $extra);
    }

    /**
     * @param string $message
     * @param array $extra
     *
     * @return void
     */
    public function error(string $message, array $extra = []): void
    {
        $this->logger->error($this->addModuleVersionToMessage($message), $extra);
    }


    /**
     * @param array $info
     *
     * @return string
     */
    private function getStringForLog(array $info): string
    {
        return stripcslashes($this->addModuleVersionToMessage(json_encode($info, JSON_PRETTY_PRINT)));
    }

    /**
     * @param string $message
     *
     * @return string
     */
    private function addModuleVersionToMessage(string $message): string
    {
        return '(Module v' . $this->getCurrentVersion() . ') ' . $message;
    }

    /**
     * @param mixed $data
     *
     * @return string|bool
     */
    public function serialize(mixed $data): bool|string
    {
        try {
            return json_encode($data);
        } catch (Throwable $e) {
            $this->logError(__METHOD__, $e);
            return '[]';
        }
    }

    /**
     * @param string $string
     *
     * @return mixed
     */
    public function unserialize(string $string): mixed
    {
        try {
            return json_decode($string);
        } catch (Throwable $e) {
            $this->logError(__METHOD__, $e);
            return [];
        }
    }

    /**
     * Invalidate cache by type
     * Clean scopeCodeResolver
     *
     * @return void
     */
    public function cleanCache(): void
    {
        try {
            $this->scopeConfig->clean();
        } catch (Throwable $e) {
            $this->logError(__METHOD__, $e);
        }
    }

    /**
     * @return string
     */
    public function getCurrentVersion(): string
    {
        try {
            $version = (string) $this->moduleResource->getDbVersion('Apsis_One');
            if (strlen($version)) {
                return $version;
            }

            $moduleInfo = $this->moduleList->getOne(self::MODULE_NAME);
            if (is_array($moduleInfo) && ! empty($moduleInfo['setup_version'])) {
                return (string) $moduleInfo['setup_version'];
            }
        } catch (Throwable $e) {
            $this->logError(__METHOD__, $e);
        }

        return 'unknown';
    }
}
