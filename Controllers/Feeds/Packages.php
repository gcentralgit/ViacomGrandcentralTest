<?php
/**
 *  \addToGroup Pub\Controllers\Feeds
 */
namespace Pub\Controllers\Feeds;

/**
 * Packages feed
 * @author $Author$
 * @class Logs
 */
class Packages extends Generic
{
    const PACKAGE_ROOT = 'https://subversion.1515.mtvi.com/phpsites/entertainment/platforms/Viacom';

    /**
     * @var array
     */
    protected $packageFiles = [];

    /**
     * @see Pub\Controllers\Feeds.Generic::setDetermineParametersPipeline()
     */
    protected function setDetermineParametersPipeline()
    {
        parent::setDetermineParametersPipeline();

        $this->determineParametersPipeline
            ->determineProjectId()
            ->determineEnvironmentId();
    }

    /**
     * get the projectId parameter (required)
     */
    protected function determineProjectId()
    {
        $this->projectId = $this->getPostParameterValue('projectId');

        if (empty($this->projectId)) {
            $this->requestFailed('projectId not found');
        }
    }

    /**
     * get the environmentId parameter (required);
     */
    protected function determineEnvironmentId()
    {
        $this->environmentId = $this->getPostParameterValue('environmentId');
        if (!($this->environmentId > -1)) {
            $this->requestFailed('environmentId was not found');
        }
    }

    /**
     * Set fetch pipeline
     */
    protected function setFetchInformationPipeline()
    {
        parent::setFetchInformationPipeline();

        $this->fetchInformationPipeline
            ->fetchAvailablePackages();
    }

    /**
     * Fetch available packages
     */
    protected function fetchAvailablePackages()
    {
        try {
            $packages = $this->getCrabappleSystem()->components->configuration->packagesSource;
            if (!empty($packages)) {
                foreach ($packages as $package) {
                    $this->packageFiles["{$package->name}"] = $package->source;
                }
            } else {
                throw new \Viacom\Crabapple\Exceptions\Exception('Failed to retrieve available packages!');
            }
        } catch (\Viacom\Crabapple\Exceptions\Exception $e) {
            $this->logException("Failed to retrieve available packages!", $e);
            $this->requestFailed($e->getMessage());
            return;
        }
    }

    /**
     * Set process information pipeline
     */
    protected function setProcessInformationPipeline()
    {
        parent::setProcessInformationPipeline();

        $this->processInformationPipeline
            ->setPackagesInTemplate();
    }

    /**
     * Set packages in template
     */
    protected function setPackagesInTemplate()
    {
        $this->output->packages = [];
        foreach ($this->packageFiles as $name => $file) {
            $this->output->packages[] = ['name' => $name, 'path' => $file];
        }

        $this->output->success = true;
    }

}