<?php

// autoload_static.php @generated by Composer

namespace Composer\Autoload;

class ComposerStaticInitedde8a49a0469b24549076822f5c7326
{
    public static $prefixLengthsPsr4 = array (
        'C' => 
        array (
            'Composer\\Installers\\' => 20,
        ),
    );

    public static $prefixDirsPsr4 = array (
        'Composer\\Installers\\' => 
        array (
            0 => __DIR__ . '/..' . '/composer/installers/src/Composer/Installers',
        ),
    );

    public static $prefixesPsr0 = array (
        'R' => 
        array (
            'Racecore\\GATracking' => 
            array (
                0 => __DIR__ . '/..' . '/ins0/google-measurement-php-client/src',
            ),
        ),
    );

    public static function getInitializer(ClassLoader $loader)
    {
        return \Closure::bind(function () use ($loader) {
            $loader->prefixLengthsPsr4 = ComposerStaticInitedde8a49a0469b24549076822f5c7326::$prefixLengthsPsr4;
            $loader->prefixDirsPsr4 = ComposerStaticInitedde8a49a0469b24549076822f5c7326::$prefixDirsPsr4;
            $loader->prefixesPsr0 = ComposerStaticInitedde8a49a0469b24549076822f5c7326::$prefixesPsr0;

        }, null, ClassLoader::class);
    }
}
