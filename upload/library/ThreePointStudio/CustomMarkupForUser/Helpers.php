<?php
/*
* Custom Username Markup For User v1.0.0 written by tyteen4a03@3.studIo.
* This software is licensed under the BSD 2-Clause modified License.
* See the LICENSE file within the package for details.
*/

class ThreePointStudio_CustomMarkupForUser_Helpers {
    public static function startsWith($haystack, $needle) {
        return !strncmp($haystack, $needle, strlen($needle));
    }

    public static function stripHTMLHeader($html) {
        return preg_replace(array("/^\<\!DOCTYPE.*?<html><body>/si", "!</body></html>$!si"), "", $html);
    }

    public static function lazyArrayShift($array) {
        unset($array[0]);
        return $array;
    }

    public static function array_column($input = null, $columnKey = null, $indexKey = null) {
        if (!function_exists('array_column')) {
            /**
             * This file is part of the array_column library
             *
             * For the full copyright and license information, please view the LICENSE
             * file that was distributed with this source code.
             *
             * @copyright Copyright (c) 2013 Ben Ramsey <http://benramsey.com>
             * @license http://opensource.org/licenses/MIT MIT
             */

            /**
             * Returns the values from a single column of the input array, identified by
             * the $columnKey.
             *
             * Optionally, you may provide an $indexKey to index the values in the returned
             * array by the values from the $indexKey column in the input array.
             *
             * @param array $input A multi-dimensional array (record set) from which to pull
             *                     a column of values.
             * @param mixed $columnKey The column of values to return. This value may be the
             *                         integer key of the column you wish to retrieve, or it
             *                         may be the string key name for an associative array.
             * @param mixed $indexKey (Optional.) The column to use as the index/keys for
             *                        the returned array. This value may be the integer key
             *                        of the column, or it may be the string key name.
             * @return array
             */
            function array_column($input = null, $columnKey = null, $indexKey = null)
            {
                // Using func_get_args() in order to check for proper number of
                // parameters and trigger errors exactly as the built-in array_column()
                // does in PHP 5.5.
                $argc = func_num_args();
                $params = func_get_args();

                if ($argc < 2) {
                    trigger_error("array_column() expects at least 2 parameters, {$argc} given", E_USER_WARNING);
                    return null;
                }

                if (!is_array($params[0])) {
                    trigger_error('array_column() expects parameter 1 to be array, ' . gettype($params[0]) . ' given', E_USER_WARNING);
                    return null;
                }

                if (!is_int($params[1]) && !is_float($params[1]) && !is_string($params[1]) && $params[1] !== null
                    && !(is_object($params[1]) && method_exists($params[1], '__toString'))
                ) {
                    trigger_error('array_column(): The column key should be either a string or an integer', E_USER_WARNING);
                    return false;
                }

                if (isset($params[2]) && !is_int($params[2]) && !is_float($params[2]) && !is_string($params[2])
                    && !(is_object($params[2]) && method_exists($params[2], '__toString'))
                ) {
                    trigger_error('array_column(): The index key should be either a string or an integer', E_USER_WARNING);
                    return false;
                }

                $paramsInput = $params[0];
                $paramsColumnKey = ($params[1] !== null) ? (string) $params[1] : null;

                $paramsIndexKey = null;
                if (isset($params[2])) {
                    if (is_float($params[2]) || is_int($params[2])) {
                        $paramsIndexKey = (int) $params[2];
                    } else {
                        $paramsIndexKey = (string) $params[2];
                    }
                }

                $resultArray = array();

                foreach ($paramsInput as $row) {
                    $key = $value = null;
                    $keySet = $valueSet = false;

                    if ($paramsIndexKey !== null && array_key_exists($paramsIndexKey, $row)) {
                        $keySet = true;
                        $key = (string) $row[$paramsIndexKey];
                    }

                    if ($paramsColumnKey === null) {
                        $valueSet = true;
                        $value = $row;
                    } elseif (is_array($row) && array_key_exists($paramsColumnKey, $row)) {
                        $valueSet = true;
                        $value = $row[$paramsColumnKey];
                    }

                    if ($valueSet) {
                        if ($keySet) {
                            $resultArray[$key] = $value;
                        } else {
                            $resultArray[] = $value;
                        }
                    }
                }
                return $resultArray;
            }
        }
        return array_column($input, $columnKey, $indexKey);
    }

    # From http://kvz.io/blog/2008/09/05/php-recursive-str-replace-replacetree/
    public static function strReplaceRecursive($search="", $replace="", $array=false, $keys_too=false) {
        if (!is_array($array)) {
            // Regular replace
            return str_replace($search, $replace, $array);
        }

        $newArr = array();
        foreach ($array as $k => $v) {
            // Replace keys as well?
            $add_key = $k;
            if ($keys_too) {
                $add_key = str_replace($search, $replace, $k);
            }
            // Recurse
            $newArr[$add_key] = self::strReplaceRecursive($search, $replace, $v, $keys_too);
        }
        return $newArr;
    }

