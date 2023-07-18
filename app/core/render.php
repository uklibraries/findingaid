<?php

function fa_render($fragment)
{
    $node = dom_import_simplexml($fragment);
    $segments = array();
    foreach ($node->childNodes as $child) {
        switch ($child->nodeName) {
            case 'emph':
                $segments[] = fa_render_title($child);
                break;
            case 'extref':
                $segments[] = fa_render_extref($child);
                break;
            case 'title':
                $segments[] = fa_render_title($child);
                break;
            default:
                $segments[] = $child->textContent;
                break;
        }
    }
    return trim(implode('', $segments));
}

function fa_render_title($node)
{
    $render = '';
    if ($node->hasAttribute('render')) {
        switch ($node->getAttribute('render')) {
            case 'italic':
                $render = '<i>' . $node->textContent . '</i>';
                break;
            case 'doublequote':
                $render = '"' . $node->textContent . '"';
                break;
            default:
                $render = '"' . $node->textContent . '"';
                break;
        }
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

    if (strlen($ns) > 0) {
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
