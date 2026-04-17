<?php

namespace App\Helpers\Legacy;

/**
 * Port of CakePHP `PermissionNestedTreeHelper`.
 * Generates hierarchical tree data for the FancyTree plugin used in permission management.
 */
class PermissionNestedTree
{
    /**
     * Generates the root level of the permission tree (Controllers).
     */
    public static function getMenuNameTree(array $categories, ?array $selectedMenu): array
    {
        $menu = [];
        foreach ($categories as $catKey => $actions) {
            // Check if this controller has any selected actions
            $isSelected = is_array($selectedMenu) && in_array($catKey, array_keys($selectedMenu));
            
            $result = [
                "icon" => false,
                "selected" => $isSelected,
                "key" => $catKey,
                "title" => "<strong>{$catKey}</strong>",
                "module" => $catKey,
                "expanded" => true,
                "children" => self::getMenuChild($catKey, $actions, $selectedMenu)
            ];
            
            $menu[] = $result;
        }
        return $menu;
    }

    /**
     * Generates the child level of the permission tree (Actions/Methods).
     */
    private static function getMenuChild(string $catKey, array $actions, ?array $selectedMenu): array
    {
        $children = [];
        foreach ($actions as $action) {
            $isSelected = isset($selectedMenu[$catKey]) && in_array($action, (array)$selectedMenu[$catKey]);
            
            $children[] = [
                "icon" => false,
                "selected" => $isSelected,
                "key" => $action,
                "title" => "{$action}",
                "module" => $action,
                "parent_module" => $catKey,
                "expanded" => true,
                "children" => []
            ];
        }
        return $children;
    }
}