    public static function assembleCustomMarkup($options, $category) {
        if (!isset($options[$category]) || empty($options[$category])) { // No styling option set
            return "{inner}";
        }

        $sortedTags = $firstOccurrence = array();
        $dom = new DOMDocument();
        foreach ($options[$category] as $optionName => $optionValue) {
            foreach (ThreePointStudio_CustomMarkupForUser_Constants::$availableMarkups[$optionName]["format"] as $tag) {
                // Replace all placeholders, if necessary
                if (isset($tag[2]["variableFeed"])) {
                    foreach ($tag[2]["variableFeed"] as $var) {
                        $tag[1] = self::replacePlaceholders($var, $tag[1], $optionValue);
                    }
                }
                $firstOccurrence = array_search($tag[0], self::array_column($sortedTags, 0));
                if ($firstOccurrence !== false) {
                    $firstOccurrenceTag = &$sortedTags[$firstOccurrence];
                    // Try to see if we can merge the properties
                    if (isset($tag[2]["loneTag"]) && $tag[2]["loneTag"]) { // It wants it own tag
                        $sortedTags[] = $tag;
                        continue;
                    }
                    $intersection = array_keys(array_intersect_key($tag[1], $sortedTags[$firstOccurrence][1]));
                    if (in_array("style", $intersection)) {
                        if (isset($tag[2]["mergeProperties"]) && $tag[2]["mergeProperties"]) {
                            $firstOccurrenceTag[1]["style"] = array_merge_recursive($firstOccurrenceTag[1]["style"], $tag[1]["style"]);
                        } else {
                            $firstOccurrenceTag[1]["style"] = array_merge($firstOccurrenceTag[1]["style"], $tag[1]["style"]);
                        }
                        unset($tag[1]["style"]);
                    }
                    if (in_array("class", $intersection)) {
                        $firstOccurrenceTag[1]["class"] = array_replace($firstOccurrenceTag[1]["class"], $tag[1]["class"]);
                        unset($tag[1]["class"]);
                    }

                    // Try to put anything that is not in the first occurrence tag into the first occurrence tag instead
                    foreach ($tag[1] as $attr => $attrValue) {
                        if (!in_array($attr, array_keys($firstOccurrenceTag[1]))) {
                            $firstOccurrenceTag[1][$attr] = $attrValue;
                            unset($tag[1][$attr]);
                        }
                    }

                    if (!empty($tag[1])) {
                        // What is left is conflicted attributes. Leave as is in its own tag.
                        $sortedTags[] = $tag;
                    }
                } else {
                    $sortedTags[] = $tag;
                }
            }
        }
        $i = 0;
        $inner = $dom;
        foreach ($sortedTags as $tagItem) {
            $i++;
            $child = $dom->createElement($tagItem[0], ($i == count($sortedTags) ? "{inner}": ""));
            // Process attributes
            foreach ($tagItem[1] as $attr => &$attrValue) {
                $finalAttrValue = "";
                if ($attr == "style") {
                    foreach ($tagItem[1]["style"] as $propName => $propValues) {
                        $finalAttrValue .= $propName . ": " . implode(" ", $propValues) . "; ";
                    }
                } elseif ($attr == "class") {
                    $finalAttrValue .= implode(" ", $attrValue);
                } else {
                    $finalAttrValue = $attrValue;
                }
                $child->setAttribute($attr, $finalAttrValue);
            }
            // Add the node
            $inner = $inner->appendChild($child);
        }

        return self::stripHTMLHeader($dom->saveHTML());
    }

    public static function assembleCustomMarkupPermissionForUser($group) {
        $visitor = XenForo_Visitor::getInstance();
        $finalPermissions = array();
        switch ($group) {
            case "username":
                $titleCode = "UN";
                break;
            case "usertitle":
                $titleCode = "UT";
                break;
            default:
                throw new UnexpectedValueException();
        }
        foreach (ThreePointStudio_CustomMarkupForUser_Constants::$availableMarkups as $markupName => $markupArray) {
            $finalPermissions[$markupName] = $visitor->hasPermission("3ps_cmfu", sprintf($markupArray["permission"], $titleCode));
            if ($finalPermissions[$markupName] and !isset($finalPermissions["_" . $markupArray["category"]])) {
                $finalPermissions["_" . $markupArray["category"]] = true;
            }
        }
        return $finalPermissions;
    }

    protected static function replacePlaceholders($type, $str, $value) {
        switch ($type) {
            case "_value":
                $str = self::strReplaceRecursive("{_value}", $value, $str);
                break;
            case "fontFamily":
                $str = self::strReplaceRecursive("{fontFamily}", ThreePointStudio_CustomMarkupForUser_Constants::$fontList[$value]["fullname"], $str);
                break;
            case "borderStyle":
                $str = self::strReplaceRecursive("{borderStyle}", ThreePointStudio_CustomMarkupForUser_Constants::$borderList[$value], $str);
                break;
        }
        return $str;
    }

    public static function prepareSerializedOptionsForView($options) {
        $fullUserOptions = unserialize($options);
        if (!$fullUserOptions) {
            $fullUserOptions = ThreePointStudio_CustomMarkupForUser_Constants::$defaultOptionsArray;
        }
        foreach ($fullUserOptions as $category => $catArray) {
            foreach ($catArray as $itemName => $itemValue) {
                if (isset(ThreePointStudio_CustomMarkupForUser_Constants::$availableMarkups[$itemName]["enable_prefix"])) { // This item has an enable_ marker, tick it as well
                    $fullUserOptions[$category]["enable_" . $itemName] = true;
                }
            }
        }
        return $fullUserOptions;
    }

    public static function verifyColour($itemValue) {
        return (preg_match("/^#[a-fA-F0-9]{6}$/", $itemValue) or $itemValue == "");
    }

    public static function verifyBool($itemValue) {
        return in_array($itemValue, array(1, 0));
    }

    public static function verifyBorderList($itemValue) {
        return in_array($itemValue, array_keys(ThreePointStudio_CustomMarkupForUser_Constants::$borderList));
    }

    public static function verifyFontList($itemValue) {
        return in_array($itemValue, array_keys(ThreePointStudio_CustomMarkupForUser_Constants::$fontList));
    }

    public static function determineVersion() {
        $versionStrSplit = str_split(XenForo_Application::$versionId);
        return strval($versionStrSplit[0] . $versionStrSplit[2]);
    }
}