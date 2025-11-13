<?php
/**
 * Centralized helper functions for formatting and utility operations
 */

/**
 * Shorten text to a maximum length with ellipsis
 * @param string $text Text to shorten
 * @param int $max Maximum length before truncation
 * @return string Shortened text with ellipsis if needed
 */
function shorten(string $text, int $max = 60): string {
    $text = trim($text);
    if (mb_strlen($text) <= $max) {
        return $text;
    }
    return rtrim(mb_substr($text, 0, $max - 1)) . '…';
}

/**
 * Format price in Polish złoty (PLN)
 * @param mixed $amount Amount to format
 * @return string Formatted price string
 */
function format_price($amount): string {
    if ($amount === null || $amount === '' || !is_numeric($amount)) {
        return '-';
    }
    $val = (float)$amount;
    if (floor($val) == $val) {
        return number_format($val, 0, ',', ' ') . ' zł';
    }
    return number_format($val, 2, ',', ' ') . ' zł';
}

/**
 * Sanitize and prepare image URL for display
 * @param string|null $imageName Image filename
 * @param string $directory Directory path relative to root
 * @param string $placeholder Default placeholder image path
 * @return string Escaped image URL
 */
function get_image_url(?string $imageName, string $directory = 'uploads/properties/', string $placeholder = 'assets/img/placeholder.png'): string {
    if (!empty($imageName)) {
        $imgSrc = $directory . rawurlencode($imageName);
    } else {
        $imgSrc = $placeholder;
    }
    return htmlspecialchars($imgSrc, ENT_QUOTES);
}

/**
 * Format datetime for display (short format)
 * @param string|null $datetime Datetime string
 * @return string Formatted datetime or empty string
 */
function format_datetime(?string $datetime): string {
    if (!$datetime) {
        return '';
    }
    return htmlspecialchars(substr($datetime, 0, 16));
}

/**
 * Escape HTML output safely
 * @param mixed $value Value to escape
 * @return string Escaped string
 */
function e($value): string {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}
