<?php

namespace aelvan\imager\optimizers;

use Craft;

use aelvan\imager\models\ConfigModel;
use aelvan\imager\services\ImagerService;
use aelvan\imager\traits\RunShellCommandTrait;

class PngquantOptimizer implements ImagerOptimizeInterface
{
    use RunShellCommandTrait;

    public static function optimize(string $file, array $settings)
    {
        /** @var ConfigModel $settings */
        $config = ImagerService::getConfig();
        
        if ($config->skipExecutableExistCheck || file_exists($settings['path'])) {
            $cmd = $settings['path'];
            $cmd .= ' ';
            $cmd .= $settings['optionString'];
            $cmd .= ' ';
            $cmd .= '-f -o ';
            $cmd .= '"'.$file.'"';
            $cmd .= ' ';
            $cmd .= '"'.$file.'"';
            
            $result = self::runShellCommand($cmd);
            Craft::info('Command "'.$cmd.'" returned "' . $result . '"');
        } else {
            Craft::error('Optimizer ' . self::class . ' could not be found in path ' . $settings['path'], __METHOD__);
        }
    }
}