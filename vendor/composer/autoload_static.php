<?php

// autoload_static.php @generated by Composer

namespace Composer\Autoload;

class ComposerStaticInitad1fdc10855fb5307d25e1e7baba95db
{
    public static $prefixLengthsPsr4 = array (
        't' => 
        array (
            'tuyapiphp\\' => 10,
        ),
    );

    public static $prefixDirsPsr4 = array (
        'tuyapiphp\\' => 
        array (
            0 => __DIR__ . '/..' . '/tuyapiphp/tuyapiphp/src',
        ),
    );

    public static $classMap = array (
        'Composer\\InstalledVersions' => __DIR__ . '/..' . '/composer/InstalledVersions.php',
    );

    public static function getInitializer(ClassLoader $loader)
    {
        return \Closure::bind(function () use ($loader) {
            $loader->prefixLengthsPsr4 = ComposerStaticInitad1fdc10855fb5307d25e1e7baba95db::$prefixLengthsPsr4;
            $loader->prefixDirsPsr4 = ComposerStaticInitad1fdc10855fb5307d25e1e7baba95db::$prefixDirsPsr4;
            $loader->classMap = ComposerStaticInitad1fdc10855fb5307d25e1e7baba95db::$classMap;

        }, null, ClassLoader::class);
    }
}
