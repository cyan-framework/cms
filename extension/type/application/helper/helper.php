<?php
namespace Cyan\Framework;

abstract class ExtensionTypeApplicationHelper
{
    /**
     * @var array
     */
    static $extension_manifests = [];

    /**
     *
     */
    public static function initialize()
    {
        $Cyan = \Cyan::initialize();

        if (empty(self::$extension_manifests)) {
            foreach (glob($Cyan->Finder->getResource('app') . DIRECTORY_SEPARATOR . '*/extension.xml') as $extension_manifest) {
                self::$extension_manifests[basename(dirname($extension_manifest))] = $extension_manifest;
            }

            ksort(self::$extension_manifests);
        }
    }

    public static function getAll()
    {
        return array_keys(self::$extension_manifests);
    }

    /**
     * @param $extension
     * @return XMLElement
     */
    public static function getManifest($extension)
    {
        if (!isset(self::$extension_manifests[$extension])) {
            throw new ExtensionException(sprintf('Extension %s manifest not found!',$extension));
        }
        return simplexml_load_file(self::$extension_manifests[$extension],'\Cyan\Framework\XmlElement');
    }

    public static function getPermissions($extension)
    {
        $manifest = self::getManifest($extension);

        $permissions = [];

        foreach ($manifest->xpath('permissions/permission') as $permission_node) {
            $permissions[$permission_node->getAttribute('name')] = $permission_node->getAttribute('title');
        }

        return $permissions;
    }
}