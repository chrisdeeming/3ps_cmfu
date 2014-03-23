<?php
/*
* Custom Username Markup For User v1.0.0 written by tyteen4a03@3.studIo.
* This software is licensed under the BSD 2-Clause modified License.
* See the LICENSE file within the package for details.
*/

class ThreePointStudio_CustomMarkupForUser_TemplateHelpers_11 extends ThreePointStudio_CustomMarkupForUser_TemplateHelpers_Base {
    public static function helperUserTitle($user, $allowCustomTitle = true) {
        $result = parent::helperUserTitle($user, $allowCustomTitle);
        $html = ThreePointStudio_CustomMarkupForUser_Helpers::assembleCustomMarkup($user, "usertitle");
        $finalHTML = str_replace("{inner}", $result, $html);
        return $finalHTML;
    }
}