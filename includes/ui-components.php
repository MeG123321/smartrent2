<?php
/**
 * Reusable UI components and table rendering functions
 */

/**
 * Render a property card for grid display
 * @param array $property Property data
 * @return string HTML for property card
 */
function render_property_card(array $property): string {
    $id = urlencode($property['id']);
    $imgUrl = get_image_url($property['image'] ?? null);
    $title = e(shorten($property['title'] ?? '', 60));
    $city = e($property['city'] ?? '');
    $price = e(format_price($property['price'] ?? 0));
    
    return <<<HTML
    <article class="card">
        <a href="property_details.php?id={$id}">
            <div class="card-img" style="background-image:url('{$imgUrl}')"></div>
            <div class="card-body">
                <h3>{$title}</h3>
                <p class="muted">{$city}</p>
                <div class="price">{$price} / miesiąc</div>
            </div>
        </a>
    </article>
HTML;
}

/**
 * Render a table of rentals
 * @param array $rentals Array of rental records
 * @param bool $showProperty Whether to show property column
 * @return string HTML table
 */
function render_rentals_table(array $rentals, bool $showProperty = true): string {
    if (empty($rentals)) {
        return '<p>Brak rezerwacji.</p>';
    }
    
    $propertyHeader = $showProperty ? '<th>Mieszkanie</th>' : '';
    $html = '<table class="table"><thead><tr><th>#</th>' . $propertyHeader . '<th>Okres</th><th>Cena</th><th>Data rezerwacji</th></tr></thead><tbody>';
    
    foreach ($rentals as $r) {
        $id = e($r['id']);
        $period = e($r['start_date']) . ' → ' . e($r['end_date']);
        $price = e(format_price($r['price']));
        $created = e($r['created_at']);
        
        $propertyCell = '';
        if ($showProperty) {
            $propertyName = e(($r['title'] ?? '') . ' — ' . ($r['city'] ?? ''));
            $propertyCell = "<td>{$propertyName}</td>";
        }
        
        $html .= "<tr><td>{$id}</td>{$propertyCell}<td>{$period}</td><td>{$price}</td><td>{$created}</td></tr>";
    }
    
    $html .= '</tbody></table>';
    return $html;
}

/**
 * Render alert message
 * @param string $message Message text
 * @param string $type Alert type (info, danger, success, warning)
 * @return string HTML for alert
 */
function render_alert(string $message, string $type = 'info'): string {
    $escapedMessage = e($message);
    return "<div class=\"alert alert-{$type}\">{$escapedMessage}</div>";
}

/**
 * Render multiple alert messages
 * @param array $messages Array of message strings
 * @param string $type Alert type
 * @return string HTML for alerts
 */
function render_alerts(array $messages, string $type = 'danger'): string {
    if (empty($messages)) {
        return '';
    }
    
    $html = "<div class=\"alert alert-{$type}\">";
    foreach ($messages as $msg) {
        $html .= '<div>' . e($msg) . '</div>';
    }
    $html .= '</div>';
    return $html;
}

/**
 * Render conversation item for messages list
 * @param array $conversation Conversation data
 * @param bool $isActive Whether this conversation is currently active
 * @return string HTML for conversation item
 */
function render_conversation_item(array $conversation, bool $isActive = false): string {
    $pid = (int)$conversation['property_id'];
    $partner = (int)$conversation['partner_id'];
    $activeClass = $isActive ? 'conv-active' : '';
    $thumb = $conversation['property_image'] ?? null;
    $partnerName = e($conversation['partner_name']);
    $propertyTitle = e($conversation['property_title']);
    $datetime = format_datetime($conversation['sent_at']);
    $snippet = e($conversation['snippet']);
    
    $thumbHtml = '';
    if ($thumb) {
        $thumbEsc = e($thumb);
        $thumbHtml = "<img src=\"{$thumbEsc}\" alt=\"miniatura\" style=\"width:156px;height:110px;object-fit:cover;display:block;\">";
    } else {
        $thumbHtml = '<div class="thumb-placeholder" style="width:56px;height:40px;"></div>';
    }
    
    return <<<HTML
    <div class="conv-item {$activeClass}" onclick="location.href='messages.php?property_id={$pid}&partner_id={$partner}'">
        <div class="conv-thumb" style="width:76px;height:120px;flex:0 0 56px;">
            {$thumbHtml}
        </div>
        <div class="conv-body">
            <strong class="conv-title">{$partnerName}</strong>
            <div class="conv-meta muted">{$propertyTitle} — {$datetime}</div>
            <div class="snippet">{$snippet}</div>
        </div>
    </div>
HTML;
}

/**
 * Render a message in chat thread
 * @param array $message Message data
 * @param int $currentUserId Current user ID to determine message direction
 * @return string HTML for message
 */
function render_message(array $message, int $currentUserId): string {
    $isMe = ((int)$message['from_user_id'] === $currentUserId);
    $class = $isMe ? 'me' : 'other';
    $fromName = e($message['from_name'] ?? ($isMe ? 'Ty' : 'Użytkownik'));
    $sentAt = e($message['sent_at']);
    $body = nl2br(e($message['body']));
    
    return <<<HTML
    <div class="message {$class}">
        <div class="meta"><strong>{$fromName}</strong> <span class="muted">{$sentAt}</span></div>
        <div class="body">{$body}</div>
        <div style="clear:both"></div>
    </div>
HTML;
}
