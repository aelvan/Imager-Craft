<?php

namespace aelvan\imager\models;

use craft\base\Model;

use aelvan\imager\services\ImagerService;

class ConfigModel extends Settings
{
    /**
     * @var string
     */
    private $configOverrideString = '';

    /**
     * TransformSettings constructor.
     *
     * @param Settings|Model $settings
     * @param array          $overrides
     * @param array          $config
     */
    public function __construct($settings, $overrides = null, $config = [])
    {
        parent::__construct($config);


        // Reset model to get overrides from config file 
        foreach ($settings as $key => $value) {
            $this->$key = $value;
        }

        // Apply transform overrides
        $excludedConfigOverrideProperties = ['fillTransforms', 'fillInterval', 'fillAttribute', 'filenamePattern', 'transformer'];

        if ($overrides !== null) {
            foreach ($overrides as $key => $value) {
                $this->$key = $value;
                if (!\in_array($key, $excludedConfigOverrideProperties, true)) {
                    $this->addToOverrideFilestring($key, $value);
                }
            }
        }

        // Prep position value
        if (isset(ImagerService::$craftPositionTranslate[(string)$this->position])) {
            $this->position = ImagerService::$craftPositionTranslate[(string)$this->position];
        }

        $this->position = str_replace('%', '', $this->position);

        // Replace aliases
        $aliasables = ['imagerSystemPath', 'imagerUrl'];

        foreach ($aliasables as $aliasable) {
            $this->{$aliasable} = \Yii::getAlias($settings->{$aliasable});
        }
    }

    /**
     * Get setting by key. If there is an override in transform, that is returned instead of the value in the model.
     *
     * @param string     $key
     * @param array|null $transform
     *
     * @return mixed
     */
    public function getSetting($key, $transform = null)
    {
        if (isset($transform[$key])) {
            return $transform[$key];
        }

        return $this[$key];
    }

    /**
     * Returns config override string for this config model
     *
     * @return string
     */
    public function getConfigOverrideString(): string
    {
        return $this->configOverrideString;
    }

    /**
     * Creates additional file string based on config overrides that is appended to filename
     *
     * @param string $key
     * @param mixed  $value
     */
    private function addToOverrideFilestring($key, $value)
    {
        $r = (ImagerService::$transformKeyTranslate[$key] ?? $key).(\is_array($value) ? md5(implode('-', $value)) : $value);
        $this->configOverrideString .= '_'.str_replace('%', '', str_replace([' ', '.'], '-', $r));
    }

}
