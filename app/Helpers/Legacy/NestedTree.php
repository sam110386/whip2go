<?php

namespace App\Helpers\Legacy;

class NestedTree
{
    /**
     * Generates a nestable HTML list string for rendering modular UI hierarchies.
     */
    public static function getMenuTree($parentId, $categories, &$mainList = [])
    {
        $result = '<ol class="dd-list">';
        $hasItems = false;
        foreach ($categories as $cat) {
            if ((int)$cat->parent_id === (int)$parentId) {
                $hasItems = true;
                $result .= self::getCategory($cat, $categories, $mainList);
            }
        }
        $result .= '</ol>';
        return $hasItems ? $result : '';
    }

    private static function getCategory($cat, $categories, &$mainList)
    {
        $icon = htmlspecialchars($cat->icon ?? '');
        $module = htmlspecialchars($cat->module ?? '');
        $url = htmlspecialchars($cat->module_url ?? '');
        $catUrl = url($url);

        $result = '<li class="dd-item" data-id="' . $cat->id . '">';
        $result .= '<div class="dd-handle">';
        $result .= "<i class='{$icon}'></i>&nbsp;<strong>{$module}</strong>";
        $result .= "&nbsp;&nbsp;&nbsp;<a href='{$catUrl}' class=\"dd-nodrag\">{$url}</a>";
        $result .= "<span class=\"pull-right dd-nodrag\">
            <a href=\"javascript:void(0);\" data-id=\"{$cat->id}\" class=\"tree_branch_edit\"><i class=\"fa fa-edit\"></i></a>
            <a href=\"javascript:void(0);\" data-id=\"{$cat->id}\" class=\"tree_branch_delete\"><i class=\"fa fa-trash\"></i></a>
        </span>";
        $result .= '</div>';
        
        $childrenStr = self::getMenuTree($cat->id, $categories, $mainList);
        if (!empty($childrenStr)) {
            $result .= $childrenStr;
        }
        
        $result .= '</li>';
        return $result;
    }

    /**
     * Generates the array payload structure required by the FancyTree JS plugin.
     */
    public static function getMenuNameTree($parentId, $categories, &$mainList = [], $selectedMenu = [])
    {
        $result = [];
        foreach ($categories as $cat) {
            if ((int)$cat->parent_id === (int)$parentId) {
                $result[] = self::getMenuChild($cat, $categories, $mainList, $selectedMenu);
            }
        }
        return $result;
    }

    private static function getMenuChild($cat, $categories, &$mainList, $selectedMenu)
    {
        $icon = htmlspecialchars($cat->icon ?? 'fa fa-bars');
        $module = htmlspecialchars($cat->module ?? '');
        $moduleUrl = htmlspecialchars($cat->module_url ?? '');
        $catUrl = url($moduleUrl);

        $result = [
            "icon" => false,
            "selected" => in_array((string)$cat->id, array_map('strval', $selectedMenu), true),
            "key" => (string)$cat->id,
            "title" => "<i class='{$icon}'></i>&nbsp;<strong>{$module}</strong>&nbsp;&nbsp;&nbsp;<a href='{$catUrl}' class='dd-nodrag'>{$moduleUrl}</a>"
        ];
        
        $children = self::getMenuNameTree($cat->id, $categories, $mainList, $selectedMenu);
        if (!empty($children)) {
            $result['expanded'] = true;
            $result['children'] = $children;
        }
        
        return $result;
    }
}
