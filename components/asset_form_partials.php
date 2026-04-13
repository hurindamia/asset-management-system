<?php

if(!function_exists('asset_form_escape')){
    function asset_form_escape($value): string{
        return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
    }
}

if(!function_exists('asset_form_render_ram_options')){
    function asset_form_render_ram_options(?string $selected = ''): string{
        $sizes = ['2 GB', '4 GB', '8 GB', '16 GB', '32 GB', '64 GB'];
        $html = '<option value="">Select RAM</option>';

        foreach($sizes as $size){
            $isSelected = ((string)$selected === $size) ? ' selected' : '';
            $safe = asset_form_escape($size);
            $html .= '<option value="'.$safe.'"'.$isSelected.'>'.$safe.'</option>';
        }

        return $html;
    }
}

if(!function_exists('asset_form_render_windows_options')){
    function asset_form_render_windows_options(?string $selected = ''): string{
        $options = ['Windows 7', 'Windows 8.1', 'Windows 10', 'Windows 11', 'Mac OS'];
        $html = '<option value="">Select Windows</option>';

        foreach($options as $option){
            $isSelected = ((string)$selected === $option) ? ' selected' : '';
            $safe = asset_form_escape($option);
            $html .= '<option value="'.$safe.'"'.$isSelected.'>'.$safe.'</option>';
        }

        return $html;
    }
}

if(!function_exists('asset_form_render_error_popup')){
    function asset_form_render_error_popup(
        string $popupId,
        string $listId,
        string $title = 'Missing Information',
        string $message = 'Please review the following fields:'
    ): string{
        $safePopupId = asset_form_escape($popupId);
        $safeListId = asset_form_escape($listId);
        $safeTitle = asset_form_escape($title);
        $safeMessage = asset_form_escape($message);

        return <<<HTML
<div id="{$safePopupId}" class="popup">
<div class="popup-box">
<h3>{$safeTitle}</h3>
<p>{$safeMessage}</p>
<ul id="{$safeListId}" class="error-list"></ul>
<button type="button" class="popup-btn" onclick="closeErrorPopup('{$safePopupId}')">OK</button>
</div>
</div>
HTML;
    }
}

if(!function_exists('asset_form_render_sticky_action_bar')){
    function asset_form_render_sticky_action_bar(
        string $primaryLabel,
        string $primaryType = 'submit',
        string $primaryClass = 'save-btn',
        string $secondaryHtml = ''
    ): string{
        $safeLabel = asset_form_escape($primaryLabel);
        $safeType = asset_form_escape($primaryType);
        $safePrimaryClass = asset_form_escape(trim($primaryClass.' sticky-primary-btn'));

        return <<<HTML
<div class="sticky-action-bar">
{$secondaryHtml}
<button type="{$safeType}" class="{$safePrimaryClass}">{$safeLabel}</button>
</div>
HTML;
    }
}

