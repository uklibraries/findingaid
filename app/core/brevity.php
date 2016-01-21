<?php

define('FA_MAX_LENGTH', 100);

function fa_brevity($message, $length = 0)
{
    if ($length == 0) {
        $length = FA_MAX_LENGTH;
    }
    if (strlen($message) > $length) {
        $source_words = preg_split('/\b/', $message);
        $target_words = array();
        $current_length = 0;
        foreach ($source_words as $word) {
            if (($current_length == 0) || $current_length + strlen($word) <= $length) {
                $target_words[] = $word;
                $current_length += strlen($word);
            }
            else {
                break;
            }
        }
        $count = count($target_words);
        if ($count == 0) {
            $message = '…';
        }
        else {
            $terminal = $target_words[$count - 1];
            if (preg_match('/^\W+$/', $terminal)) {
                array_pop($target_words);
            }
            $message = implode('', $target_words) . '…';
        }
    }
    return $message;
}
