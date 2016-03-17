<?php
namespace Cyan\Library;

/**
 * Class ExtensionTypeComponentHelper
 * @package Cyan\Library
 */
abstract class ExtensionTypeComponentHelper
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
            foreach (glob($Cyan->Finder->getResource('components') . DIRECTORY_SEPARATOR . '*/extension.xml') as $component_manifest) {
                self::$extension_manifests[basename(dirname($component_manifest))] = $component_manifest;
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
        return simplexml_load_file(self::$extension_manifests[$extension],'\Cyan\Library\XmlElement');
    }

    public static function getMenu($extension)
    {
        $menus = [];
        $Cyan = \Cyan::initialize();
        $App = $Cyan->getContainer('application');

        $manifest = self::getManifest($extension);
        /** @var XmlElement $menuNode */
        $menu_path = strtolower($App->getName()).'/menu';
        $submenu_path = strtolower($App->getName()).'/submenu';
        foreach ($manifest->xpath($menu_path) as $menu_node) {
            $menus[] = self::getMenuManifestNode($menu_node);
        }

        return $menus;
    }

    /**
     * @param XmlElement $menu_node
     * @return \stdClass
     */
    private static function getMenuManifestNode(XmlElement $menu_node)
    {
        $Cyan = \Cyan::initialize();
        $App = $Cyan->getContainer('application');

        $route_name = $menu_node->getAttribute('route_name');
        $route_params = json_decode($menu_node->getAttribute('route_params','{}'), true);
        if (!is_array($route_params)) {
            $route_params = [];
        }

        $menu = new \stdClass();
        $menu->icon = $menu_node->getAttribute('icon','fa fa-circle-o');
        $menu->title = $menu_node->getAttribute('title');
        $menu->link = !empty($route_name) ? $App->Router->generate($route_name, $route_params) : '#' ;

        if (isset($menu_node->submenu) && $menu_node->submenu->menu->count()) {
            $menu->items = [];
            foreach ($menu_node->submenu->menu as $submenu_node) {
                $menu->items[] = self::getMenuManifestNode($submenu_node);
            }
        }

        return $menu;
    }
}

ExtensionTypeComponentHelper::initialize();