<?php
namespace Cyan\Framework;

/**
 * Class ExtensionTypeComponentHelper
 * @package Cyan\Framework
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
        return simplexml_load_file(self::$extension_manifests[$extension],'\Cyan\Framework\XmlElement');
    }

    public static function getMenu($extension)
    {
        $menus = [];
        $Cyan = \Cyan::initialize();
        $App = $Cyan->getContainer('application');

        $manifest = self::getManifest($extension);

        /** @var XmlElement $menuNode */
        $menu_path = sprintf('scope[@name="%s"]/menu',strtolower($App->getName()));
        foreach ($manifest->xpath($menu_path) as $menu_node) {
            $menu = self::getMenuManifestNode($menu_node);
            if (isset($menu->items) && empty($menu->items)) continue;
            $menus[] = $menu;
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
        $User = $App->getContainer('user');

        $acl_menu_permission = $menu_node->getAttribute('acl_check');

        if ($acl_menu_permission) {
            $acl_check = explode(',',$acl_menu_permission);
            $continue = false;
            foreach ($acl_check as $acl_permission) {
                if (!$continue && $User->can($acl_permission)) {
                    $continue = true;
                    break;
                }
            }

            if (!$continue) return [];
        }

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
                $submenu_items = self::getMenuManifestNode($submenu_node);
                if (!empty($submenu_items)) {
                    $menu->items[] = $submenu_items;
                }
            }
        }

        return $menu;
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

ExtensionTypeComponentHelper::initialize();