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
    }
    else if ($node->hasAttribute('xlink:href')) {
        $render = fa_render_extref_ns($node, "xlink");
    }
    else {
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

    $show_new = true;
    if ($node->hasAttribute($show_attr)) {
        $show_desire = $node->getAttribute($show_attr);
        if ($show_desire === 'replace') {
            $show_new = false;
        }
    }
    $link = '<a href="' . $href . '"';
    if ($show_new) {
        $link .= ' target="_blank" rel="nooopener noreferrer"';
    }
    $link .= '>' . $node->textContent . '</a>';
    return $link;
}
