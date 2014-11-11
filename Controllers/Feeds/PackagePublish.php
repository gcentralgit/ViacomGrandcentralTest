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
class PackagePublish extends Packages
{
    protected $filePath;

    /**
     * Packages for publish
     * @var array
     */
    protected $packagesToPublish;

    /**
     * Message for publish
     */
    const PUBLISH_MESSAGE = "PUBLISHING";

    /**
     * Delete message
     */
    const DELETE_MESSAGE = "DELETING";

    /**
     * Set determines
     */
    protected function setDetermineParametersPipeline()
    {
        parent::setDetermineParametersPipeline();

        $this->determineParametersPipeline
            ->determineFilePath()
            ->determinePackagesForPublish();
    }

    /**
     * get the filePath parameter (required)
     */
    protected function determineFilePath()
    {
        $this->filePath = $this->getPostParameterValue('filePath');
        if(empty($this->filePath)) $this->requestFailed('filePath not found');
    }

    /**
     * Determine paths which should be published
     */
    protected function determinePackagesForPublish()
    {
        $this->packagesToPublish = $this->getPostParameterValue('packages');
        if(empty($this->packagesToPublish)) {
            $this->requestFailed('Please select at least one path to publish');
            return;
        } else {
            $this->packagesToPublish = json_decode($this->packagesToPublish);
        }
    }

    /**
     * Fetch pipes
     */
    protected function setFetchInformationPipeline()
    {
        parent::setFetchInformationPipeline();

        $this->fetchInformationPipeline
            ->performPackageMergeOnProject();
    }

    /**
     * Perform package merge on project
     */
    protected function performPackageMergeOnProject()
    {
        $this->output->debug = [];
        /*
        $this->filePath = substr($this->filePath, 0, -6); //get rid of /trunk for test reasons
        if($this->projectId != "testing_deploy") {
            $this->requestFailed("this feature doesn't work yet; Try fake project first");
            return;
        }
        */

        foreach ($this->packagesToPublish as $package)
        {
            if(!empty($this->packageFiles["{$package->name}"]) && $this->packageFiles["{$package->name}"] == $package->path) {
                $localPackageDir = $this->filePath . "/packages/" . $package->name;
                $sourcePackageDir =  $package->path;
                $this->sourceControlClient->delete('/', $localPackageDir, static::DELETE_MESSAGE . ' ' . $package->name);
                $this->sourceControlClient->copy('/', $sourcePackageDir, $localPackageDir, static::PUBLISH_MESSAGE . ' ' . $package->name);

                $this->output->debug[] = $package->name;
            }
        }
    }

    /**
     * Set packages in template
     */
    protected function setPackagesInTemplate()
    {
        $this->output->message = "You just published:<br />" . implode("<br />", $this->output->debug);

        $this->output->success = true;
    }

}