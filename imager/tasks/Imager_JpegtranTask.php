<?php
namespace Craft;

class Imager_JpegtranTask extends BaseTask
{
    private $_paths;

    public function getDescription()
    {
        return Craft::t('Optimizing images with jpegtran');
    }

    /**
     * @inheritDoc ITask::getTotalSteps()
     *
     * @return int
     */
    public function getTotalSteps()
    {
        // Get the actual paths out of the settings
        $this->_paths = $this->getSettings()->paths;

        // Count our final chunked array
        return is_array($this->_paths) ? count($this->_paths) : 1;
    }

    /**
     * @inheritDoc ITask::runStep()
     *
     * @param int $step
     *
     * @return bool
     */
    public function runStep($step)
    {
        if (is_array($this->_paths)) {
            $path = $this->_paths[$step];
        } else {
            $path = $this->_paths;
        }

        // Run TinyPNG 
        craft()->imager->runJpegtran($path);
        
        // if AWS is enabled, upload file
        if (craft()->imager->getSetting('awsEnabled')) {
            try {
                craft()->imager->uploadToAWS($path);
            } catch (\Exception $e) {
                ImagerPlugin::log("Upload to AWS failed for $path in Imager_JpegtranTask", LogLevel::Error);
            }
        }

        return true;
    }

    // Protected Methods
    // =========================================================================

    /**
     * @inheritDoc BaseSavableComponentType::defineSettings()
     *
     * @return array
     */
    protected function defineSettings()
    {
        return array(
          'paths' => AttributeType::Mixed
        );
    }

}