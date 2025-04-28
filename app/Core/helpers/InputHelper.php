<?php
/**
 * Input Helper
 * app/Helpers/InputHelper.php
 */

namespace App\Helpers;

class InputHelper {
    /**
     * Sanitize user input
     * 
     * @param string $input Input to sanitize
     * @return string Sanitized input
     */
    public static function sanitize($input) {
        if (is_array($input)) {
            return array_map([self::class, 'sanitize'], $input);
        }
        
        return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
    }
    
    /**
     * Sanitize HTML content
     * 
     * @param string $html HTML content
     * @return string Sanitized HTML
     */
    public static function sanitizeHtml($html) {
        // Allow only specific HTML tags and attributes
        $allowedTags = [
            'p', 'br', 'strong', 'em', 'u', 'h1', 'h2', 'h3', 'h4', 'h5', 'ul', 'ol', 'li', 
            'blockquote', 'a', 'img'
        ];
        
        $allowedAttrs = [
            'href', 'src', 'alt', 'title', 'class'
        ];
        
        // Use HTML Purifier or similar library for proper HTML sanitization
        // This is a simplified example
        $dom = new \DOMDocument();
        $dom->loadHTML(mb_convert_encoding($html, 'HTML-ENTITIES', 'UTF-8'), LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        
        $xpath = new \DOMXPath($dom);
        $nodes = $xpath->query('//*');
        
        foreach ($nodes as $node) {
            // Remove disallowed tags
            if (!in_array($node->nodeName, $allowedTags)) {
                $node->parentNode->removeChild($node);
                continue;
            }
            
            // Remove disallowed attributes
            if ($node->hasAttributes()) {
                foreach ($node->attributes as $attr) {
                    if (!in_array($attr->nodeName, $allowedAttrs)) {
                        $node->removeAttribute($attr->nodeName);
                    }
                }
            }
        }
        
        return $dom->saveHTML();
    }
}