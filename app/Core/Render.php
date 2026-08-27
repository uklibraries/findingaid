<?php

function fa_render($fragment)
{
    $node = dom_import_simplexml($fragment);
    $segments = [];
    foreach ($node->childNodes as $child) {
        $segments[] = match ($child->nodeName) {
            'emph' => fa_render_title($child),
            'extref' => fa_render_extref($child),
            'title' => fa_render_title($child),
            default => $child->textContent,
        };
    }
    return trim(implode('', $segments));
}

function fa_render_title($node)
{
    $render = '';
    if ($node->hasAttribute('render')) {
        $render = match ($node->getAttribute('render')) {
            'italic' => '<i>' . $node->textContent . '</i>',
            'doublequote' => '"' . $node->textContent . '"',
            default => '"' . $node->textContent . '"',
        };
    } else {
        $render = $node->textContent;
    }
    return $render;
}

function fa_render_extref($node)
{
    $render = '';
    if ($node->hasAttribute('href')) {
        $render = fa_render_extref_ns($node, "");
    } elseif ($node->hasAttribute('xlink:href')) {
        $render = fa_render_extref_ns($node, "xlink");
    } else {
        $render = $node->textContent;
    }
    return $render;
}

function fa_render_extref_ns($node, $ns)
{
    $href_attr = 'href';
    $show_attr = 'show';

    if (strlen((string) $ns) > 0) {
        $href_attr = "$ns:href";
        $show_attr = "$ns:show";
    }

    $href = $node->getAttribute($href_attr);
    $show = $node->getAttribute($show_attr);
    $text = (string) $node->textContent;

    if (strlen($href) === 0 || strlen(trim($text)) === 0) {
        return $text;
    }
    return fa_render_link($href, $text, $show === 'new');
}

function fa_render_link($href, $content, $open_new_tab = false)
{
    $attributes = ['href="' . htmlspecialchars((string) $href, ENT_QUOTES) . '"'];
    if ($open_new_tab) {
        $attributes[] = 'target="_blank"';
        $attributes[] = 'rel="noopener noreferrer"';
    }
    $note = $open_new_tab ? 'external link, opens in a new tab' : 'external link';

    return '<a class="underline-link" ' . implode(' ', $attributes) . '>'
        . htmlspecialchars((string) $content, ENT_QUOTES)
        . ' <span class="ic ic--popup" aria-hidden="true"></span>'
        . ' <span class="show-for-sr">(' . $note . ')</span></a>';
}
