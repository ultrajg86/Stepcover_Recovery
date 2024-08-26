<?php

// autoload_static.php @generated by Composer

namespace Composer\Autoload;

class ComposerStaticInit310c5c5aae9fc6a5433547698ca7837a
{
    public static $classMap = array (
        'Composer\\InstalledVersions' => __DIR__ . '/..' . '/composer/InstalledVersions.php',
        'WP_Async_Request' => __DIR__ . '/..' . '/deliciousbrains/wp-background-processing/classes/wp-async-request.php',
        'WP_Background_Process' => __DIR__ . '/..' . '/deliciousbrains/wp-background-processing/classes/wp-background-process.php',
    );

    public static function getInitializer(ClassLoader $loader)
    {
        return \Closure::bind(function () use ($loader) {
            $loader->classMap = ComposerStaticInit310c5c5aae9fc6a5433547698ca7837a::$classMap;

        }, null, ClassLoader::class);
    }
}
